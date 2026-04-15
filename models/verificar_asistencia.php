<?php
// Evita que cualquier advertencia de PHP ensucie la salida JSON
error_reporting(0);
header('Content-Type: application/json');
require_once '../config/db.php'; 

try {
    // Seleccionamos solo a los empleados que ya tienen un rostro capturado
    $stmt = $pdo->prepare("SELECT pk_id_empleado, nomb_empl, rostro_embedding FROM empleado WHERE rostro_embedding IS NOT NULL");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enviamos el JSON y nos aseguramos de que no se ejecute nada más
    echo json_encode($empleados);
    exit;
} catch (PDOException $e) {
    echo json_encode([]);
    exit;
}