<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Seguridad: Solo admin puede registrar
if (!isset($_SESSION['admin_id'])) { header("Location: ../views/index.php"); exit(); }

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomb_grup = trim($_POST['nomb_grup']);

    if (!empty($nomb_grup)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO grupo (nomb_grup) VALUES (?)");
            $result = $stmt->execute([$nomb_grup]);

            if ($result) {
                // Redirigir con mensaje de éxito
                header("Location: ../views/grupos_lista.php?msj=registrado");
            } else {
                header("Location: ../views/grupos_lista.php?msj=error");
            }
        } catch (PDOException $e) {
            // Manejo de errores (ej: nombre duplicado)
            header("Location: ../views/grupos_lista.php?msj=error");
        }
    } else {
        header("Location: ../views/grupos_lista.php?msj=vacio");
    }
    exit();
}