<?php
// ---- ARCHIVO: socketsbay_config.php (CORREGIDO SEGÚN DOCUMENTACIÓN) ----
// Configuración para el servicio de WebSocket de SocketsBay.com

// Reemplaza 'TU_API_KEY' con la API Key de tu cuenta de SocketsBay.
define('SOCKETSBAY_API_KEY', 'ae9ae91d28ba54bb222a9623db9a02e6');

// Elige el ID del canal que quieres usar (del 1 al 5).
define('SOCKETSBAY_CHANNEL_ID', '10');

// URL para conectarse desde el navegador (cliente) - Formato corregido
define('SOCKETSBAY_WSS_URL', 'wss://socketsbay.com/wss/v2/' . SOCKETSBAY_CHANNEL_ID . '/' . SOCKETSBAY_API_KEY . '/');

// URL para enviar mensajes desde el servidor (PHP) - Formato corregido
define('SOCKETSBAY_API_URL', 'https://socketsbay.com/api/v2/' . SOCKETSBAY_CHANNEL_ID . '/' . SOCKETSBAY_API_KEY . '/publish');
?>