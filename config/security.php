<?php
/**
 * Seguridad para datos sensibles en la Hoja de Vida
 */

define('ENCRYPTION_KEY', 'AMFURI_SECRET_KEY_2026'); // Cambiar por una clave más robusta en producción
define('ENCRYPTION_METHOD', 'AES-256-CBC');

function encriptarDato($dato) {
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = openssl_random_pseudo_bytes($iv_length);
    $encrypted = openssl_encrypt($dato, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

function desencriptarDato($dato_encriptado) {
    list($encrypted_data, $iv) = explode('::', base64_decode($dato_encriptado), 2);
    return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}
?>
