<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/response.php';

$dni = preg_replace('/\D/', '', $_GET['dni'] ?? '');
$excluir = (int) ($_GET['excluir'] ?? 0);

if ($dni === '' || strlen($dni) !== 8) {
    jsonResponse(['exists' => false, 'valid' => false, 'message' => 'DNI incompleto.']);
}

$sql = "SELECT pk_id_empleado, nomb_empl, apat_empl FROM empleado WHERE dni_empl = ?";
$params = [$dni];

if ($excluir > 0) {
    $sql .= " AND pk_id_empleado <> ?";
    $params[] = $excluir;
}

$sql .= " LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$empleado = $stmt->fetch(PDO::FETCH_ASSOC);

jsonResponse([
    'exists' => (bool) $empleado,
    'valid' => true,
    'employee' => $empleado ? [
        'id' => (int) $empleado['pk_id_empleado'],
        'nombre' => trim($empleado['nomb_empl'] . ' ' . $empleado['apat_empl']),
    ] : null,
]);
?>
