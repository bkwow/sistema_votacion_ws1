<?php // ---- ARCHIVO: voting_delete.php (NUEVO) ----
$page_title = 'Eliminar Votación';
require_once __DIR__ . '/includes/auth_check.php';
require_login(['admin', 'superadmin']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('admin_dashboard.php?error=invalid_id');
}
$voting_id = $_GET['id'];

// Solo permitir eliminar si la votación está 'pendiente'
$stmt = $pdo->prepare("SELECT title, status FROM votings WHERE id = ?");
$stmt->execute([$voting_id]);
$voting = $stmt->fetch();

if (!$voting) {
    redirect('admin_dashboard.php?error=voting_not_found');
}
if ($voting['status'] !== 'pending') {
    redirect('admin_dashboard.php?error=delete_not_allowed');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        try {
            // La eliminación en cascada (ON DELETE CASCADE) se encargará de las opciones
            $stmt = $pdo->prepare("DELETE FROM votings WHERE id = ?");
            $stmt->execute([$voting_id]);
            redirect('admin_dashboard.php?success=voting_deleted');
        } catch (PDOException $e) {
            redirect('admin_dashboard.php?error=delete_failed');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="p-8 bg-white rounded-lg shadow max-w-lg mx-auto text-center">
    <h2 class="text-2xl font-bold mb-4 text-red-600">Confirmar Eliminación</h2>
    <p class="text-gray-700 mb-6">
        ¿Estás seguro de que quieres eliminar la votación <strong>"<?php echo htmlspecialchars($voting['title']); ?>"</strong>?
        <br>
        Esta acción es permanente y eliminará todas sus opciones asociadas.
    </p>
    <form action="voting_delete.php?id=<?php echo $voting_id; ?>" method="POST">
        <div class="flex justify-center gap-4">
            <a href="admin_dashboard.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                Cancelar
            </a>
            <button type="submit" name="confirm_delete" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                Sí, Eliminar
            </button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
