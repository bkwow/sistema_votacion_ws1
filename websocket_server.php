<?php // ---- ARCHIVO: websocket_server.php (VERSIÓN FINAL Y ROBUSTA) ----

// Suprimimos las advertencias "Deprecated" para tener una consola limpia.
// Seguiremos viendo los errores fatales, que es lo que nos interesa.
error_reporting(E_ALL & ~E_DEPRECATED);

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require __DIR__ . '/vendor/autoload.php';

class VotingChat implements MessageComponentInterface {
    protected $clients;
    protected $users; // [resourceId => ['conn', 'user_id', 'username', 'role']]
    private $pdo;
    private $dbConfig;

    private $activeVotingId = null;
    private $activeVotingOptions = [];
    private $activeVotingTitle = null;
    private $activeVotingDescription = null;



    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        
        // Guardamos la configuración de la BD para usarla después
        require_once __DIR__ . '/config.php';
        $this->dbConfig = [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS
        ];

        $this->connectDb();
        echo "Servidor de Votación robusto iniciado. Escuchando en el puerto 8080...\n";
    }

    // --- MANEJO DE CONEXIÓN A LA BASE DE DATOS ---
    private function connectDb() {
        try {
            $dsn = "mysql:host=" . $this->dbConfig['host'] . ";dbname=" . $this->dbConfig['name'] . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->dbConfig['user'], $this->dbConfig['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            echo "Conexión a la base de datos establecida/refrescada.\n";
        } catch (PDOException $e) {
            echo "Error fatal de base de datos: " . $e->getMessage() . "\n";
        }
    }

    private function checkDbConnection() {
        try {
            $this->pdo->query("SELECT 1");
        } catch (PDOException $e) {
            echo "Conexión a la BD perdida. Reconectando...\n";
            $this->connectDb();
        }
    }

    // --- MANEJO DE EVENTOS WEBSOCKET ---

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nueva conexión! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data || !isset($data['action'])) return;

        echo "Mensaje recibido de {$from->resourceId}: " . $msg . "\n";

        // Comprobar la conexión a la BD solo si la acción lo requiere
        $actions_requiring_db = ['start_voting', 'stop_voting', 'new_vote', 'chat_message'];
        if (in_array($data['action'], $actions_requiring_db)) {
            $this->checkDbConnection();
        }

        switch ($data['action']) {
            case 'admin_connect':
            case 'user_connect':
                $this->handleUserConnect($from, $data);
                break;
            case 'start_voting':
                $this->handleStartVoting($data);
                break;
            case 'stop_voting':
                $this->handleStopVoting($data);
                break;
            case 'new_vote':
                $this->handleNewVote($from, $data);
                break;
            case 'chat_message':
                $this->handleChatMessage($from, $data);
                break;
            case 'update_chat_config': // <-- NUEVO CASO
                $this->handleUpdateChatConfig($data);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        if (isset($this->users[$conn->resourceId])) {
            unset($this->users[$conn->resourceId]);
            $this->broadcastUserList();
        }
        echo "Conexión {$conn->resourceId} se ha desconectado\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Ocurrió un error en la conexión {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    

    // --- LÓGICA DE LA APLICACIÓN (CON SESIÓN ÚNICA) ---
   private function handleUserConnect($conn, $data) {

        foreach ($this->users as $resourceId => $user) {
            if (isset($user['user_id']) && $user['user_id'] === $data['user_id'] && $resourceId !== $conn->resourceId) {
                echo "Encontrada sesión antigua para el usuario {$data['username']} ({$resourceId}). Terminando...\n";
                // Enviar comando de logout al cliente antiguo.
                $user['conn']->send(json_encode([
                    'action' => 'force_logout',
                    'message' => 'Has iniciado sesión en otro dispositivo. Esta sesión se cerrará.'
                ]));
                // Cerrar la conexión antigua desde el servidor.
                $user['conn']->close();
            }
        }
        $this->users[$conn->resourceId] = [
            'conn' => $conn,
            'user_id' => $data['user_id'],
            'username' => $data['username'],
            'role' => $data['role']
        ];
       // 3. Actualizar la lista de asistencia para los administradores.
        $this->broadcastUserList();

   

        // --- LÓGICA DE RESTAURACIÓN DE ESTADO MEJORADA ---
        if ($this->activeVotingId !== null) {
            $this->checkDbConnection();
            
            if ($data['role'] === 'voter') {
                $vote_info = $this->getUserVoteInfo($this->activeVotingId, $data['user_id']);
                $conn->send(json_encode([
                    'action' => 'voting_started',
                    'voting_id' => $this->activeVotingId,
                    'title' => $this->activeVotingTitle,
                    'description' => $this->activeVotingDescription,
                    'options' => $this->activeVotingOptions,
                    'has_voted' => $vote_info['has_voted'],
                    'selected_option_id' => $vote_info['selected_option_id'] // <-- Se envía la opción votada
                ]));
            }
            elseif ($data['role'] === 'admin' || $data['role'] === 'superadmin') {
                $results = $this->getVotingResults($this->activeVotingId);
                $conn->send(json_encode(['action' => 'update_results', 'results' => $results]));
            }
        }
    }
    // --- NUEVA FUNCIÓN AUXILIAR ---
    private function checkIfUserVoted($voting_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM votes WHERE voting_id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$voting_id, $user_id]);
            return $stmt->fetch() ? true : false;
        } catch(PDOException $e) {
            echo "Error en BD al comprobar voto: " . $e->getMessage() . "\n";
            return false;
        }
    }
    private function getUserVoteInfo($voting_id, $user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT option_id FROM votes WHERE voting_id = ? AND user_id = ? LIMIT 1");
            $stmt->execute([$voting_id, $user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                return ['has_voted' => true, 'selected_option_id' => (int)$result['option_id']];
            }
            return ['has_voted' => false, 'selected_option_id' => null];
        } catch(PDOException $e) {
            echo "Error en BD al comprobar voto: " . $e->getMessage() . "\n";
            return ['has_voted' => false, 'selected_option_id' => null];
        }
    }
    private function handleChatMessage($from, $data) {
        // Cargar la configuración del chat en cada mensaje para que los cambios sean instantáneos
        require __DIR__ . '/chat_config.php';

        // Guardar en la base de datos si está activado
        if (defined('SAVE_CHAT_ENABLED') && SAVE_CHAT_ENABLED === true) {
            $this->saveChatMessageToDb($from, $data);
        }

        // Transmitir a todos los clientes si el chat está activado
        if (defined('CHAT_ENABLED') && CHAT_ENABLED === true) {
            $this->broadcastToAll(json_encode([
                'action' => 'chat_message',
                'username' => $data['username'],
                'message' => $data['message'],
                'color' => $data['color'] ?? '#333'
            ]));
        }
    }

    // --- NUEVA FUNCIÓN PARA GUARDAR EN BD ---
    private function saveChatMessageToDb($from, $data) {
        try {
            $user_id = $this->users[$from->resourceId]['user_id'] ?? null;
            // Solo guardar si el mensaje proviene de un usuario registrado en la sesión del WebSocket
            if ($user_id) {
                $sql = "INSERT INTO chat_messages (user_id, message) VALUES (?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$user_id, $data['message']]);
            }
        } catch (PDOException $e) {
            echo "Error al guardar mensaje de chat: " . $e->getMessage() . "\n";
        }
    }
    private function broadcastUserList() {
        $admins = array_filter($this->users, fn($user) => $user['role'] === 'admin' || $user['role'] === 'superadmin');
        $voters = array_filter($this->users, fn($user) => $user['role'] === 'voter');
        $voter_list = array_values(array_map(fn($v) => ['username' => $v['username']], $voters));

        foreach ($admins as $admin) {
            $admin['conn']->send(json_encode(['action' => 'update_user_list', 'users' => $voter_list]));
        }
    }

    private function handleStartVoting($data) {
        // --- ACTUALIZADO: Guardar título y descripción en la memoria del servidor ---
        $this->activeVotingId = $data['voting_id'];
        $this->activeVotingTitle = $data['title'];
        $this->activeVotingDescription = $data['description'];
        $this->activeVotingOptions = $data['options'];
        
        try {
            $stmt = $this->pdo->prepare("UPDATE votings SET status = 'active' WHERE id = ?");
            $stmt->execute([$this->activeVotingId]);
            
             // --- ACTUALIZADO: Retransmitir la pregunta completa a todos ---
            $this->broadcastToAll(json_encode([
                'action' => 'voting_started',
                'voting_id' => $this->activeVotingId,
                'title' => $this->activeVotingTitle,
                'description' => $this->activeVotingDescription,
                'options' => $this->activeVotingOptions,
                'has_voted' => false // Nadie ha votado al iniciar
            ]));
        } catch(PDOException $e) {
            echo "Error al iniciar votación: " . $e->getMessage() . "\n";
        }
    }

    private function handleStopVoting($data) {
        $voting_id = $data['voting_id'];

        try {
            $stmt = $this->pdo->prepare("UPDATE votings SET status = 'closed' WHERE id = ?");
            $stmt->execute([$voting_id]);
            $results = $this->getVotingResults($voting_id);

            $this->broadcastToAll(json_encode(['action' => 'voting_closed', 'voting_id' => $voting_id, 'results' => $results]));
            
            $this->activeVotingId = null;
            $this->activeVotingTitle = null;
            $this->activeVotingDescription = null;
            $this->activeVotingOptions = [];
        } catch(PDOException $e) {
            echo "Error al cerrar votación: " . $e->getMessage() . "\n";
        }
    }

    private function handleNewVote($from, $data) {
        $user_id = $this->users[$from->resourceId]['user_id'];
        try {
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO votes (voting_id, option_id, user_id) VALUES (?, ?, ?)");
            $stmt->execute([$data['voting_id'], $data['option_id'], $user_id]);

            $results = $this->getVotingResults($data['voting_id']);
            $admins = array_filter($this->users, fn($user) => $user['role'] === 'admin' || $user['role'] === 'superadmin');
            foreach ($admins as $admin) {
                $admin['conn']->send(json_encode(['action' => 'update_results', 'results' => $results]));
            }
        } catch(PDOException $e) {
            echo "Error al registrar voto: " . $e->getMessage() . "\n";
        }
    }

    private function getVotingResults($voting_id) {
        $sql = "SELECT vo.option_text, COUNT(v.id) as vote_count FROM voting_options vo LEFT JOIN votes v ON vo.id = v.option_id WHERE vo.voting_id = ? GROUP BY vo.id ORDER BY vo.id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$voting_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function broadcastChatMessage($data) {
        $this->broadcastToAll(json_encode(['action' => 'chat_message', 'username' => $data['username'], 'message' => $data['message'], 'color' => $data['color'] ?? '#333']));
    }

    private function broadcastToAll($message) {
        foreach ($this->clients as $client) {
            $client->send($message);
        }
    }

    private function handleUpdateChatConfig($data) {
        if (isset($data['chat_enabled'])) {
            $this->broadcastToAll(json_encode([
                'action' => 'chat_status_changed',
                'enabled' => (bool)$data['chat_enabled']
            ]));
            echo "Retransmitiendo estado del chat: " . ($data['chat_enabled'] ? 'Activado' : 'Desactivado') . "\n";
        }
    }
}

// --- Iniciar el servidor ---
$server = IoServer::factory(new HttpServer(new WsServer(new VotingChat())), 8080);
$server->run();

?>