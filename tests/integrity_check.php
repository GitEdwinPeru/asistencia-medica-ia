<?php
/**
 * Test de Integridad del Sistema de Asistencia Facial
 * Este script verifica que los módulos críticos estén operativos.
 */

header('Content-Type: text/plain; charset=utf-8');
echo "=== TEST DE INTEGRIDAD DEL SISTEMA ===\n\n";

$base_url = "http://localhost/asistencia_facial/"; // Ajustar según entorno

function test_endpoint($url) {
    echo "Probando: $url ... ";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        echo "OK (200)\n";
        return true;
    } else {
        echo "FALLO (Código: $http_code)\n";
        return false;
    }
}

// 1. Verificación de archivos base
$files = [
    '../config/db.php',
    '../config/auth.php',
    '../views/index.php',
    '../views/asistencia_detalle.php'
];

foreach ($files as $file) {
    echo "Verificando archivo físico: $file ... ";
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "EXISTE\n";
    } else {
        echo "NO EXISTE\n";
    }
}

echo "\n--- Fin del Test ---\n";
?>
