<?php // ---- ARCHIVO: login.php (LÓGICA REVISADA) ----
// Incluir el archivo de configuración que ya inicia la sesión y conecta a la BD
require_once 'config.php';

// Si el usuario ya está logueado, redirigir a su dashboard correspondiente
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    if ($role === 'superadmin') redirect('superadmin_dashboard.php');
    elseif ($role === 'admin') redirect('admin_dashboard.php');
    else redirect('voter_dashboard.php');
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_message = 'Por favor, ingrese su usuario y contraseña.';
    } else {
        try {
            $sql = "SELECT id, username, password, role FROM users WHERE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch();
                if (password_verify($password, $user['password'])) {
                    // --- LÓGICA DE ID DE DISPOSITIVO ---
                    // Generar un ID único para este nuevo dispositivo/sesión.
                    $device_id = uniqid('device_', true);

                    // Almacenar información del usuario en la sesión.
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['device_id'] = $device_id; // Guardamos el ID del dispositivo.

                    // Actualizar el ID del dispositivo en la base de datos.
                    // Este es el paso que lo convierte en la "última sesión válida".
                    $update_sql = "UPDATE users SET last_login_device_id = :device_id WHERE id = :user_id";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->bindParam(':device_id', $device_id, PDO::PARAM_STR);
                    $update_stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                    $update_stmt->execute();

                    // Redirigir según el rol.
                    if ($user['role'] === 'superadmin') redirect('superadmin_dashboard.php');
                    elseif ($user['role'] === 'admin') redirect('admin_dashboard.php');
                    else redirect('voter_dashboard.php');
                } else {
                    $error_message = 'La contraseña ingresada no es válida.';
                }
            } else {
                $error_message = 'No se encontró una cuenta con ese nombre de usuario.';
            }
        } catch (PDOException $e) {
            $error_message = 'Ocurrió un error. Por favor, intente de nuevo más tarde.';
        }
    }
}
// El resto del HTML de login.php no cambia...
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo PROJECT_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS para la apariencia moderna */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5; /* Un gris claro para el fondo */
        }
        .login-container {
            background: white;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-radius: 1rem; /* Bordes más redondeados */
            overflow: hidden;
            display: flex;
            max-width: 900px;
            width: 100%;
        }
        .login-image {
            background: url('https://placehold.co/450x600/3498db/ffffff?text=Sistema+de+Votación') no-repeat center center;
            background-size: cover;
        }
        .form-input {
            border: 1px solid #d1d5db;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
        }
        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            outline: none;
        }
        .submit-btn {
            background-color: #3b82f6;
            color: white;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: background-color 0.3s ease;
        }
        .submit-btn:hover {
            background-color: #2563eb;
        }
        .error-banner {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">

    <div class="login-container">
        <!-- Columna de la imagen -->
        <div class="hidden md:block w-1/2 login-image">
        </div>

        <!-- Columna del formulario -->
        <div class="w-full md:w-1/2 p-8 md:p-12">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido</h1>
            <p class="text-gray-600 mb-8">Inicia sesión para continuar</p>

            <?php if (!empty($error_message)): ?>
                <div class="error-banner p-4 rounded-lg mb-6 text-sm">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-6">
                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Usuario</label>
                    <input type="text" id="username" name="username" class="form-input w-full px-4 py-3" placeholder="ej. votante1" required>
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-input w-full px-4 py-3" placeholder="••••••••" required>
                </div>
                <div class="mb-6">
                    <button type="submit" class="submit-btn w-full py-3">
                        Iniciar Sesión
                    </button>
                </div>
            </form>
            <p class="text-center text-gray-500 text-xs">
                &copy;<?php echo date("Y"); ?> <?php echo PROJECT_NAME; ?>. Todos los derechos reservados.
            </p>
        </div>
    </div>

</body>
</html>
