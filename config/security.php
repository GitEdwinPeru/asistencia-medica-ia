<?php
require_once __DIR__ . '/app.php';

define('ENCRYPTION_METHOD_LEGACY', 'AES-256-CBC');
define('ENCRYPTION_METHOD_CURRENT', 'aes-256-gcm');

function obtenerClaveCifrado(): string {
    $configured = envValue('APP_KEY', 'AMFURI_SECRET_KEY_2026');
    return hash('sha256', $configured, true);
}

function encriptarDato($dato) {
    $iv = random_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt((string) $dato, ENCRYPTION_METHOD_CURRENT, obtenerClaveCifrado(), OPENSSL_RAW_DATA, $iv, $tag);

    if ($encrypted === false) {
        throw new RuntimeException('No se pudo cifrar el dato sensible.');
    }

    return 'v2:' . base64_encode(json_encode([
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag),
        'data' => base64_encode($encrypted),
    ]));
}

function desencriptarDato($dato_encriptado) {
    if (str_starts_with((string) $dato_encriptado, 'v2:')) {
        $payload = json_decode(base64_decode(substr($dato_encriptado, 3)), true);
        if (!is_array($payload)) return '';

        return openssl_decrypt(
            base64_decode($payload['data'] ?? ''),
            ENCRYPTION_METHOD_CURRENT,
            obtenerClaveCifrado(),
            OPENSSL_RAW_DATA,
            base64_decode($payload['iv'] ?? ''),
            base64_decode($payload['tag'] ?? '')
        ) ?: '';
    }

    $decoded = base64_decode((string) $dato_encriptado);
    if (!str_contains((string) $decoded, '::')) return '';

    [$encrypted_data, $iv] = explode('::', $decoded, 2);
    return openssl_decrypt($encrypted_data, ENCRYPTION_METHOD_LEGACY, 'AMFURI_SECRET_KEY_2026', 0, $iv) ?: '';
}
?>
