<?php
header('Content-Type: application/json');
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';

try {
    // Consulta para obtener las últimas 5 asistencias con el nombre del empleado
    $sql = "SELECT a.fech_ingr, a.horas_tard, e.nomb_empl, e.apat_empl 
            FROM asistencia a
            INNER JOIN empleado e ON a.id_empleado = e.pk_id_empleado
            ORDER BY a.fech_ingr DESC 
            LIMIT 5";
    
    $stmt = $pdo->query($sql);
    $recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $recientes
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}