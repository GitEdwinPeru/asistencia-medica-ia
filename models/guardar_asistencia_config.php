<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/asistencia_config.php?msg=error");
    exit;
}

requerirCSRF($_POST['csrf_token'] ?? '');

$horaEntrada = $_POST['hora_entrada'] ?? '';
$tolerancia = (int) ($_POST['tolerancia_minutos'] ?? 0);

if (!preg_match('/^\d{2}:\d{2}$/', $horaEntrada) || $tolerancia < 0 || $tolerancia > 240) {
    header("Location: ../views/asistencia_config.php?msg=error");
    exit;
}

$horaEntrada .= ':00';

try {
    $pdo->beginTransaction();
    $pdo->exec("UPDATE asistencia_config SET activo = 0");
    $stmt = $pdo->prepare("INSERT INTO asistencia_config (id_distrito, hora_entrada, tolerancia_minutos, activo) VALUES (NULL, ?, ?, 1)");
    $stmt->execute([$horaEntrada, $tolerancia]);
    $pdo->commit();
    Logger::log("Configuracion de asistencia actualizada: $horaEntrada tolerancia $tolerancia", "AUDIT");
    auditEvent($pdo, 'CONFIGURAR', 'asistencia_config', null, "Hora $horaEntrada tolerancia $tolerancia");
    header("Location: ../views/asistencia_config.php?msg=guardado");
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    Logger::error("Error al guardar configuracion de asistencia: " . $e->getMessage());
    header("Location: ../views/asistencia_config.php?msg=error");
}
exit;
?>
