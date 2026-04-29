<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
