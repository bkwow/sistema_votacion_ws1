<?php
// ---- ARCHIVO: reports.php ----
$page_title = 'Reportes de Votaciones';
require_once __DIR__ . '/includes/auth_check.php';
require_login(['admin', 'superadmin']);
require_once __DIR__ . '/includes/header.php';

// Obtener todas las votaciones que ya han sido cerradas
$votings = [];
try {
    $sql = "SELECT id, title, description, created_at 
            FROM votings 
            WHERE status = 'closed' 
            ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    $votings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded">Error al cargar los reportes.</div>';
}
?>

<div class="p-8 bg-white rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-6">Historial de Votaciones Finalizadas</h2>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Título de la Votación</th>
                    <th class="text-left py-3 px-4 uppercase font-semibold text-sm">Fecha de Creación</th>
                    <th class="text-center py-3 px-4 uppercase font-semibold text-sm">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                <?php if (empty($votings)): ?>
                    <tr><td colspan="3" class="text-center py-10 text-gray-500">No hay votaciones finalizadas para mostrar.</td></tr>
                <?php else: ?>
                    <?php foreach ($votings as $voting): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($voting['title']); ?></td>
                        <td class="py-3 px-4"><?php echo date("d/m/Y H:i", strtotime($voting['created_at'])); ?></td>
                        <td class="text-center py-3 px-4">
                            <a href="report_view.php?id=<?php echo $voting['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition duration-300">
                                Ver Reporte
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
