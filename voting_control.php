<?php
$page_title = 'Control de Votación';
require_once __DIR__ . '/includes/auth_check.php';
// Incluir la configuración de los gráficos
require_once __DIR__ . '/chart_config.php';
require_login(['admin', 'superadmin']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('admin_dashboard.php?error=invalid_id');
}
$voting_id = (int)($_GET['id'] ?? 0);


$results_on_load = [];


// Obtener datos de la votación y sus opciones
try {
    $stmt = $pdo->prepare("SELECT * FROM votings WHERE id = ?");
    $stmt->execute([$voting_id]);
    $voting = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$voting) { redirect('admin_dashboard.php?error=voting_not_found'); }

    $stmt_options = $pdo->prepare("SELECT id, option_text FROM voting_options WHERE voting_id = ? ORDER BY id");
    $stmt_options->execute([$voting_id]);
    $options = $stmt_options->fetchAll(PDO::FETCH_ASSOC);

    if ($voting['status'] === 'active' || $voting['status'] === 'closed') {
        $sql_results = "SELECT vo.option_text, COUNT(v.id) as vote_count FROM voting_options vo LEFT JOIN votes v ON vo.id = v.option_id WHERE vo.voting_id = ? GROUP BY vo.id ORDER BY vo.id";
        $stmt_results = $pdo->prepare($sql_results);
        $stmt_results->execute([$voting_id]);
        $results_on_load = $stmt_results->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>
<!-- Incluir Chart.js para los gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Columna de Control y Resultados -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
        <div class="border-b pb-4 mb-4">
            <h2 class="text-2xl font-bold">Votación: "<?php echo htmlspecialchars($voting['title']); ?>"</h2>
            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($voting['description']); ?></p>
            <p class="text-sm text-gray-500 mt-2">Estado actual: <span id="voting-status-badge" class="font-semibold text-gray-800"><?php echo ucfirst($voting['status']); ?></span></p>
        </div>

        <div class="mb-6">
            <h3 class="text-xl font-bold mb-4">Controles de la Sesión</h3>
            <div id="control-buttons" class="flex space-x-4">
                <button id="start-voting-btn" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" /></svg>
                    Iniciar Votación
                </button>
                <button id="stop-voting-btn" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-300 ease-in-out flex items-center gap-2">
                     <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd" /></svg>
                    Cerrar Votación
                </button>
            </div>
        </div>

        <div class="border-t pt-4">
             <h3 class="text-xl font-bold mb-4">Resultados en Vivo</h3>
             <div id="live-results">
                <p class="text-gray-500">Los resultados aparecerán aquí cuando la votación inicie.</p>
                <!-- El gráfico se inyectará aquí -->
                <canvas id="resultsChart"></canvas>
             </div>
        </div>
    </div>

    <!-- Columna de Asistencia y Chat -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h3 class="text-xl font-bold mb-4">Asistencia (<span id="connected-users-count">0</span>)</h3>
        <ul id="connected-users-list" class="list-none pl-0 h-48 overflow-y-auto bg-gray-50 p-3 rounded border">
            <li class="text-gray-400">Esperando conexiones...</li>
        </ul>

        <div class="border-t pt-4 mt-4">
            <h3 class="text-xl font-bold mb-4">Chat General</h3>
            <div id="chat-box" class="border rounded-lg p-3 h-48 overflow-y-auto bg-gray-50">
                <!-- Mensajes del chat -->
            </div>
            <div class="mt-2 flex">
                <input type="text" id="chat-message-input" class="flex-grow border rounded-l-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enviar mensaje a todos...">
                <button id="send-chat-btn" class="bg-indigo-600 text-white px-4 rounded-r-lg hover:bg-indigo-700">Enviar</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal para forzar el cierre de sesión (AÑADIDO) -->
<div id="force-logout-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-80 flex items-center justify-center z-50 transition-opacity duration-300">
    <div class="bg-white p-8 rounded-lg shadow-xl text-center max-w-sm mx-auto">
        <h2 class="text-2xl font-bold text-red-600 mb-4">Sesión Terminada</h2>
        <p id="force-logout-message" class="text-gray-700 mb-6">Un momento...</p>
        <a href="login.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">
            Aceptar
        </a>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {

    const votingTitle = <?php echo json_encode($voting['title'] ?? 'Sin Título', JSON_THROW_ON_ERROR); ?>;
    const votingDescription = <?php echo json_encode($voting['description'] ?? '', JSON_THROW_ON_ERROR); ?>;
    const votingId = <?php echo json_encode($voting_id ?? 0, JSON_THROW_ON_ERROR); ?>;
    const votingOptions = <?php echo json_encode($options ?? [], JSON_THROW_ON_ERROR); ?>;
    const initialStatus = <?php echo json_encode($voting['status'] ?? 'pending', JSON_THROW_ON_ERROR); ?>;
    const initialResults = <?php echo json_encode($results_on_load ?? [], JSON_THROW_ON_ERROR); ?>;

    const startBtn = document.getElementById('start-voting-btn');
    const stopBtn = document.getElementById('stop-voting-btn');


    
    const adminUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
    const adminUsername = <?php echo json_encode($_SESSION['username']); ?>;
    
    // --- LA CORRECCIÓN ESTÁ AQUÍ ---
    const adminUserRole = <?php echo json_encode($_SESSION['user_role']); ?>; 
          
    const resultsContainer = document.getElementById('live-results');
    const userList = document.getElementById('connected-users-list');
    const userCount = document.getElementById('connected-users-count');
    const statusBadge = document.getElementById('voting-status-badge');
    const chatBox = document.getElementById('chat-box');
    const chatInput = document.getElementById('chat-message-input');
    const sendChatBtn = document.getElementById('send-chat-btn');
    
    let resultsChart = null;
    const chartType = <?php echo json_encode(CHART_TYPE); ?>;


    const ws_uri = "<?php echo WEBSOCKET_SERVER; ?>";
    const websocket = new WebSocket(ws_uri);

    websocket.onopen = function(e) {
        console.log("Conexión de administrador establecida!");
        websocket.send(JSON.stringify({
            action: 'admin_connect',
            user_id: adminUserId,
            username: adminUsername,
            role: adminUserRole,
            voting_id: votingId
        }));
    };
    // --- LÓGICA PARA RESTAURAR ESTADO AL CARGAR (CORREGIDA) ---
    if (initialStatus === 'active' || initialStatus === 'closed') {
        startBtn.disabled = true;
        startBtn.classList.add('opacity-50', 'cursor-not-allowed');
        initializeChart(votingOptions);
        updateChart(initialResults); // Dibujar el gráfico con los resultados de la BD
    }
    if (initialStatus === 'closed') {
        stopBtn.disabled = true;
        stopBtn.classList.add('opacity-50', 'cursor-not-allowed');
        document.getElementById('control-buttons').innerHTML = '<p class="font-bold text-red-600">La votación ha finalizado.</p>';
    }


    websocket.onmessage = function(e) {
        console.log("Mensaje recibido por admin: ", e.data);
        const data = JSON.parse(e.data);

        switch(data.action) {
            case 'update_user_list':
                updateUserList(data.users);
                break;
            case 'voting_started':
                statusBadge.textContent = 'Activa';
                resultsContainer.innerHTML = '<canvas id="resultsChart"></canvas>'; // Limpiar y preparar para el gráfico
                initializeChart(data.options);
                break;
            case 'voting_closed':
                statusBadge.textContent = 'Cerrada';
                document.getElementById('control-buttons').innerHTML = '<p class="font-bold text-red-600">La votación ha finalizado.</p>';
                // Actualizar el gráfico con los resultados finales
                updateChart(data.results);
                break;
            case 'update_results':
                updateChart(data.results);
                break;
            case 'chat_message':
                appendChatMessage(data);
                break;
                
        }
    };

    websocket.onclose = function(e) {
        console.log("Conexión cerrada.");
        statusBadge.textContent = 'Desconectado';
        statusBadge.classList.add('text-red-500');
    };

    websocket.onerror = function(e) {
        console.error("Error en WebSocket: ", e);
        statusBadge.textContent = 'Error de Conexión';
        statusBadge.classList.add('text-red-500');
    };

    // --- Funciones de Interfaz ---
    function updateUserList(users) {
        userList.innerHTML = ''; // Limpiar lista
        if (users.length === 0) {
             userList.innerHTML = '<li class="text-gray-400">No hay votantes conectados.</li>';
        } else {
            users.forEach(user => {
                const li = document.createElement('li');
                li.textContent = user.username;
                li.className = 'text-gray-800';
                userList.appendChild(li);
            });
        }
        userCount.textContent = users.length;
    }

    function initializeChart(options) {
        resultsContainer.innerHTML = '<canvas id="resultsChart"></canvas>';
        const ctx = document.getElementById('resultsChart').getContext('2d');
        let chartConfig = createChartConfig(chartType, options.map(o => o.option_text), []);
        resultsChart = new Chart(ctx, chartConfig);
    }

    function updateChart(results) {
        if (!resultsChart) return;
        resultsChart.data.datasets[0].data = results.map(r => r.vote_count);
        resultsChart.update();
    }

    // Función de utilidad para crear la configuración del gráfico
    function createChartConfig(type, labels, data) {
        let config = {
            type: type === 'horizontalBar' ? 'bar' : type,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Votos',
                    data: data,
                    backgroundColor: ['rgba(54, 162, 235, 0.7)', 'rgba(255, 99, 132, 0.7)', 'rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)'],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        };

        if (type === 'bar' || type === 'horizontalBar') {
            config.options.indexAxis = (type === 'horizontalBar') ? 'y' : 'x';
            config.options.scales = { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { beginAtZero: true, ticks: { stepSize: 1 } } };
        } else { // pie
            config.options.plugins = { legend: { position: 'top' } };
        }
        return config;
    }

    function appendChatMessage(data) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'mb-2 text-sm';
        msgDiv.innerHTML = `<strong style="color: ${data.color || '#333'}">${htmlspecialchars(data.username)}:</strong> ${htmlspecialchars(data.message)}`;
        chatBox.appendChild(msgDiv);
        chatBox.scrollTop = chatBox.scrollHeight; // Auto-scroll
    }

    function htmlspecialchars(str) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }

    // --- Event Listeners de Botones ---

    startBtn.addEventListener('click', function() {
        this.disabled = true;
        this.classList.add('opacity-50');
        websocket.send(JSON.stringify({
            action: 'start_voting',
            voting_id: votingId,
            title: votingTitle,
            description: votingDescription,
            options: votingOptions
        }));
        initializeChart(votingOptions);
    });

    stopBtn.addEventListener('click', function() {
        websocket.send(JSON.stringify({
            action: 'stop_voting',
            voting_id: votingId
        }));
        this.disabled = true;
        this.classList.add('opacity-50', 'cursor-not-allowed');
    });

    sendChatBtn.addEventListener('click', function() {
        const message = chatInput.value.trim();
        if (message) {
            websocket.send(JSON.stringify({
                action: 'chat_message',
                username: adminUsername,
                message: message,
                color: '#b91c1c' // Color rojo para el admin
            }));
            chatInput.value = '';
        }
    });

    chatInput.addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            sendChatBtn.click();
        }
    });



});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
