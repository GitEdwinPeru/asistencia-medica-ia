<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/logger.php';

try {
    $stmt = $pdo->query("SELECT pk_id_distrito as id, nomb_dist as nombre FROM distrito ORDER BY nomb_dist ASC");
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($sedes);
} catch (PDOException $e) {
    Logger::error("Error al obtener sedes para selector: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'No se pudieron cargar las sedes']);
}
?>
