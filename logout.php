<?php // ---- ARCHIVO: logout.php ----

// Iniciar sesión para poder destruirla
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración para la función redirect
require_once __DIR__ . '/config.php';

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se está usando cookies de sesión, borrarlas
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir a la página de inicio de sesión
redirect('login.php');

?>
