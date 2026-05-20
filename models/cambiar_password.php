<?php
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/audit.php';
require_once '../config/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Metodo no permitido'], 405);
}

requerirCSRF($_POST['csrf_token'] ?? '', true);

$actual = $_POST['clave_actual'] ?? '';
$nueva = $_POST['clave_nueva'] ?? '';
$confirmar = $_POST['clave_confirmar'] ?? '';

if (strlen($nueva) < 8 || $nueva !== $confirmar) {
    jsonResponse(['status' => 'error', 'message' => 'La nueva contrasena debe tener minimo 8 caracteres y coincidir.'], 422);
}

$stmt = $pdo->prepare("SELECT pk_id_login, clave FROM login WHERE pk_id_login = ? LIMIT 1");
$stmt->execute([$_SESSION['admin_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($actual, $usuario['clave'])) {
    jsonResponse(['status' => 'error', 'message' => 'La contrasena actual no es correcta.'], 403);
}

$hash = password_hash($nueva, PASSWORD_BCRYPT);
$pdo->prepare("UPDATE login SET clave = ? WHERE pk_id_login = ?")->execute([$hash, $_SESSION['admin_id']]);
Logger::log("Usuario ID {$_SESSION['admin_id']} cambio su contrasena", "AUDIT");
auditEvent($pdo, 'CAMBIO_PASSWORD', 'login', (string) $_SESSION['admin_id'], 'Cambio de contrasena propio');
jsonResponse(['status' => 'success', 'message' => 'Contrasena actualizada correctamente.']);
?>
