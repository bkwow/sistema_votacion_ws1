<?php
// ---- ARCHIVO: voting_edit.php (CON ADVERTENCIA) ----
$page_title = 'Editar Votación';
require_once __DIR__ . '/includes/auth_check.php';
require_login(['admin', 'superadmin']);

// 1. Validar el ID de la votación
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('admin_dashboard.php?error=invalid_id');
}
$voting_id = (int)$_GET['id'];
$edit_allowed = true; // Variable para controlar si se puede editar
$error_message = ''; // Variable para el mensaje de advertencia

// Obtener los datos de la votación ANTES de procesar el formulario
try {
    $stmt = $pdo->prepare("SELECT * FROM votings WHERE id = ?");
    $stmt->execute([$voting_id]);
    $voting = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voting) {
        redirect('admin_dashboard.php?error=voting_not_found');
    }

    // Comprobar si la edición está permitida
    if ($voting['status'] !== 'pending') {
        $edit_allowed = false;
        $status_text = ($voting['status'] === 'active') ? 'activa' : 'cerrada';
        $error_message = "Esta votación está {$status_text} y ya no puede ser editada.";
    }

    // Solo obtener las opciones si se permite la edición
    if ($edit_allowed) {
        $stmt_options = $pdo->prepare("SELECT option_text FROM voting_options WHERE voting_id = ?");
        $stmt_options->execute([$voting_id]);
        $options_data = $stmt_options->fetchAll(PDO::FETCH_COLUMN);
    }

} catch (PDOException $e) {
    die("Error al obtener datos de la votación: " . $e->getMessage());
}

// 2. Procesar el formulario (POST) SOLO SI la edición está permitida
if ($edit_allowed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $options = $_POST['options'];
    $errors = [];

    // Validación de datos
    if (empty($title)) $errors[] = 'El título es obligatorio.';
    $options = array_filter($options, fn($value) => !empty(trim($value)));
    if (count($options) < 2) $errors[] = 'Se requieren al menos dos opciones de votación.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Actualizar la información principal de la votación
            $sql_update = "UPDATE votings SET title = ?, description = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$title, $description, $voting_id]);

            // Borrar las opciones antiguas para reemplazarlas con las nuevas
            $sql_delete_options = "DELETE FROM voting_options WHERE voting_id = ?";
            $stmt_delete = $pdo->prepare($sql_delete_options);
            $stmt_delete->execute([$voting_id]);

            // Insertar las nuevas opciones
            $sql_insert_option = "INSERT INTO voting_options (voting_id, option_text) VALUES (?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert_option);
            foreach ($options as $option) {
                $stmt_insert->execute([$voting_id, trim($option)]);
            }

            $pdo->commit();
            redirect('admin_dashboard.php?success=voting_updated');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error al actualizar la votación: " . $e->getMessage();
        }
    }
}

// 3. Rellenar las variables para el formulario
$title = $voting['title'];
if ($edit_allowed) {
    $description = $voting['description'];
    $options = $options_data;
} else {
    // Valores por defecto si no se puede editar para evitar errores en el HTML
    $description = '';
    $options = [];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="p-8 bg-white rounded-lg shadow max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Editando Votación: <?php echo htmlspecialchars($title); ?></h2>

    <?php if (!$edit_allowed): ?>
        <!-- Bloque de advertencia si la edición no está permitida -->
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded-md" role="alert">
            <p class="font-bold text-lg">No se puede editar</p>
            <p><?php echo $error_message; ?></p>
        </div>
        <div class="mt-6">
            <a href="admin_dashboard.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                &larr; Volver al Panel de Administración
            </a>
        </div>
    <?php else: ?>
        <!-- Formulario de edición (solo se muestra si la edición está permitida) -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
            </div>
        <?php endif; ?>

        <form action="voting_edit.php?id=<?php echo $voting_id; ?>" method="POST">
            <div class="mb-4">
                <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Título de la Votación:</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
            </div>
            <div class="mb-4">
                <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descripción (Opcional):</label>
                <textarea name="description" id="description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div id="options-container" class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Opciones de Voto:</label>
                <?php foreach ($options as $option): ?>
                <div class="flex items-center mb-2 option-group">
                    <input type="text" name="options[]" value="<?php echo htmlspecialchars($option); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    <button type="button" onclick="removeOption(this)" class="ml-2 bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" id="add-option-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded mb-6">
                + Añadir Opción
            </button>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                    Guardar Cambios
                </button>
                <a href="admin_dashboard.php" class="inline-block font-bold text-sm text-indigo-600 hover:text-indigo-800">
                    Cancelar
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
// El script solo se activa si la edición está permitida, para evitar errores
<?php if ($edit_allowed): ?>
document.getElementById('add-option-btn').addEventListener('click', function() {
    const container = document.getElementById('options-container');
    const newOption = document.createElement('div');
    newOption.className = 'flex items-center mb-2 option-group';
    newOption.innerHTML = `
        <input type="text" name="options[]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="Nueva opción">
        <button type="button" onclick="removeOption(this)" class="ml-2 bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">&times;</button>
    `;
    container.appendChild(newOption);
});

function removeOption(button) {
    const optionGroup = button.parentElement;
    if (document.querySelectorAll('.option-group').length > 2) {
        optionGroup.remove();
    } else {
        alert('Debe haber al menos dos opciones.');
    }
}
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
