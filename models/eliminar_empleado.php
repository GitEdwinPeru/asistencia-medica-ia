<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/empleados_lista.php?msg=error");
    exit;
}

requerirCSRF($_POST['csrf_token'] ?? '');

$id = intval($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $stmtEmp = $pdo->prepare("UPDATE empleado SET esta_empl = 0 WHERE pk_id_empleado = ?");
        $stmtEmp->execute([$id]);
        Logger::log("Empleado ID $id desactivado por " . $_SESSION['admin_id'], "AUDIT");
        auditEvent($pdo, 'ELIMINAR', 'empleado', (string) $id, 'Empleado desactivado');
        header("Location: ../views/empleados_lista.php?msg=desactivado");
    } catch (PDOException $e) {
        Logger::error("Error al desactivar empleado $id: " . $e->getMessage());
        header("Location: ../views/empleados_lista.php?msg=error");
    }
}
exit;
?>
