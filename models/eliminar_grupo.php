<?php
require_once '../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM grupo WHERE pk_id_grupo = ?");
        $stmt->execute([$id]);
        header("Location: ../views/grupos_lista.php?msj=eliminado");
    } catch (Exception $e) {
        header("Location: ../views/grupos_lista.php?msj=error_integridad");
    }
    exit();
}