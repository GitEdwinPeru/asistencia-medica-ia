<?php
// Bloqueamos cualquier error visual que rompa el JSON
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
require_once '../config/auth.php';
require_once '../config/response.php';

requerirTokenAsistencia();

// Limpiamos cualquier espacio en blanco o eco previo
if (ob_get_length()) ob_clean();

try {
    require_once '../config/db.php';

    if (!isset($pdo)) {
        throw new Exception("Variable de conexión PDO no encontrada.");
    }

    $stmt = $pdo->query("SELECT pk_id_empleado as id, rostro_embedding FROM empleado WHERE esta_empl = 1 AND rostro_embedding IS NOT NULL");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($empleados ?: [], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    jsonResponse(["status" => "error", "message" => "No se pudo obtener la base facial."], 500);
}
exit;
