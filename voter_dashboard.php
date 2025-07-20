<?php // ---- ARCHIVO: voter_dashboard.php (ACTUALIZADO) ----
$page_title = 'Panel del Votante';
require_once __DIR__ . '/includes/auth_check.php';
require_login(['voter']);
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/livestream_config.php';
require_once __DIR__ . '/chat_config.php';

?>
<!-- Incluir Chart.js para los gráficos de resultados -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="p-8 bg-white rounded-lg shadow">
    <!-- Contenedor para la transmisión en vivo -->
    <?php if (!empty(YOUTUBE_VIDEO_ID)): ?>
    <div id="livestream-container" class="mb-8">
        <h2 class="text-2xl font-bold mb-4">Transmisión en Vivo</h2>
        <div class="aspect-w-16 aspect-h-9 bg-black rounded-lg overflow-hidden shadow-lg" style="padding-bottom: 56.25%; position: relative; height: 0;">
            <iframe id="youtube-player" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                    src="https://www.youtube.com/embed/<?php echo YOUTUBE_VIDEO_ID; ?>?autoplay=1" 
                    frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
            </iframe>
        </div>
    </div>
    <?php endif; ?>
    <div id="main-content">
        <h2 class="text-2xl font-bold mb-4">Votación</h2>
        <div id="voting-area" class="text-center text-gray-500 p-8 border-2 border-dashed rounded-lg">
            <p id="voting-status-text">Esperando que el administrador inicie una votación...</p>
        </div>
    </div>
    <div id="chat-wrapper"> <!-- Envoltura para el chat -->
        <h2 class="text-2xl font-bold mt-8 mb-4">Chat</h2>
        <div id="chat-ui-container" class="<?php echo CHAT_ENABLED ? '' : 'hidden'; ?>">
            <div id="chat-box" class="border rounded-lg p-3 h-64 overflow-y-auto bg-gray-50"></div>
            <div class="mt-2 flex">
                <input type="text" id="chat-message-input" class="flex-grow border rounded-l-lg p-2" placeholder="Escribe un mensaje...">
                <button id="send-chat-btn" class="bg-indigo-600 text-white px-4 rounded-r-lg">Enviar</button>
            </div>
        </div>
        <div id="chat-disabled-message" class="bg-gray-100 p-4 rounded-lg text-center text-gray-500 <?php echo CHAT_ENABLED ? 'hidden' : ''; ?>">
            <p>El chat está desactivado por el administrador.</p>
        </div>
    </div>

    <!-- Modal para forzar el cierre de sesión -->
    <div id="force-logout-modal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-80 flex items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white p-8 rounded-lg shadow-xl text-center max-w-sm mx-auto">
            <h2 class="text-2xl font-bold text-red-600 mb-4">Sesión Terminada</h2>
            <p id="force-logout-message" class="text-gray-700 mb-6">Un momento...</p>
            <a href="logout.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg">
                Aceptar
            </a>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const userId = <?php echo json_encode($_SESSION['user_id']); ?>;
    const username = <?php echo json_encode($_SESSION['username']); ?>;
    const userRole = <?php echo json_encode($_SESSION['user_role']); ?>;


    
    const votingArea = document.getElementById('voting-area');
    const statusText = document.getElementById('voting-status-text');
    const chatBox = document.getElementById('chat-box');
    const chatInput = document.getElementById('chat-message-input');
    const sendChatBtn = document.getElementById('send-chat-btn');
    const chatUiContainer = document.getElementById('chat-ui-container');
    const chatDisabledMessage = document.getElementById('chat-disabled-message');
 

    let currentVotingId = null;
    const ws_uri = "<?php echo WEBSOCKET_SERVER; ?>";
    let websocket;

    function connect() {
        websocket = new WebSocket(ws_uri);

        websocket.onopen = function(e) {
            console.log("Conexión de votante establecida!");
            websocket.send(JSON.stringify({
                action: 'user_connect',
                user_id: userId,
                username: username,
                role: userRole
            }));
        };

        websocket.onmessage = function(e) {
            const data = JSON.parse(e.data);
            switch(data.action) {
                case 'voting_started':
                currentVotingId = data.voting_id;
                if (data.has_voted) {
                    displayVotedAndWaitingInterface(data); // <-- Nueva función para este caso
                } else {
                    displayVotingInterface(data);
                }
                break;

                case 'voting_closed':
                    displayFinalResults(data.results);
                    break;
                case 'chat_message':
                    appendChatMessage(data);
                    break;
                case 'force_logout':
                    websocket.onmessage = null;
                    websocket.onerror = null;
                    websocket.onclose = null;
                    document.getElementById('force-logout-message').textContent = data.message;
                    document.getElementById('force-logout-modal').classList.remove('hidden');
                    setTimeout(function() {
                        window.location.href = 'logout.php';
                    }, 3000);
                    break;
                case 'chat_status_changed': // <-- NUEVO CASO
                    toggleChatUI(data.enabled);
                    break;

            }
        };

        websocket.onerror = function(e) {
            console.error("Error en WebSocket:", e);
        };

        websocket.onclose = function(e) {
            console.log("Conexión cerrada. Intentando reconectar en 5 segundos...");
            if (!document.getElementById('force-logout-modal').classList.contains('hidden')) {
                return; // No reconectar si fuimos forzados a salir.
            }
            setTimeout(connect, 5000);
        };
    }
   function displayVotedAndWaitingInterface(data) {
        votingArea.innerHTML = '';
        votingArea.classList.remove('p-8', 'border-2', 'border-dashed', 'text-center');
        votingArea.classList.add('text-left');

        const title = document.createElement('h3');
        title.className = 'text-2xl font-bold mb-2 text-gray-800';
        title.textContent = data.title;
        votingArea.appendChild(title);

        if (data.description) {
            const description = document.createElement('p');
            description.className = 'text-gray-600 mb-6';
            description.textContent = data.description;
            votingArea.appendChild(description);
        }

        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'flex flex-wrap justify-center gap-4 pt-4 border-t';
        
        data.options.forEach(option => {
            const button = document.createElement('button');
            button.textContent = option.option_text;
            button.disabled = true; // Deshabilitar todos los botones
            
            // Poner todos en gris y luego resaltar el votado
            button.className = 'bg-gray-400 text-white font-bold py-3 px-6 rounded-lg text-lg cursor-not-allowed';
            if (option.id === data.selected_option_id) {
                button.classList.remove('bg-gray-400');
                button.classList.add('bg-green-600'); // Resaltar el votado
            }
            buttonContainer.appendChild(button);
        });
        votingArea.appendChild(buttonContainer);

        const thankYouDiv = document.createElement('div');
        thankYouDiv.className = 'w-full text-center p-4 mt-6';
        thankYouDiv.innerHTML = '<h3 class="text-xl font-bold text-green-600">¡Gracias por votar!</h3><p class="text-gray-600">Esperando que el administrador cierre la votación para ver los resultados.</p>';
        votingArea.appendChild(thankYouDiv);
    }
    function displayVotingOptions(options) {
        votingArea.innerHTML = '';
        votingArea.classList.remove('p-8', 'border-2', 'border-dashed');
        const title = document.createElement('h3');
        title.className = 'text-xl font-bold mb-6 text-gray-800';
        title.textContent = 'Por favor, emita su voto:';
        votingArea.appendChild(title);
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'flex flex-wrap justify-center gap-4';
        options.forEach(option => {
            const button = document.createElement('button');
            button.textContent = option.option_text;
            button.className = 'bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg text-lg transition duration-300';
            button.onclick = function() {
                sendVote(option.id);
                buttonContainer.querySelectorAll('button').forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('opacity-50', 'cursor-not-allowed');
                });
                title.textContent = 'Gracias por votar. Esperando resultados...';
            };
            buttonContainer.appendChild(button);
        });
        votingArea.appendChild(buttonContainer);
    }

    function displayVotingInterface(data) {
        votingArea.innerHTML = '';
        votingArea.classList.remove('p-8', 'border-2', 'border-dashed', 'text-center');
        votingArea.classList.add('text-left');

        const title = document.createElement('h3');
        title.className = 'text-2xl font-bold mb-2 text-gray-800';
        title.textContent = data.title;
        votingArea.appendChild(title);

        if (data.description) {
            const description = document.createElement('p');
            description.className = 'text-gray-600 mb-6';
            description.textContent = data.description;
            votingArea.appendChild(description);
        }

        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'flex flex-wrap justify-center gap-4 pt-4 border-t';
        
        data.options.forEach(option => {
            const button = document.createElement('button');
            button.textContent = option.option_text;
            button.className = 'bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg text-lg transition duration-300';
            button.onclick = function() {
                sendVote(option.id);
                
                // --- LÓGICA MEJORADA POST-VOTO ---
                // 1. Deshabilitar todos los botones y ponerlos en gris
                buttonContainer.querySelectorAll('button').forEach(btn => {
                    btn.disabled = true;
                    btn.classList.remove('bg-blue-500', 'hover:bg-blue-600');
                    btn.classList.add('bg-gray-400', 'cursor-not-allowed');
                });

                // 2. Resaltar el botón seleccionado con un color de éxito
                this.classList.remove('bg-gray-400');
                this.classList.add('bg-green-600');

                // 3. Añadir el mensaje de agradecimiento debajo
                const thankYouDiv = document.createElement('div');
                thankYouDiv.className = 'w-full text-center p-4 mt-6';
                thankYouDiv.innerHTML = '<h3 class="text-xl font-bold text-green-600">¡Gracias por votar!</h3><p class="text-gray-600">Esperando que el administrador cierre la votación para ver los resultados.</p>';
                votingArea.appendChild(thankYouDiv);
            };
            buttonContainer.appendChild(button);
        });
        votingArea.appendChild(buttonContainer);
    }

    function sendVote(optionId) {
        websocket.send(JSON.stringify({ action: 'new_vote', voting_id: currentVotingId, option_id: optionId }));
    }

    function displayFinalResults(results) {
        votingArea.innerHTML = '<h3 class="text-xl font-bold mb-4">Resultados Finales</h3><div style="height:300px;"><canvas id="resultsChartVoter"></canvas></div>';
        const ctx = document.getElementById('resultsChartVoter').getContext('2d');
        const chartType = <?php echo json_encode(CHART_TYPE); ?>;
        let chartConfig = createChartConfig(chartType, results.map(r => r.option_text), results.map(r => r.vote_count));
        new Chart(ctx, chartConfig);
    }

    // Función de utilidad (la misma que en el panel de control)
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
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function htmlspecialchars(str) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }

    sendChatBtn.addEventListener('click', function() {
        const message = chatInput.value.trim();
        if (message) {
            websocket.send(JSON.stringify({ action: 'chat_message', username: username, message: message }));
            chatInput.value = '';
        }
    });

    chatInput.addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            sendChatBtn.click();
        }
    });

    function toggleChatUI(enabled) {
        if (enabled) {
            chatUiContainer.classList.remove('hidden');
            chatDisabledMessage.classList.add('hidden');
        } else {
            chatUiContainer.classList.add('hidden');
            chatDisabledMessage.classList.remove('hidden');
        }
    }
    connect(); // Iniciar la conexión inicial.
});
</script>
 