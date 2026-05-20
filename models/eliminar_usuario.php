<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/usuarios_lista.php?status=error");
    exit;
}

requerirCSRF($_POST['csrf_token'] ?? '');

$id = intval($_POST['id'] ?? 0);

if ($id > 0) {
    if ($id === intval($_SESSION['admin_id'])) {
        header("Location: ../views/usuarios_lista.php?status=self_delete_error");
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE login SET esta_login = 0 WHERE pk_id_login = ?");
        $stmt->execute([$id]);
        Logger::log("Usuario ID $id desactivado por " . $_SESSION['admin_id'], "AUDIT");
        auditEvent($pdo, 'ELIMINAR', 'login', (string) $id, 'Usuario desactivado');
        header("Location: ../views/usuarios_lista.php?status=deleted");
    } catch (PDOException $e) {
        Logger::error("Error al eliminar usuario: " . $e->getMessage());
        header("Location: ../views/usuarios_lista.php?status=error");
    }
}
exit;
?>
