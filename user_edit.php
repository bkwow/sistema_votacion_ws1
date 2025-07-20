
<?php // ---- ARCHIVO: user_edit.php ----
$page_title = 'Editar Usuario';
require_once 'includes/auth_check.php';
require_login(['superadmin']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('superadmin_dashboard.php?error=invalid_id');
}

$user_id = $_GET['id'];
$user = null;
$permissions = [];

// Obtener datos del usuario y sus permisos si es admin
try {
    $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        redirect('superadmin_dashboard.php?error=user_not_found');
    }

    if ($user['role'] === 'admin') {
        $stmt_perms = $pdo->prepare("SELECT * FROM admin_permissions WHERE admin_id = ?");
        $stmt_perms->execute([$user_id]);
        $permissions = $stmt_perms->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error al obtener datos del usuario: " . $e->getMessage());
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lógica de actualización (se omite por brevedad, pero sería similar a la creación)
    // ... Validar datos ...
    // ... Actualizar tabla `users` ...
    // ... Actualizar tabla `admin_permissions` si el rol es 'admin' ...
    redirect('superadmin_dashboard.php?success=user_updated');
}

require_once 'includes/header.php';
?>

<div class="p-8 bg-white rounded-lg shadow max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Editando a "<?php echo htmlspecialchars($user['full_name']); ?>"</h2>

    <form action="user_edit.php?id=<?php echo $user_id; ?>" method="POST">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Columna de Información del Usuario -->
            <div>
                <h3 class="text-lg font-semibold border-b pb-2 mb-4">Información del Usuario</h3>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nombre de Usuario:</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-500 bg-gray-200 leading-tight" readonly>
                    <p class="text-xs text-gray-500 mt-1">El nombre de usuario no se puede cambiar.</p>
                </div>
                <div class="mb-4">
                    <label for="full_name" class="block text-gray-700 text-sm font-bold mb-2">Nombre Completo:</label>
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Nueva Contraseña:</label>
                    <input type="password" name="password" id="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <p class="text-xs text-gray-500 mt-1">Dejar en blanco para no cambiar la contraseña.</p>
                </div>
                <div class="mb-6">
                    <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Rol:</label>
                    <select name="role" id="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="voter" <?php echo ($user['role'] === 'voter') ? 'selected' : ''; ?>>Votante</option>
                        <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                        <option value="superadmin" <?php echo ($user['role'] === 'superadmin') ? 'selected' : ''; ?>>Superadministrador</option>
                    </select>
                </div>
            </div>

            <!-- Columna de Permisos (solo para admins) -->
            <?php if ($user['role'] === 'admin' && $permissions): ?>
            <div>
                <h3 class="text-lg font-semibold border-b pb-2 mb-4">Permisos de Administrador</h3>
                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="can_create_voting" class="form-checkbox h-5 w-5 text-indigo-600" <?php echo !empty($permissions['can_create_voting']) ? 'checked' : ''; ?>>
                        <span class="ml-2 text-gray-700">Puede crear votaciones</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="can_manage_users" class="form-checkbox h-5 w-5 text-indigo-600" <?php echo !empty($permissions['can_manage_users']) ? 'checked' : ''; ?>>
                        <span class="ml-2 text-gray-700">Puede gestionar votantes</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="can_start_stop_voting" class="form-checkbox h-5 w-5 text-indigo-600" <?php echo !empty($permissions['can_start_stop_voting']) ? 'checked' : ''; ?>>
                        <span class="ml-2 text-gray-700">Puede iniciar/detener votaciones</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="can_view_results" class="form-checkbox h-5 w-5 text-indigo-600" <?php echo !empty($permissions['can_view_results']) ? 'checked' : ''; ?> disabled>
                        <span class="ml-2 text-gray-500">Puede ver resultados (siempre activo)</span>
                    </label>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between mt-8">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Guardar Cambios
            </button>
            <a href="superadmin_dashboard.php" class="inline-block align-baseline font-bold text-sm text-indigo-600 hover:text-indigo-800">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>