<?php
require_once '../config/db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        // Borrado lógico: cambiamos el estado a 0 (Inactivo)
        $sql = "UPDATE empleado SET esta_empl = 0 WHERE pk_id_empleado = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        header("Location: ../views/empleados_lista.php?msg=eliminado");
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}