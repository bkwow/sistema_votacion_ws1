<?php // ---- ARCHIVO: user_create.php ----
$page_title = 'Crear Nuevo Usuario';
require_once 'includes/auth_check.php';
require_login(['superadmin']);
require_once 'includes/header.php';

$username = $full_name = $role = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    // --- Validación ---
    if (empty($username)) $errors[] = 'El nombre de usuario es obligatorio.';
    if (empty($full_name)) $errors[] = 'El nombre completo es obligatorio.';
    if (empty($password)) $errors[] = 'La contraseña es obligatoria.';
    if (empty($role)) $errors[] = 'El rol es obligatorio.';

    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = 'El nombre de usuario ya está en uso.';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Hashear la contraseña
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insertar usuario
            $sql = "INSERT INTO users (username, full_name, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $full_name, $hashed_password, $role]);
            $user_id = $pdo->lastInsertId();

            // Si es un admin, crear su registro de permisos
            if ($role === 'admin') {
                $sql_perms = "INSERT INTO admin_permissions (admin_id) VALUES (?)";
                $stmt_perms = $pdo->prepare($sql_perms);
                $stmt_perms->execute([$user_id]);
            }

            $pdo->commit();
            redirect('superadmin_dashboard.php?success=user_created');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error al crear el usuario: " . $e->getMessage();
        }
    }
}
?>

<div class="p-8 bg-white rounded-lg shadow max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Formulario de Creación de Usuario</h2>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">Error:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="user_create.php" method="POST">
        <div class="mb-4">
            <label for="full_name" class="block text-gray-700 text-sm font-bold mb-2">Nombre Completo:</label>
            <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($full_name); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-4">
            <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Nombre de Usuario:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña:</label>
            <input type="password" name="password" id="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-6">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Rol:</label>
            <select name="role" id="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="">Seleccione un rol</option>
                <option value="voter" <?php echo ($role === 'voter') ? 'selected' : ''; ?>>Votante</option>
                <option value="admin" <?php echo ($role === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                <option value="superadmin" <?php echo ($role === 'superadmin') ? 'selected' : ''; ?>>Superadministrador</option>
            </select>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Crear Usuario
            </button>
            <a href="superadmin_dashboard.php" class="inline-block align-baseline font-bold text-sm text-indigo-600 hover:text-indigo-800">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>

 

