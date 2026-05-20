<?php
header('Content-Type: application/json');
require_once '../config/auth.php';
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/response.php';

requerirTokenAsistencia();

try {
    $stmt = $pdo->query("SELECT pk_id_distrito as id, nomb_dist as nombre FROM distrito ORDER BY nomb_dist ASC");
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($sedes, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    Logger::error("Error al obtener sedes para selector: " . $e->getMessage());
    jsonResponse(['status' => 'error', 'message' => 'No se pudieron cargar las sedes'], 500);
}
?>
