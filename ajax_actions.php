
```

### Paso 2: Corregir el Gestor de Acciones

El archivo `ajax_actions.php` también necesita ser actualizado para enviar los mensajes a la nueva URL de la API.


```php
<?php
// ---- ARCHIVO: ajax_actions.php (CORREGIDO) ----
// Este archivo maneja todas las acciones que requieren comunicación en tiempo real.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/socketsbay_config.php';

header('Content-Type: application/json');

// Función para enviar mensajes a través de la API de SocketsBay (corregida)
function sendToSocketsBay($payload) {
    // SocketsBay espera el payload directamente, no dentro de un objeto 'message'.
    $data_string = json_encode($payload);

    $ch = curl_init(SOCKETSBAY_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string)
    ]);
    // Ya no se necesita el header 'Authorization', la API Key va en la URL.
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Log para depuración
    if ($http_code != 200) {
        error_log("Error al enviar a SocketsBay. Código: $http_code. Respuesta: $response");
    }

    return $response;
}

// Función para obtener los resultados de una votación (sin cambios)
function getVotingResults($pdo, $voting_id) {
    $sql = "SELECT vo.option_text, COUNT(v.id) as vote_count FROM voting_options vo LEFT JOIN votes v ON vo.id = v.option_id WHERE vo.voting_id = ? GROUP BY vo.id ORDER BY vo.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$voting_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

// El resto del switch no necesita cambios, ya que la lógica interna es la misma.
// Solo la función sendToSocketsBay() ha sido corregida.
switch ($action) {
    case 'start_voting':
        $voting_id = $data['voting_id'];
        $stmt = $pdo->prepare("UPDATE votings SET status = 'active' WHERE id = ?");
        $stmt->execute([$voting_id]);
        
        $stmt_options = $pdo->prepare("SELECT id, option_text FROM voting_options WHERE voting_id = ?");
        $stmt_options->execute([$voting_id]);
        $options = $stmt_options->fetchAll(PDO::FETCH_ASSOC);

        sendToSocketsBay(['action' => 'voting_started', 'voting_id' => $voting_id, 'options' => $options]);
        echo json_encode(['status' => 'success', 'message' => 'Votación iniciada.']);
        break;

    case 'stop_voting':
        $voting_id = $data['voting_id'];
        $stmt = $pdo->prepare("UPDATE votings SET status = 'closed' WHERE id = ?");
        $stmt->execute([$voting_id]);
        $results = getVotingResults($pdo, $voting_id);

        sendToSocketsBay(['action' => 'voting_closed', 'voting_id' => $voting_id, 'results' => $results]);
        echo json_encode(['status' => 'success', 'message' => 'Votación cerrada.']);
        break;

    case 'new_vote':
        session_start();
        $user_id = $_SESSION['user_id'];
        $voting_id = $data['voting_id'];
        $option_id = $data['option_id'];

        $stmt = $pdo->prepare("INSERT IGNORE INTO votes (voting_id, option_id, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$voting_id, $option_id, $user_id]);
        $results = getVotingResults($pdo, $voting_id);
        
        sendToSocketsBay(['action' => 'update_results', 'results' => $results]);
        echo json_encode(['status' => 'success', 'message' => 'Voto registrado.']);
        break;
    
    case 'chat_message':
        sendToSocketsBay(['action' => 'chat_message', 'username' => $data['username'], 'message' => $data['message'], 'color' => $data['color'] ?? '#333']);
        echo json_encode(['status' => 'success', 'message' => 'Mensaje enviado.']);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
?>