<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/logger.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_empleado = !empty($_POST['id_empleado']) ? intval($_POST['id_empleado']) : null;
    $usuario = trim($_POST['usuario']);
    $clave = password_hash($_POST['clave'], PASSWORD_BCRYPT);
    $perfil = $_POST['perfil'];

    try {
        $stmt = $pdo->prepare("INSERT INTO login (id_empleado, usuario, clave, perfil) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_empleado, $usuario, $clave, $perfil]);
        
        Logger::log("Nuevo usuario administrativo creado: '$usuario' por " . $_SESSION['admin_id']);
        header("Location: ../views/usuarios_lista.php?status=success");
    } catch (PDOException $e) {
        Logger::error("Error al crear usuario: " . $e->getMessage());
        header("Location: ../views/usuarios_lista.php?status=error");
    }
}
?>
