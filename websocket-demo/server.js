const WebSocket = require('ws');
const wss = new WebSocket.Server({ port: 8080 });

console.log('Servidor WebSocket de votación iniciado en ws://localhost:8080');

// Estado en memoria
let votos = {
  opcion1: 0,
  opcion2: 0,
  opcion3: 0
};

// Función para enviar resultados a todos
function broadcastResultados() {
  const resultados = {
    action: 'update_results',
    votos: votos
  };

  wss.clients.forEach(client => {
    if (client.readyState === WebSocket.OPEN) {
      client.send(JSON.stringify(resultados));
    }
  });
}

wss.on('connection', (ws) => {
  console.log('Nuevo cliente conectado');

  // Al conectar, enviar resultados actuales
  ws.send(JSON.stringify({
    action: 'update_results',
    votos: votos
  }));

  ws.on('message', (message) => {
    console.log('Mensaje recibido:', message);

    try {
      const data = JSON.parse(message);

      if (data.action === 'new_vote' && votos.hasOwnProperty(data.opcion)) {
        votos[data.opcion]++;
        console.log(`Voto registrado: ${data.opcion}`);
        broadcastResultados();
      }
    } catch (err) {
      console.log('Error al procesar mensaje JSON:', err);
    }
  });

  ws.on('close', () => {
    console.log('Cliente desconectado');
  });
});
