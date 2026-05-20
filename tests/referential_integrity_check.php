<?php
require_once __DIR__ . '/../config/db.php';

$checks = [
    'Empleados con cargo inexistente' => "SELECT COUNT(*) FROM empleado e LEFT JOIN cargo c ON e.id_cargo = c.pk_id_cargo WHERE e.id_cargo IS NOT NULL AND c.pk_id_cargo IS NULL",
    'Empleados con grupo inexistente' => "SELECT COUNT(*) FROM empleado e LEFT JOIN grupo g ON e.id_grupo = g.pk_id_grupo WHERE e.id_grupo IS NOT NULL AND g.pk_id_grupo IS NULL",
    'Empleados con sede inexistente' => "SELECT COUNT(*) FROM empleado e LEFT JOIN distrito d ON e.id_distrito = d.pk_id_distrito WHERE e.id_distrito IS NOT NULL AND d.pk_id_distrito IS NULL",
    'Asistencias con empleado inexistente' => "SELECT COUNT(*) FROM asistencia a LEFT JOIN empleado e ON a.id_empleado = e.pk_id_empleado WHERE a.id_empleado IS NOT NULL AND e.pk_id_empleado IS NULL",
    'Asistencias con sede inexistente' => "SELECT COUNT(*) FROM asistencia a LEFT JOIN distrito d ON a.id_distrito = d.pk_id_distrito WHERE a.id_distrito IS NOT NULL AND d.pk_id_distrito IS NULL",
];

echo "=== REVISION DE INTEGRIDAD REFERENCIAL ===" . PHP_EOL;
$errores = 0;
foreach ($checks as $label => $sql) {
    $count = (int) $pdo->query($sql)->fetchColumn();
    echo ($count === 0 ? '[OK] ' : '[FAIL] ') . "$label: $count" . PHP_EOL;
    if ($count > 0) $errores++;
}

if ($errores > 0) {
    exit(1);
}

echo "Resultado: INTEGRIDAD REFERENCIAL OK" . PHP_EOL;
?>
