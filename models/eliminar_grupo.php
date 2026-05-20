<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/grupos_lista.php?msj=error");
    exit;
}

requerirCSRF($_POST['csrf_token'] ?? '');

$id = intval($_POST['id'] ?? 0);
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM empleado WHERE id_grupo = ? AND esta_empl = 1");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            header("Location: ../views/grupos_lista.php?msj=error_integridad");
            exit;
        }

        $stmt = $pdo->prepare("UPDATE grupo SET esta_grup = 0 WHERE pk_id_grupo = ?");
        $stmt->execute([$id]);
        auditEvent($pdo, 'eliminar', 'grupo', (string)$id, 'Baja logica de grupo');
        header("Location: ../views/grupos_lista.php?msj=eliminado");
    } catch (Exception $e) {
        header("Location: ../views/grupos_lista.php?msj=error_integridad");
    }
} else {
    header("Location: ../views/grupos_lista.php?msj=error");
}
exit;
?>
