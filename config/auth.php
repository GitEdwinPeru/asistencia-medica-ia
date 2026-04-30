<?php
// Configuración de cookies de sesión antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once 'security_headers.php';

/**
 * Función para generar un token CSRF
 */
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Función para validar el token CSRF
 */
function validarTokenCSRF(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Función para verificar si el usuario tiene una sesión activa
 */
function verificarSesion() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Función para verificar si el usuario tiene el perfil de Administrador
 */
function esAdministrador() {
    return isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'Administrador';
}

/**
 * Restringe el acceso solo a administradores
 */
function restringirSoloAdmin() {
    verificarSesion();
    if (!esAdministrador()) {
        echo "<script>
            alert('Acceso denegado. Se requiere perfil de Administrador.');
            window.location.href = 'dashboard.php';
        </script>";
        exit();
    }
}
?>
