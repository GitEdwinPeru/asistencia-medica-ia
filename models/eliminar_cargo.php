<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/cargos_lista.php?msj=error");
    exit;
}

requerirCSRF($_POST['csrf_token'] ?? '');

$id = intval($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empleado WHERE id_cargo = ? AND esta_empl = 1");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            header("Location: ../views/cargos_lista.php?msj=error_integridad");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE cargo SET esta_carg = 0 WHERE pk_id_cargo = ?");
        $stmt->execute([$id]);
        auditEvent($pdo, 'eliminar', 'cargo', (string)$id, 'Baja logica de cargo');
        header("Location: ../views/cargos_lista.php?msj=eliminado");
    } catch (PDOException $e) {
        header("Location: ../views/cargos_lista.php?msj=error_integridad");
    }
} else {
    header("Location: ../views/cargos_lista.php?msj=error");
}
exit;
?>
