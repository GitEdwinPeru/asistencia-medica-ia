<?php
header('Content-Type: application/json');
require_once '../config/db.php';

try {
    // Total de empleados
    $countEmpl = $pdo->query("SELECT COUNT(*) FROM empleado")->fetchColumn();
    
    // Asistencias de hoy
    $hoy = date('Y-m-d');
    $asistenciasHoy = $pdo->prepare("SELECT COUNT(*) FROM asistencia WHERE DATE(fech_ingr) = ?");
    $asistenciasHoy->execute([$hoy]);
    $countAsist = $asistenciasHoy->fetchColumn();

    // Datos para el gráfico (últimos 7 días)
    $stmt = $pdo->query("SELECT DATE(fech_ingr) as fecha, COUNT(*) as total 
                         FROM asistencia 
                         GROUP BY DATE(fech_ingr) 
                         ORDER BY fecha DESC LIMIT 7");
    $grafico = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'totales' => [
            'empleados' => $countEmpl,
            'asistencias' => $countAsist
        ],
        'grafico' => array_reverse($grafico)
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}