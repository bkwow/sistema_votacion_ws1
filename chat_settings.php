<?php // ---- ARCHIVO: chat_settings.php (NUEVO Y CORREGIDO) ----
$page_title = 'Ajustes del Chat';
require_once __DIR__ . '/includes/auth_check.php';
require_login(['admin', 'superadmin']);
require_once __DIR__ . '/includes/header.php';

$success_message = '';
$errors = [];
$config_changed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chat_enabled_value = isset($_POST['chat_enabled']) ? 'true' : 'false';
    $save_chat_enabled_value = isset($_POST['save_chat_enabled']) ? 'true' : 'false';

    $config_content = "<?php\n// Archivo generado automáticamente desde los ajustes.\n\n";
    $config_content .= "define('CHAT_ENABLED', " . $chat_enabled_value . ");\n";
    $config_content .= "define('SAVE_CHAT_ENABLED', " . $save_chat_enabled_value . ");\n";

    if (file_put_contents('chat_config.php', $config_content) !== false) {
        $success_message = '¡La configuración del chat se ha guardado correctamente!';
        $config_changed = true;
    } else {
        $errors[] = 'Error: No se pudo escribir en el archivo de configuración.';
    }
}

require_once __DIR__ . '/chat_config.php';
?>

<div class="p-8 bg-white rounded-lg shadow max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Configuración del Chat Global</h2>

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

    <form action="chat_settings.php" method="POST">
        <div class="space-y-6">
            <div class="bg-gray-50 p-4 rounded-lg border">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="chat_enabled" id="chat_enabled_checkbox" class="form-checkbox h-6 w-6 text-indigo-600 rounded" <?php echo CHAT_ENABLED ? 'checked' : ''; ?>>
                    <span class="ml-3 text-lg font-medium text-gray-800">Activar Chat</span>
                </label>
                <p class="ml-9 text-gray-600 text-sm">Si está desactivado, el chat desaparecerá para todos los usuarios en tiempo real.</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg border">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="save_chat_enabled" class="form-checkbox h-6 w-6 text-indigo-600 rounded" <?php echo SAVE_CHAT_ENABLED ? 'checked' : ''; ?>>
                    <span class="ml-3 text-lg font-medium text-gray-800">Guardar Historial del Chat</span>
                </label>
                <p class="ml-9 text-gray-600 text-sm">Activa o desactiva el guardado de mensajes en la base de datos.</p>
            </div>
        </div>

        <div class="flex items-center justify-end mt-8">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                Guardar Configuración
            </button>
        </div>
    </form>
</div>

<?php if ($config_changed): ?>
<script>
    // Este script se ejecuta solo si la configuración se guardó correctamente.
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Enviando actualización de configuración de chat en tiempo real...');
        const ws = new WebSocket("<?php echo WEBSOCKET_SERVER; ?>");
        
        ws.onopen = function() {
            const chatEnabled = document.getElementById('chat_enabled_checkbox').checked;
            
            ws.send(JSON.stringify({
                action: 'update_chat_config',
                chat_enabled: chatEnabled
            }));

            // **LA CORRECCIÓN CLAVE:** Se añade una pequeña pausa para asegurar que el mensaje se envíe
            // antes de que la conexión se cierre, evitando la condición de carrera.
            setTimeout(function() {
                console.log('Señal enviada. Cerrando conexión temporal.');
                ws.close();
            }, 500); // 500ms de espera
        };

        ws.onerror = function() {
            console.error('No se pudo conectar al WebSocket para enviar la actualización.');
            alert('No se pudo enviar la señal en tiempo real. Los usuarios deberán refrescar la página para ver los cambios en el chat.');
        };
    });
</script>
<?php endif; ?>