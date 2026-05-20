<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST DE INTEGRIDAD DEL SISTEMA ===\n\n";

$root = dirname(__DIR__);
$ok = true;

function assert_check(bool $condition, string $message): void {
    global $ok;
    echo ($condition ? "[OK] " : "[FALLO] ") . $message . "\n";
    if (!$condition) $ok = false;
}

$files = [
    'index.php',
    'config/db.php',
    'config/auth.php',
    'config/response.php',
    'config/upload.php',
    'models/registrar_asistencia.php',
    'models/obtener_empleados_fotos.php',
    'views/asistencia_detalle.php',
    'assets/js/camara.js',
    'assets/models/tiny_face_detector_model-weights_manifest.json',
];

foreach ($files as $file) {
    assert_check(file_exists($root . '/' . $file), "Archivo requerido: $file");
}

require_once $root . '/config/db.php';

$requiredTables = [
    'empleado',
    'asistencia',
    'login',
    'login_attempts',
    'asistencia_config',
    'auditoria_eventos',
];

foreach ($requiredTables as $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    assert_check((bool) $stmt->fetchColumn(), "Tabla requerida: $table");
}

$empleadosConRostro = (int) $pdo->query("SELECT COUNT(*) FROM empleado WHERE esta_empl = 1 AND rostro_embedding IS NOT NULL")->fetchColumn();
assert_check($empleadosConRostro > 0, "Existe al menos un empleado activo con descriptor facial");

echo "\nResultado: " . ($ok ? "SISTEMA BASE OK" : "REVISAR FALLOS") . "\n";
exit($ok ? 0 : 1);
?>
