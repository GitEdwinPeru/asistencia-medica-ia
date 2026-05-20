<?php
$baseDir = realpath(__DIR__ . '/../uploads/fotos');
$maxBytes = 2 * 1024 * 1024;
$allowed = ['image/jpeg', 'image/png', 'image/webp'];

echo "=== AUDITORIA DE ARCHIVOS SUBIDOS ===" . PHP_EOL;
if (!$baseDir || !is_dir($baseDir)) {
    echo "[FAIL] No existe uploads/fotos" . PHP_EOL;
    exit(1);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$errors = 0;
$checked = 0;

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)) as $file) {
    if (!$file->isFile() || $file->getFilename() === '.htaccess') continue;

    $checked++;
    $mime = $finfo->file($file->getPathname());
    $size = $file->getSize();
    $ok = in_array($mime, $allowed, true) && $size <= $maxBytes;
    echo ($ok ? '[OK] ' : '[FAIL] ') . $file->getFilename() . " | $mime | $size bytes" . PHP_EOL;
    if (!$ok) $errors++;
}

echo "Archivos revisados: $checked" . PHP_EOL;
if ($errors > 0) {
    echo "Resultado: REVISAR ARCHIVOS SUBIDOS" . PHP_EOL;
    exit(1);
}

echo "Resultado: UPLOADS OK" . PHP_EOL;
?>
