<?php
// Iniciar la sesión en cada página que lo necesite
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// -- Configuración de la Base de Datos --
define('DB_HOST', 'localhost'); // O la IP de tu servidor de base de datos
define('DB_USER', 'root');      // Tu usuario de MySQL
define('DB_PASS', '');          // Tu contraseña de MySQL
define('DB_NAME', 'votingsystem');

// -- Conexión a la Base de Datos --
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Configurar el modo de error de PDO a excepción
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Deshabilitar emulación de preparaciones para seguridad
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Si la conexión falla, muestra un error genérico y termina la ejecución
    die("ERROR: No se pudo conectar a la base de datos. " . $e->getMessage());
}

// -- Configuración del Sitio --
define('SITE_URL', '192.168.1.99'); // En lugar de 'http://localhost/votingsystem'
define('PROJECT_NAME', 'Sistema de Votación en Tiempo Real');

// -- Configuración de WebSocket --
// CAMBIA 'localhost' por tu IP
define('WEBSOCKET_HOST', '192.168.1.99'); 
define('WEBSOCKET_PORT', '8080');
define('WEBSOCKET_SERVER', 'ws://' . WEBSOCKET_HOST . ':' . WEBSOCKET_PORT);
//define('WEBSOCKET_SERVER', 'ws://alone-wellington-mu-grant.trycloudflare.com');


// -- Funciones de Utilidad (pueden crecer) --

/**
 * Redirige a una página específica.
 * @param string $url La URL a la que redirigir.
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Verifica si un usuario ha iniciado sesión.
 * @return bool True si está logueado, false si no.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

?>
