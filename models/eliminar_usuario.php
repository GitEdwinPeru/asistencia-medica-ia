<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/logger.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    // Evitar que el admin se elimine a sí mismo
    if ($id == $_SESSION['admin_id']) {
        header("Location: ../views/usuarios_lista.php?status=self_delete_error");
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM login WHERE pk_id_login = ?");
        $stmt->execute([$id]);
        Logger::log("Usuario ID $id eliminado por " . $_SESSION['admin_id']);
        header("Location: ../views/usuarios_lista.php?status=deleted");
    } catch (PDOException $e) {
        Logger::error("Error al eliminar usuario: " . $e->getMessage());
        header("Location: ../views/usuarios_lista.php?status=error");
    }
}
?>
