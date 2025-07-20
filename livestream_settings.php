<?php
// ---- ARCHIVO: livestream_settings.php ----
$page_title = 'Ajustes de Transmisión';
require_once __DIR__ . '/includes/auth_check.php';
require_login(['admin', 'superadmin']);
require_once __DIR__ . '/includes/header.php';

// Función para extraer el ID de un video de YouTube
function getYouTubeVideoId($url) {
    if (empty($url)) return "''"; // Devuelve comillas vacías para el archivo de config
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    if (preg_match($pattern, $url, $match)) {
        return "'" . $match[1] . "'"; // Devuelve el ID entre comillas
    }
    return "''";
}

$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $youtube_url = trim($_POST['youtube_url']);
    $video_id_for_config = getYouTubeVideoId($youtube_url);

    // Crear el contenido del nuevo archivo de configuración
    $config_content = "<?php\n\n// Este archivo se genera automáticamente desde los ajustes de transmisión.\ndefine('YOUTUBE_VIDEO_ID', " . $video_id_for_config . ");\n";

    // Sobrescribir el archivo de configuración
    if (file_put_contents('livestream_config.php', $config_content) !== false) {
        $success_message = '¡La configuración de la transmisión se ha guardado correctamente!';
    } else {
        $errors[] = 'Error: No se pudo escribir en el archivo de configuración. Verifica los permisos.';
    }
}

// Cargar la configuración actual para mostrarla en el formulario
require_once __DIR__ . '/livestream_config.php';
$current_youtube_url = !empty(YOUTUBE_VIDEO_ID) ? 'https://www.youtube.com/watch?v=' . YOUTUBE_VIDEO_ID : '';
?>

<div class="p-8 bg-white rounded-lg shadow max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Configuración de la Transmisión en Vivo</h2>

    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
         <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <p class="text-gray-600 mb-4">
        Introduce aquí el enlace completo de la transmisión de YouTube. Esta transmisión será visible para todos los votantes conectados. Para desactivarla, simplemente deja el campo en blanco y guarda los cambios.
    </p>

    <form action="livestream_settings.php" method="POST">
        <div class="mb-4">
            <label for="youtube_url" class="block text-gray-700 text-sm font-bold mb-2">Enlace de Transmisión de YouTube:</label>
            <input type="url" name="youtube_url" id="youtube_url" value="<?php echo htmlspecialchars($current_youtube_url); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="https://www.youtube.com/watch?v=...">
        </div>
        <div class="flex items-center justify-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Guardar Configuración
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
