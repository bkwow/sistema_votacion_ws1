<?php
// ---- ARCHIVO NUEVO: chart_settings.php ----
$page_title = 'Ajustes de Gráficos';
require_once __DIR__ . '/includes/auth_check.php';
require_login(['admin', 'superadmin']);
require_once __DIR__ . '/includes/header.php';

$success_message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chart_type = $_POST['chart_type'] ?? 'bar';
    // Validar que el tipo de gráfico sea uno de los permitidos
    if (in_array($chart_type, ['bar', 'horizontalBar', 'pie'])) {
        $config_content = "<?php\n\n// Archivo generado automáticamente desde los ajustes.\n\n";
        $config_content .= "// Opciones: 'bar', 'horizontalBar', 'pie'\n";
        $config_content .= "define('CHART_TYPE', '" . $chart_type . "');\n";

        if (file_put_contents('chart_config.php', $config_content) !== false) {
            $success_message = '¡La configuración de los gráficos se ha guardado correctamente!';
        } else {
            $errors[] = 'Error: No se pudo escribir en el archivo de configuración.';
        }
    } else {
        $errors[] = 'Tipo de gráfico no válido.';
    }
}

// Cargar la configuración actual para mostrarla en el formulario
require_once __DIR__ . '/chart_config.php';
?>

<div class="p-8 bg-white rounded-lg shadow max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Configuración de Gráficos de Resultados</h2>

    <?php if ($success_message): ?>
        <div class="bg-green-100 border-green-400 text-green-700 border px-4 py-3 rounded relative mb-4">
            <span><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
         <div class="bg-red-100 border-red-400 text-red-700 border px-4 py-3 rounded relative mb-4">
            <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <p class="text-gray-600 mb-6">
        Selecciona el tipo de gráfico que se usará para mostrar los resultados de las votaciones en los reportes y en el panel de control del administrador.
    </p>

    <form action="chart_settings.php" method="POST">
        <div class="space-y-4">
            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                <input type="radio" name="chart_type" value="bar" class="h-5 w-5 text-indigo-600" <?php echo (CHART_TYPE === 'bar') ? 'checked' : ''; ?>>
                <span class="ml-3 text-gray-700 font-medium">Barras Verticales (Por defecto)</span>
            </label>
            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                <input type="radio" name="chart_type" value="horizontalBar" class="h-5 w-5 text-indigo-600" <?php echo (CHART_TYPE === 'horizontalBar') ? 'checked' : ''; ?>>
                <span class="ml-3 text-gray-700 font-medium">Barras Horizontales</span>
            </label>
            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                <input type="radio" name="chart_type" value="pie" class="h-5 w-5 text-indigo-600" <?php echo (CHART_TYPE === 'pie') ? 'checked' : ''; ?>>
                <span class="ml-3 text-gray-700 font-medium">Circular (Pastel)</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-8">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                Guardar Configuración
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>