<?php
$page_title = 'Panel de Superadministrador';
// La corrección clave está en esta línea:
require_once __DIR__ . '/includes/auth_check.php';
require_login(['superadmin']);
require_once __DIR__ . '/includes/header.php';

// Lógica para mostrar usuarios
$users = [];
$message = '';
try {
    $stmt = $pdo->query("SELECT id, username, full_name, role FROM users ORDER BY username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message = '<div class="bg-red-100 text-red-700 p-4 rounded">Error al cargar usuarios.</div>';
}

// Mensajes de éxito/error desde otras páginas
if (isset($_GET['success'])) {
    $success_msg = '';
    switch ($_GET['success']) {
        case 'user_created': $success_msg = 'Usuario creado exitosamente.'; break;
        case 'user_updated': $success_msg = 'Usuario actualizado exitosamente.'; break;
        case 'user_deleted': $success_msg = 'Usuario eliminado exitosamente.'; break;
    }
    $message = '<div class="bg-green-100 text-green-700 p-4 rounded mb-4">' . $success_msg . '</div>';
}
if (isset($_GET['error'])) {
    // Manejar errores...
}
?>

<div class="p-8 bg-white rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Gestión de Usuarios</h2>
        <a href="user_create.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
            + Crear Usuario
        </a>
    </div>
    
    <?php echo $message; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Usuario</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Nombre Completo</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Rol</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php if (empty($users)): ?>
                    <tr><td colspan="4" class="text-center py-4">No hay usuarios para mostrar.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="text-left py-3 px-4"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="text-left py-3 px-4"><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td class="text-left py-3 px-4">
                            <span class="inline-block bg-gray-200 rounded-full px-3 py-1 text-sm font-semibold text-gray-700 mr-2">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </span>
                        </td>
                        <td class="text-left py-3 px-4">
                            <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 font-semibold">Editar</a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="user_delete.php?id=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-900 ml-4 font-semibold">Eliminar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
