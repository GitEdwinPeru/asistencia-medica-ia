<?php
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once '../config/auth.php';
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/response.php';
require_once '../config/rate_limit.php';

date_default_timezone_set('America/Lima');

function distanciaEuclidiana(array $a, array $b): float {
    if (count($a) !== count($b)) {
        return INF;
    }

    $sum = 0.0;
    foreach ($a as $index => $value) {
        $diff = (float) $value - (float) $b[$index];
        $sum += $diff * $diff;
    }

    return sqrt($sum);
}

function obtenerHoraEntrada(PDO $pdo, int $idDistrito): string {
    $stmt = $pdo->prepare("SELECT hora_entrada, tolerancia_minutos FROM asistencia_config WHERE id_distrito = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$idDistrito]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        $stmt = $pdo->query("SELECT hora_entrada, tolerancia_minutos FROM asistencia_config WHERE id_distrito IS NULL AND activo = 1 LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $horaBase = $config['hora_entrada'] ?? '08:15:00';
    $tolerancia = intval($config['tolerancia_minutos'] ?? 0);

    return date('H:i:s', strtotime($horaBase . " +$tolerancia minutes"));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Metodo no permitido'], 405);
}

requerirTokenAsistencia();
requerirRateLimit('registrar_asistencia', 8, 60);

$idEmpleadoCliente = intval($_POST['id_empleado'] ?? 0);
$tipoRegistro = $_POST['tipo_registro'] ?? '';
$idDistrito = intval($_POST['id_distrito'] ?? 0);
$descriptorJson = $_POST['descriptor'] ?? '';

if (!$idEmpleadoCliente || !in_array($tipoRegistro, ['entrada', 'salida'], true) || !$idDistrito || $descriptorJson === '') {
    jsonResponse(['status' => 'error', 'message' => 'Datos incompletos para el registro'], 422);
}

$descriptor = json_decode($descriptorJson, true);
if (!is_array($descriptor) || count($descriptor) !== 128) {
    jsonResponse(['status' => 'error', 'message' => 'Descriptor facial invalido'], 422);
}

try {
    $stmtSede = $pdo->prepare("SELECT pk_id_distrito FROM distrito WHERE pk_id_distrito = ?");
    $stmtSede->execute([$idDistrito]);
    if (!$stmtSede->fetch()) {
        jsonResponse(['status' => 'error', 'message' => 'Sede no valida'], 422);
    }

    $stmt = $pdo->query("SELECT pk_id_empleado, nomb_empl, apat_empl, rostro_embedding FROM empleado WHERE esta_empl = 1 AND rostro_embedding IS NOT NULL");
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mejorEmpleado = null;
    $mejorDistancia = INF;
    foreach ($empleados as $empleado) {
        $embedding = json_decode($empleado['rostro_embedding'], true);
        if (!is_array($embedding)) {
            continue;
        }

        $distancia = distanciaEuclidiana($descriptor, $embedding);
        if ($distancia < $mejorDistancia) {
            $mejorDistancia = $distancia;
            $mejorEmpleado = $empleado;
        }
    }

    $umbral = 0.6;
    if (!$mejorEmpleado || $mejorDistancia > $umbral || intval($mejorEmpleado['pk_id_empleado']) !== $idEmpleadoCliente) {
        Logger::security("Marcacion rechazada por verificacion facial. Cliente ID $idEmpleadoCliente, distancia $mejorDistancia.");
        jsonResponse(['status' => 'error', 'message' => 'No se pudo verificar la identidad facial'], 403);
    }

    $idEmpleado = intval($mejorEmpleado['pk_id_empleado']);
    $ahora = date('Y-m-d H:i:s');
    $hoy = date('Y-m-d');
    $horaActual = date('H:i:s');

    if ($tipoRegistro === 'entrada') {
        $stmtCheck = $pdo->prepare("SELECT id_asistencia FROM asistencia WHERE id_empleado = ? AND DATE(fech_ingr) = ? LIMIT 1");
        $stmtCheck->execute([$idEmpleado, $hoy]);

        if ($stmtCheck->fetch()) {
            jsonResponse(['status' => 'error', 'message' => 'Ya registraste tu entrada el dia de hoy']);
        }

        $horaEntradaOficial = obtenerHoraEntrada($pdo, $idDistrito);
        $esTardanza = ($horaActual > $horaEntradaOficial);
        $tardanza = '00:00:00';

        if ($esTardanza) {
            $diff = (new DateTime($horaEntradaOficial))->diff(new DateTime($horaActual));
            $tardanza = $diff->format('%H:%I:%S');
        }

        $sqlInsert = "INSERT INTO asistencia (id_empleado, id_distrito, fech_ingr, horas_tard) VALUES (?, ?, ?, ?)";
        $pdo->prepare($sqlInsert)->execute([$idEmpleado, $idDistrito, $ahora, $tardanza]);

        Logger::log("Entrada registrada: Empleado {$mejorEmpleado['nomb_empl']} {$mejorEmpleado['apat_empl']} (ID $idEmpleado) en Sede ID $idDistrito", "AUDIT");

        jsonResponse([
            'status' => 'success',
            'message' => $esTardanza ? "TARDANZA REGISTRADA ($tardanza)" : 'ENTRADA PUNTUAL REGISTRADA',
            'detalle' => "Hora: $horaActual"
        ]);
    }

    $stmtFind = $pdo->prepare("SELECT id_asistencia, fech_ingr, id_distrito FROM asistencia WHERE id_empleado = ? AND DATE(fech_ingr) = ? AND fech_sali IS NULL ORDER BY id_asistencia DESC LIMIT 1");
    $stmtFind->execute([$idEmpleado, $hoy]);
    $asistencia = $stmtFind->fetch(PDO::FETCH_ASSOC);

    if (!$asistencia) {
        jsonResponse(['status' => 'error', 'message' => 'No se encontro una entrada abierta para hoy']);
    }

    $fechIngr = new DateTime($asistencia['fech_ingr']);
    $fechSali = new DateTime($ahora);
    $horasTrabajadas = $fechIngr->diff($fechSali)->format('%H:%I:%S');

    $sqlUpdate = "UPDATE asistencia SET fech_sali = ?, horas_trab = ? WHERE id_asistencia = ?";
    $pdo->prepare($sqlUpdate)->execute([$ahora, $horasTrabajadas, $asistencia['id_asistencia']]);

    Logger::log("Salida registrada: Empleado {$mejorEmpleado['nomb_empl']} {$mejorEmpleado['apat_empl']} (ID $idEmpleado) en Sede ID {$asistencia['id_distrito']}", "AUDIT");

    jsonResponse([
        'status' => 'success',
        'message' => 'SALIDA REGISTRADA',
        'detalle' => "Horas cumplidas: $horasTrabajadas"
    ]);
} catch (PDOException $e) {
    Logger::error("Error en registro de asistencia para Empleado ID $idEmpleadoCliente: " . $e->getMessage());
    jsonResponse(['status' => 'error', 'message' => 'Error interno del servidor'], 500);
}
?>
