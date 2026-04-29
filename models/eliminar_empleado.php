<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $pdo->beginTransaction();

        // 1. Primero eliminamos sus asistencias (evita error de integridad)
        $sqlAsis = "DELETE FROM asistencia WHERE id_empleado = ?";
        $stmtAsis = $pdo->prepare($sqlAsis);
        $stmtAsis->execute([$id]);

        // 2. Ahora sí eliminamos al empleado definitivamente
        $sqlEmp = "DELETE FROM empleado WHERE pk_id_empleado = ?";
        $stmtEmp = $pdo->prepare($sqlEmp);
        $stmtEmp->execute([$id]);

        $pdo->commit();
        header("Location: ../views/empleados_lista.php?msg=eliminado_total");
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "Error al eliminar: " . $e->getMessage();
    }
}