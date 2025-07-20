<?php
// Este script te ayudará a crear una contraseña compatible con tu sistema.

$password_plano = 'password';
$hash_generado = password_hash($password_plano, PASSWORD_DEFAULT);

echo '<h1>Generador de Contraseña Segura</h1>';
echo '<p>Usa el siguiente "hash" para actualizar tu base de datos.</p>';
echo '<p><strong>Contraseña:</strong> ' . htmlspecialchars($password_plano) . '</p>';
echo '<textarea rows="3" cols="80" readonly style="font-size: 16px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;" onclick="this.select();">' . htmlspecialchars($hash_generado) . '</textarea>';
echo '<p><small>Puedes borrar este archivo (generar_clave.php) después de usarlo.</small></p>';

?>