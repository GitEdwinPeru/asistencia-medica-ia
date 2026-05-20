<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/sedes_lista.php?msj=error");
    exit;
}

requerirCSRF($_POST['csrf_token'] ?? '');

$id = intval($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empleado WHERE id_distrito = ? AND esta_empl = 1");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            header("Location: ../views/sedes_lista.php?msj=error_integridad");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE distrito SET esta_dist = 0 WHERE pk_id_distrito = ?");
        $stmt->execute([$id]);
        auditEvent($pdo, 'eliminar', 'sede', (string)$id, 'Baja logica de sede');
        header("Location: ../views/sedes_lista.php?msj=eliminado");
    } catch (Exception $e) {
        header("Location: ../views/sedes_lista.php?msj=error_integridad");
    }
} else {
    header("Location: ../views/sedes_lista.php?msj=error");
}
exit;
?>
