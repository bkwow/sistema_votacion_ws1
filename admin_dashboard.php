<?php
$page_title = 'Panel de Administración';
// La corrección clave está en esta línea:
require_once __DIR__ . '/includes/auth_check.php';
require_login(['admin', 'superadmin']);
require_once __DIR__ . '/includes/header.php';

// Lógica para mostrar votaciones
$votings = [];
$message = '';
try {
    $sql = "SELECT v.*, u.full_name as creator_name 
            FROM votings v 
            JOIN users u ON v.created_by = u.id 
            ORDER BY v.created_at DESC";
    $stmt = $pdo->query($sql);
    $votings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message = '<div class="bg-red-100 text-red-700 p-4 rounded">Error al cargar las votaciones.</div>';
}

// Mensajes de éxito/error
if (isset($_GET['success'])) {
    $success_msg = '';
    switch ($_GET['success']) {
        case 'voting_created': $success_msg = 'Votación creada exitosamente.'; break;
        case 'voting_updated': $success_msg = 'Votación actualizada exitosamente.'; break;
        case 'voting_deleted': $success_msg = 'Votación eliminada exitosamente.'; break;
    }
    $message = '<div class="bg-green-100 text-green-700 p-4 rounded mb-4">' . $success_msg . '</div>';
}
?>

<div class="p-8 bg-white rounded-lg shadow">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Gestión de Votaciones</h2>
        <a href="voting_create.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
            + Crear Votación
        </a>
    </div>

    <?php echo $message; ?>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Título</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Estado</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Creado por</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php if (empty($votings)): ?>
                    <tr><td colspan="4" class="text-center py-4">No hay votaciones creadas.</td></tr>
                <?php else: ?>
                    <?php foreach ($votings as $voting): ?>
                    <tr>
                        <td class="text-left py-3 px-4 font-medium"><?php echo htmlspecialchars($voting['title']); ?></td>
                        <td class="text-left py-3 px-4">
                            <?php 
                                $status_color = 'bg-gray-200 text-gray-800';
                                if ($voting['status'] === 'active') $status_color = 'bg-green-200 text-green-800';
                                if ($voting['status'] === 'closed') $status_color = 'bg-red-200 text-red-800';
                            ?>
                            <span class="inline-block rounded-full px-3 py-1 text-sm font-semibold <?php echo $status_color; ?>">
                                <?php echo ucfirst($voting['status']); ?>
                            </span>
                        </td>
                        <td class="text-left py-3 px-4"><?php echo htmlspecialchars($voting['creator_name']); ?></td>
                        <td class="text-left py-3 px-4">
                            <a href="voting_control.php?id=<?php echo $voting['id']; ?>" class="text-blue-600 hover:text-blue-900 font-semibold">Controlar</a>
                            <a href="voting_edit.php?id=<?php echo $voting['id']; ?>" class="text-indigo-600 hover:text-indigo-900 ml-4 font-semibold">Editar</a>
                            <a href="voting_delete.php?id=<?php echo $voting['id']; ?>" class="text-red-600 hover:text-red-900 ml-4 font-semibold">Eliminar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
