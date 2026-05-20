<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/validators.php';
require_once '../config/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/usuarios_lista.php?status=error");
    exit;
}

requerirCSRF($_POST['csrf_token'] ?? '');

$id_empleado = !empty($_POST['id_empleado']) ? intval($_POST['id_empleado']) : null;
$usuario = textoLimpio($_POST['usuario'] ?? '', 50);
$clavePlano = $_POST['clave'] ?? '';
$perfil = $_POST['perfil'] ?? '';

if ($usuario === '' || strlen($clavePlano) < 8 || !in_array($perfil, ['Administrador', 'RRHH', 'Supervisor', 'Visualizador'], true)) {
    header("Location: ../views/usuarios_lista.php?status=error");
    exit;
}

$clave = password_hash($clavePlano, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("SELECT pk_id_login, esta_login FROM login WHERE usuario = ? LIMIT 1");
    $stmt->execute([$usuario]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existente && (int) $existente['esta_login'] === 0) {
        $stmt = $pdo->prepare("UPDATE login SET id_empleado = ?, clave = ?, perfil = ?, esta_login = 1 WHERE pk_id_login = ?");
        $stmt->execute([$id_empleado, $clave, $perfil, $existente['pk_id_login']]);
        $idLogin = (string) $existente['pk_id_login'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO login (id_empleado, usuario, clave, perfil) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_empleado, $usuario, $clave, $perfil]);
        $idLogin = (string) $pdo->lastInsertId();
    }

    Logger::log("Nuevo usuario creado: '$usuario' por " . $_SESSION['admin_id'], "AUDIT");
    auditEvent($pdo, 'CREAR', 'login', $idLogin, "Usuario creado/reactivado: $usuario ($perfil)");
    header("Location: ../views/usuarios_lista.php?status=success");
} catch (PDOException $e) {
    Logger::error("Error al crear usuario: " . $e->getMessage());
    header("Location: ../views/usuarios_lista.php?status=error");
}
exit;
?>
