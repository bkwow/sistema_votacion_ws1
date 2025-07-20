
<?php // ---- ARCHIVO: user_delete.php ----
$page_title = 'Eliminar Usuario';
require_once 'includes/auth_check.php';
require_login(['superadmin']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('superadmin_dashboard.php?error=invalid_id');
}
$user_id = $_GET['id'];

if ($user_id == $_SESSION['user_id']) {
    redirect('superadmin_dashboard.php?error=self_delete');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            redirect('superadmin_dashboard.php?success=user_deleted');
        } catch (PDOException $e) {
            redirect('superadmin_dashboard.php?error=delete_failed');
        }
    }
}

// Obtener nombre para confirmación
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    redirect('superadmin_dashboard.php?error=user_not_found');
}

require_once 'includes/header.php';
?>
<div class="p-8 bg-white rounded-lg shadow max-w-lg mx-auto text-center">
    <h2 class="text-2xl font-bold mb-4 text-red-600">Confirmar Eliminación</h2>
    <p class="text-gray-700 mb-6">
        ¿Estás seguro de que quieres eliminar permanentemente al usuario <strong><?php echo htmlspecialchars($user['username']); ?></strong>?
        <br>
        Esta acción no se puede deshacer.
    </p>
    <form action="user_delete.php?id=<?php echo $user_id; ?>" method="POST">
        <div class="flex justify-center gap-4">
            <a href="superadmin_dashboard.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                Cancelar
            </a>
            <button type="submit" name="confirm_delete" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                Sí, Eliminar
            </button>
        </div>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
