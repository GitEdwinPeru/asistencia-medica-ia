<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        // En lugar de eliminar, cambiamos el estado a 0 (Inactivo)
        $sqlEmp = "UPDATE empleado SET esta_empl = 0 WHERE pk_id_empleado = ?";
        $stmtEmp = $pdo->prepare($sqlEmp);
        $stmtEmp->execute([$id]);

        header("Location: ../views/empleados_lista.php?msg=desactivado");
    } catch (PDOException $e) {
        echo "Error al desactivar colaborador: " . $e->getMessage();
    }
}