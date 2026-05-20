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

$id = (int) ($_POST['id'] ?? 0);
$clave = $_POST['clave'] ?? '';

if ($id <= 0 || strlen($clave) < 8) {
    header("Location: ../views/usuarios_lista.php?status=weak_password");
    exit;
}

try {
    $hash = password_hash($clave, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE login SET clave = ? WHERE pk_id_login = ?");
    $stmt->execute([$hash, $id]);
    Logger::log("Password reiniciado para usuario ID $id por " . $_SESSION['admin_id'], "AUDIT");
    auditEvent($pdo, 'RESET_PASSWORD', 'login', (string) $id, 'Reinicio de contrasena por administrador');
    header("Location: ../views/usuarios_lista.php?status=password_reset");
} catch (PDOException $e) {
    Logger::error("Error al reiniciar password: " . $e->getMessage());
    header("Location: ../views/usuarios_lista.php?status=error");
}
exit;
?>
