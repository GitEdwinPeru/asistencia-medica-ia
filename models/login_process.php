<?php
session_start();
require_once '../config/db.php'; // Usa tu archivo de conexión

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $clave   = trim($_POST['clave']);

    try {
        // Buscamos en la tabla 'login' según tu diagrama
        $stmt = $pdo->prepare("SELECT * FROM login WHERE usuario = :user LIMIT 1");
        $stmt->execute([':user' => $usuario]);
        $user = $stmt->fetch();

        // NOTA: Para producción usa password_verify, aquí asumo texto plano por ahora
        if ($user && $clave === $user['clave']) {
            $_SESSION['admin_id'] = $user['pk_id_login'];
            $_SESSION['perfil']   = $user['perfil'];
            
            header("Location: ../views/dashboard.php");
            exit;
        } else {
            echo "<script>alert('Usuario o clave incorrectos'); window.history.back();</script>";
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}