<?php
function guardarImagenSubida(array $file, string $prefix, string $uploadDir = '../uploads/fotos/'): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo recibir la imagen.');
    }

    $maxBytes = 2 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('La imagen no debe superar 2 MB.');
    }

    $tmpName = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpName)) {
        throw new RuntimeException('Archivo de imagen invalido.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Solo se permiten imagenes JPG, PNG o WEBP.');
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new RuntimeException('No se pudo preparar la carpeta de imagenes.');
    }

    $safePrefix = preg_replace('/[^A-Za-z0-9_-]/', '_', $prefix) ?: 'foto';
    $filename = $safePrefix . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $destination = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    return $filename;
}
?>
