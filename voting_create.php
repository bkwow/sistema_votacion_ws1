<?php
// ---- ARCHIVO: voting_create.php (CORREGIDO Y SIMPLIFICADO) ----
$page_title = 'Crear Nueva Votación';
require_once __DIR__ . '/includes/auth_check.php';
require_login(['admin', 'superadmin']);
require_once __DIR__ . '/includes/header.php';

$errors = [];
$title = '';
$description = '';
$options = ['', '']; // Iniciar con dos opciones vacías por defecto

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $options = $_POST['options'];

    // Validación de los datos
    if (empty($title)) {
        $errors[] = 'El título de la votación es obligatorio.';
    }
    
    // Filtrar opciones vacías y asegurarse de que haya al menos dos
    $options = array_filter($options, function($value) { return !empty(trim($value)); });
    if (count($options) < 2) {
        $errors[] = 'Se requieren al menos dos opciones de votación.';
    }

    // Si no hay errores, proceder a guardar en la base de datos
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insertar la votación (sin el campo de YouTube)
            $sql = "INSERT INTO votings (title, description, created_by) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $description, $_SESSION['user_id']]);
            $voting_id = $pdo->lastInsertId();

            // Insertar las opciones asociadas a la nueva votación
            $sql_option = "INSERT INTO voting_options (voting_id, option_text) VALUES (?, ?)";
            $stmt_option = $pdo->prepare($sql_option);
            foreach ($options as $option) {
                $stmt_option->execute([$voting_id, trim($option)]);
            }

            $pdo->commit();
            redirect('admin_dashboard.php?success=voting_created');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error al crear la votación: " . $e->getMessage();
        }
    }
}
?>

<div class="p-8 bg-white rounded-lg shadow max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Formulario de Creación de Votación</h2>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <strong class="font-bold">Error:</strong>
            <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <form action="voting_create.php" method="POST">
        <div class="mb-4">
            <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Título de la Votación:</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
        </div>
        <div class="mb-4">
            <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Descripción (Opcional):</label>
            <textarea name="description" id="description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <div id="options-container" class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Opciones de Voto:</label>
            <?php foreach ($options as $index => $option): ?>
            <div class="flex items-center mb-2 option-group">
                <input type="text" name="options[]" value="<?php echo htmlspecialchars($option); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="Ej: Sí, No, Abstenerse" required>
                <button type="button" onclick="removeOption(this)" class="ml-2 bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">&times;</button>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" id="add-option-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded mb-6">
            + Añadir Opción
        </button>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Crear Votación
            </button>
            <a href="admin_dashboard.php" class="inline-block align-baseline font-bold text-sm text-indigo-600 hover:text-indigo-800">
                Cancelar
            </a>
        </div>
    </form>
</div>

<script>
// Este script permite añadir y quitar opciones de voto dinámicamente
document.getElementById('add-option-btn').addEventListener('click', function() {
    const container = document.getElementById('options-container');
    const newOption = document.createElement('div');
    newOption.className = 'flex items-center mb-2 option-group';
    newOption.innerHTML = `
        <input type="text" name="options[]" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="Nueva opción" required>
        <button type="button" onclick="removeOption(this)" class="ml-2 bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">&times;</button>
    `;
    container.appendChild(newOption);
});

function removeOption(button) {
    const optionGroup = button.parentElement;
    // No permitir eliminar si solo quedan dos opciones
    if (document.querySelectorAll('.option-group').length > 2) {
        optionGroup.remove();
    } else {
        alert('Debe haber al menos dos opciones de votación.');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
