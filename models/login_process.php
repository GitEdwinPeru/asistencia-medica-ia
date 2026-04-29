<?php
session_start();
require_once '../config/db.php';
require_once '../config/logger.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = trim($_POST['usuario']);
    $clave   = trim($_POST['clave']);

    try {
        // Buscamos en la tabla 'login' según tu diagrama
        $stmt = $pdo->prepare("SELECT * FROM login WHERE usuario = :user LIMIT 1");
        $stmt->execute([':user' => $usuario]);
        $user = $stmt->fetch();

        // Verificación segura con password_verify
        if ($user && password_verify($clave, $user['clave'])) {
            $_SESSION['admin_id'] = $user['pk_id_login'];
            $_SESSION['perfil']   = $user['perfil'];
            
            Logger::log("Usuario '$usuario' inició sesión exitosamente.");
            header("Location: ../views/dashboard.php");
            exit;
        } else {
            // Si no coincide con hash, intentamos verificar texto plano (solo para migración)
            if ($user && $clave === $user['clave']) {
                // Actualizamos a hash automáticamente si era texto plano
                $newHash = password_hash($clave, PASSWORD_BCRYPT);
                $updateStmt = $pdo->prepare("UPDATE login SET clave = ? WHERE pk_id_login = ?");
                $updateStmt->execute([$newHash, $user['pk_id_login']]);

                $_SESSION['admin_id'] = $user['pk_id_login'];
                $_SESSION['perfil']   = $user['perfil'];
                
                Logger::log("Usuario '$usuario' migró contraseña e inició sesión.");
                header("Location: ../views/dashboard.php");
                exit;
            }
            Logger::security("Intento de login fallido para usuario: '$usuario'");
            echo "<script>alert('Usuario o clave incorrectos'); window.history.back();</script>";
        }
    } catch (PDOException $e) {
        Logger::error("Fallo en login: " . $e->getMessage());
        die("Error de sistema.");
    }
}