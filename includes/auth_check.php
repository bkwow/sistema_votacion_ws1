<?php // ---- ARCHIVO: includes/auth_check.php ----

// Este script se incluirá al principio de cada página protegida.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración de la base de datos y funciones
// Usamos __DIR__ para asegurarnos de que la ruta siempre sea correcta.
require_once __DIR__ . '/../config.php';

/**
 * Verifica si el usuario está logueado y tiene el rol requerido.
 * Si no, lo redirige a la página de login.
 *
 * @param array $required_roles Un array de roles permitidos para la página.
 */
function require_login($required_roles = []) {
    // Si el usuario no ha iniciado sesión, redirigir a login
    if (!isLoggedIn()) {
        // Asegurarse de que SITE_URL está definido antes de usarlo
        $login_url = defined('SITE_URL') ? SITE_URL . '/login.php' : 'login.php';
        redirect($login_url);
    }

    // Si se especifican roles, verificar que el usuario tenga uno de ellos
    if (!empty($required_roles)) {
        $user_role = $_SESSION['user_role'];
        if (!in_array($user_role, $required_roles)) {
            // Si no tiene el rol, es un acceso no autorizado.
            $login_url = defined('SITE_URL') ? SITE_URL . '/login.php?error=unauthorized' : 'login.php?error=unauthorized';
            redirect($login_url);
        }
    }
}
?>

