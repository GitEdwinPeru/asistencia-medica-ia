<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/logger.php';
require_once '../config/response.php';
require_once '../config/upload.php';
require_once '../config/validators.php';
require_once '../config/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Metodo no permitido'], 405);
}

requerirCSRF($_POST['csrf_token'] ?? '', true);

$nombre      = textoLimpio($_POST['nombre'] ?? '', 100);
$apat        = textoLimpio($_POST['apellido_pat'] ?? '', 100);
$amat        = textoLimpio($_POST['apellido_mat'] ?? '', 100);
$dni         = trim($_POST['dni'] ?? '');
$fnac        = $_POST['fecha_nac'] ?? null;
$genero      = $_POST['genero'] ?? '';
$telefono    = trim($_POST['telefono'] ?? '');
$email       = textoLimpio($_POST['emai_empl'] ?? '', 150);
$direccion   = textoLimpio($_POST['dire_empl'] ?? '', 255);
$idDistrito  = intval($_POST['id_distrito'] ?? 0);
$idCargo     = intval($_POST['id_cargo'] ?? 0);
$idGrupo     = intval($_POST['id_grupo'] ?? 0);
$observacion = textoLimpio($_POST['obsv_empl'] ?? '', 1000);
$descriptor  = $_POST['descriptor'] ?? null;

if ($nombre === '' || $apat === '' || $dni === '' || empty($descriptor)) {
    jsonResponse(['status' => 'error', 'message' => 'Faltan datos obligatorios o la validacion facial.'], 422);
}

if (!soloDigitos($dni, 8)) {
    jsonResponse(['status' => 'error', 'message' => 'El DNI debe tener exactamente 8 digitos numericos.'], 422);
}

if (!validarFechaOpcional($fnac)) {
    jsonResponse(['status' => 'error', 'message' => 'La fecha de nacimiento no es valida.'], 422);
}

if (!in_array($genero, ['M', 'F'], true)) {
    jsonResponse(['status' => 'error', 'message' => 'El genero seleccionado no es valido.'], 422);
}

if ($telefono !== '' && !soloDigitos($telefono, 9)) {
    jsonResponse(['status' => 'error', 'message' => 'El telefono debe tener exactamente 9 digitos numericos.'], 422);
}

if (!validarEmailOpcional($email)) {
    jsonResponse(['status' => 'error', 'message' => 'El correo electronico no es valido.'], 422);
}

$descriptorValidado = json_decode($descriptor, true);
if (!is_array($descriptorValidado) || count($descriptorValidado) !== 128) {
    jsonResponse(['status' => 'error', 'message' => 'El descriptor facial no es valido.'], 422);
}

function distanciaDescriptor(array $a, array $b): float {
    if (count($a) !== count($b)) return INF;
    $sum = 0.0;
    foreach ($a as $index => $value) {
        $diff = (float) $value - (float) $b[$index];
        $sum += $diff * $diff;
    }
    return sqrt($sum);
}

try {
    $stmtCheck = $pdo->prepare("SELECT pk_id_empleado FROM empleado WHERE dni_empl = ?");
    $stmtCheck->execute([$dni]);
    if ($stmtCheck->fetch()) {
        jsonResponse(['status' => 'error', 'message' => 'Este DNI ya esta registrado.'], 409);
    }

    $stmtRostros = $pdo->query("SELECT pk_id_empleado, dni_empl, nomb_empl, apat_empl, rostro_embedding FROM empleado WHERE esta_empl = 1 AND rostro_embedding IS NOT NULL AND rostro_embedding <> ''");
    foreach ($stmtRostros->fetchAll(PDO::FETCH_ASSOC) as $empleadoExistente) {
        $embedding = json_decode($empleadoExistente['rostro_embedding'], true);
        if (!is_array($embedding) || count($embedding) !== 128) continue;

        $distancia = distanciaDescriptor($descriptorValidado, $embedding);
        if ($distancia <= 0.45) {
            $nombreExistente = trim($empleadoExistente['nomb_empl'] . ' ' . $empleadoExistente['apat_empl']);
            jsonResponse([
                'status' => 'error',
                'message' => "El rostro se parece demasiado a un empleado ya registrado: $nombreExistente, DNI {$empleadoExistente['dni_empl']}."
            ], 409);
        }
    }

    $nombreFoto = null;
    if (isset($_FILES['foto_perfil'])) {
        $nombreFoto = guardarImagenSubida($_FILES['foto_perfil'], "FOTO_$dni");
    }

    $sql = "INSERT INTO empleado (
                nomb_empl, apat_empl, amat_empl, dni_empl,
                fnac_empl, gene_empl, celu_empl, emai_empl,
                dire_empl, id_distrito, id_cargo, id_grupo,
                rostro_embedding, foto_empl, obsv_empl, esta_empl
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $nombre, $apat, $amat, $dni,
        $fnac, $genero, $telefono, $email,
        $direccion, $idDistrito, $idCargo, $idGrupo,
        json_encode($descriptorValidado), $nombreFoto, $observacion
    ]);

    Logger::log("Empleado registrado: DNI $dni", "AUDIT");
    auditEvent($pdo, 'CREAR', 'empleado', (string) $pdo->lastInsertId(), "Empleado registrado DNI $dni");
    jsonResponse(['status' => 'success', 'message' => 'Empleado registrado con exito.']);
} catch (RuntimeException $e) {
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 422);
} catch (PDOException $e) {
    Logger::error("Error al guardar empleado: " . $e->getMessage());
    jsonResponse(['status' => 'error', 'message' => 'Error de base de datos al registrar empleado.'], 500);
}
?>
