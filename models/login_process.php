<?php
session_start();
require_once '../config/auth.php';
require_once '../config/db.php';
require_once '../config/logger.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar Token CSRF
    if (!isset($_POST['csrf_token']) || !validarTokenCSRF($_POST['csrf_token'])) {
        Logger::security("Intento de login con Token CSRF inválido.");
        die("Error de seguridad: Token inválido.");
    }

    $ip_address = $_SERVER['REMOTE_ADDR'];
    $max_attempts = 5;
    $lockout_time = 15 * 60; // 15 minutos en segundos

    // 1. Verificar si la IP está bloqueada
    $stmt_limit = $pdo->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
    $stmt_limit->execute([$ip_address]);
    $attempt_data = $stmt_limit->fetch();

    if ($attempt_data) {
        $last_time = strtotime($attempt_data['last_attempt']);
        if ($attempt_data['attempts'] >= $max_attempts && (time() - $last_time) < $lockout_time) {
            $remaining = ceil(($lockout_time - (time() - $last_time)) / 60);
            Logger::security("Bloqueo de IP $ip_address por exceso de intentos.");
            die("Demasiados intentos fallidos. Su IP ha sido bloqueada temporalmente. Intente de nuevo en $remaining minutos.");
        }
    }

    $usuario = trim($_POST['usuario']);
    $clave   = trim($_POST['clave']);

    try {
        // Buscamos en la tabla 'login' según tu diagrama
        $stmt = $pdo->prepare("SELECT * FROM login WHERE usuario = :user LIMIT 1");
        $stmt->execute([':user' => $usuario]);
        $user = $stmt->fetch();

        // Verificación segura con password_verify
        if ($user && password_verify($clave, $user['clave'])) {
            // Éxito: Limpiar intentos fallidos para esta IP
            $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip_address]);

            // Regenerar ID de sesión para prevenir Session Fixation
            session_regenerate_id(true);
            
            $_SESSION['admin_id'] = $user['pk_id_login'];
            $_SESSION['perfil']   = $user['perfil'];
            
            Logger::log("Usuario '$usuario' inició sesión exitosamente.");
            header("Location: ../views/dashboard.php");
            exit;
        } else {
            // Intento fallido: Registrar o actualizar intentos
            if ($attempt_data) {
                // Si el último intento fue hace más de 15 minutos, resetear contador
                if ((time() - strtotime($attempt_data['last_attempt'])) > $lockout_time) {
                    $pdo->prepare("UPDATE login_attempts SET attempts = 1, last_attempt = CURRENT_TIMESTAMP WHERE ip_address = ?")->execute([$ip_address]);
                } else {
                    $pdo->prepare("UPDATE login_attempts SET attempts = attempts + 1, last_attempt = CURRENT_TIMESTAMP WHERE ip_address = ?")->execute([$ip_address]);
                }
            } else {
                $pdo->prepare("INSERT INTO login_attempts (ip_address, attempts) VALUES (?, 1)")->execute([$ip_address]);
            }

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