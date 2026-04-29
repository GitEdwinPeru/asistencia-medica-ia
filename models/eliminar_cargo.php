<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM cargo WHERE pk_id_cargo = ?");
        $stmt->execute([$id]);
        header("Location: ../views/cargos_lista.php?msj=eliminado");
    } catch (PDOException $e) {
        // Error 1451 es comúnmente restricción de clave foránea (empleados asignados)
        header("Location: ../views/cargos_lista.php?msj=error_integridad");
    }
    exit();
}