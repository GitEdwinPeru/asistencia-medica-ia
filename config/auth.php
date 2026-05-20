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
require_once 'response.php';

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

function requerirCSRF(?string $token = null, bool $json = false): void {
    $token = $token ?? ($_POST['csrf_token'] ?? '');

    if (!validarTokenCSRF((string) $token)) {
        if ($json) {
            jsonResponse(['status' => 'error', 'message' => 'Error de seguridad: Token CSRF invalido.'], 403);
        }

        http_response_code(403);
        die('Error de seguridad: Token CSRF invalido.');
    }
}

function obtenerTokenAsistencia(): string {
    if (empty($_SESSION['attendance_token'])) {
        $_SESSION['attendance_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['attendance_token'];
}

function validarTokenAsistencia(string $token): bool {
    return isset($_SESSION['attendance_token']) && hash_equals($_SESSION['attendance_token'], $token);
}

function requerirTokenAsistencia(?string $token = null): void {
    $token = $token ?? ($_SERVER['HTTP_X_ATTENDANCE_TOKEN'] ?? $_POST['attendance_token'] ?? '');

    if (!validarTokenAsistencia((string) $token)) {
        jsonResponse(['status' => 'error', 'message' => 'Token de asistencia invalido. Recargue la pagina.'], 403);
    }
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

function perfilActual(): string {
    return $_SESSION['perfil'] ?? '';
}

function permisosPorPerfil(): array {
    return [
        'Administrador' => ['dashboard', 'asistencia', 'empleados', 'hoja_vida', 'catalogos', 'usuarios', 'auditoria', 'configuracion'],
        'RRHH' => ['dashboard', 'asistencia', 'empleados', 'hoja_vida'],
        'Supervisor' => ['dashboard', 'asistencia', 'empleados'],
        'Visualizador' => ['dashboard', 'asistencia'],
    ];
}

function tienePermiso(string $permiso): bool {
    $perfil = perfilActual();
    $permisos = permisosPorPerfil();
    return in_array($permiso, $permisos[$perfil] ?? [], true);
}

function requerirPermiso(string $permiso): void {
    verificarSesion();
    if (!tienePermiso($permiso)) {
        header("Location: dashboard.php?msg=sin_permiso");
        exit();
    }
}

/**
 * Restringe el acceso solo a administradores
 */
function restringirSoloAdmin() {
    verificarSesion();
    if (!esAdministrador()) {
        header("Location: dashboard.php?msg=sin_permiso");
        exit();
    }
}
?>
