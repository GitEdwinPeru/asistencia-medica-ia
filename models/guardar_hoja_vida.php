<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/security.php';
require_once '../config/logger.php';
require_once '../config/response.php';
require_once '../config/validators.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Metodo no permitido'], 405);
}

requerirCSRF($_POST['csrf_token'] ?? '', true);

function hvArray(string $name): array {
    return is_array($_POST[$name] ?? null) ? $_POST[$name] : [];
}

function hvItem(array $array, int $index, string $default = ''): string {
    return isset($array[$index]) ? trim((string) $array[$index]) : $default;
}

function hvValidarFecha(?string $fecha, string $campo): ?string {
    $fecha = trim((string) $fecha);
    if ($fecha === '') return null;
    if (!validarFechaOpcional($fecha)) {
        throw new InvalidArgumentException("Fecha invalida en $campo.");
    }
    return $fecha;
}

$id_empleado = (int) ($_POST['id_empleado'] ?? 0);
if ($id_empleado <= 0) {
    jsonResponse(['status' => 'error', 'message' => 'ID de empleado no valido.'], 422);
}

$stmtExiste = $pdo->prepare("SELECT pk_id_empleado FROM empleado WHERE pk_id_empleado = ? AND esta_empl = 1");
$stmtExiste->execute([$id_empleado]);
if (!$stmtExiste->fetch()) {
    jsonResponse(['status' => 'error', 'message' => 'El empleado no existe o esta inactivo.'], 404);
}

$estadoCivil = textoLimpio($_POST['esta_civil'] ?? '', 50);
$nacionalidad = textoLimpio($_POST['nacionalidad'] ?? '', 100);
$celular = preg_replace('/\D/', '', $_POST['celu_empl'] ?? '');
$email = textoLimpio($_POST['emai_empl'] ?? '', 150);
$direccion = textoLimpio($_POST['dire_empl'] ?? '', 255);

if ($celular !== '' && !soloDigitos($celular, 9)) {
    jsonResponse(['status' => 'error', 'message' => 'El celular debe tener exactamente 9 digitos numericos.'], 422);
}

if (!validarEmailOpcional($email)) {
    jsonResponse(['status' => 'error', 'message' => 'El correo electronico no es valido.'], 422);
}

try {
    $pdo->beginTransaction();

    $sqlPersonal = "UPDATE empleado SET esta_civil = ?, nacionalidad = ?, celu_empl = ?, emai_empl = ?, dire_empl = ? WHERE pk_id_empleado = ?";
    $pdo->prepare($sqlPersonal)->execute([$estadoCivil ?: null, $nacionalidad ?: null, $celular ?: null, $email ?: null, $direccion ?: null, $id_empleado]);

    $pdo->prepare("DELETE FROM empleado_estudios WHERE id_empleado = ?")->execute([$id_empleado]);
    $titulos = hvArray('estudio_titulo');
    $instituciones = hvArray('estudio_inst');
    $fechasEstudio = hvArray('estudio_fecha');
    if ($titulos) {
        $stmt = $pdo->prepare("INSERT INTO empleado_estudios (id_empleado, titulo, institucion, fecha_graduacion) VALUES (?, ?, ?, ?)");
        foreach ($titulos as $i => $titulo) {
            $titulo = textoLimpio($titulo, 150);
            if ($titulo === '') continue;
            $stmt->execute([
                $id_empleado,
                $titulo,
                textoLimpio(hvItem($instituciones, $i), 150),
                hvValidarFecha(hvItem($fechasEstudio, $i), 'estudios')
            ]);
        }
    }

    $pdo->prepare("DELETE FROM empleado_bancos WHERE id_empleado = ?")->execute([$id_empleado]);
    $bancos = hvArray('banco_nombre');
    $tiposCuenta = hvArray('banco_tipo');
    $numerosCuenta = hvArray('banco_numero');
    if ($bancos) {
        $stmt = $pdo->prepare("INSERT INTO empleado_bancos (id_empleado, banco, tipo_cuenta, numero_cuenta) VALUES (?, ?, ?, ?)");
        foreach ($bancos as $i => $banco) {
            $banco = textoLimpio($banco, 100);
            if ($banco === '') continue;
            $numero = preg_replace('/\D/', '', hvItem($numerosCuenta, $i));
            if ($numero === '' || strlen($numero) < 6 || strlen($numero) > 30) {
                throw new InvalidArgumentException('Cada cuenta bancaria debe tener entre 6 y 30 digitos.');
            }
            $tipo = in_array(hvItem($tiposCuenta, $i), ['Ahorros', 'Corriente'], true) ? hvItem($tiposCuenta, $i) : 'Ahorros';
            $stmt->execute([$id_empleado, $banco, $tipo, encriptarDato($numero)]);
        }
    }

    $pdo->prepare("DELETE FROM empleado_familia WHERE id_empleado = ?")->execute([$id_empleado]);
    $familia = hvArray('fam_nombre');
    $parentescos = hvArray('fam_paren');
    $fechasFamilia = hvArray('fam_fecha');
    $ocupaciones = hvArray('fam_ocup');
    if ($familia) {
        $stmt = $pdo->prepare("INSERT INTO empleado_familia (id_empleado, nombre, parentesco, fecha_nacimiento, ocupacion) VALUES (?, ?, ?, ?, ?)");
        foreach ($familia as $i => $nombre) {
            $nombre = textoLimpio($nombre, 150);
            if ($nombre === '') continue;
            $stmt->execute([
                $id_empleado,
                $nombre,
                textoLimpio(hvItem($parentescos, $i), 80),
                hvValidarFecha(hvItem($fechasFamilia, $i), 'familia'),
                textoLimpio(hvItem($ocupaciones, $i), 120)
            ]);
        }
    }

    $pdo->prepare("DELETE FROM empleado_emergencia WHERE id_empleado = ?")->execute([$id_empleado]);
    $emergencia = hvArray('eme_nombre');
    $relaciones = hvArray('eme_rel');
    $telefonos = hvArray('eme_tel');
    $direcciones = hvArray('eme_dir');
    if ($emergencia) {
        $stmt = $pdo->prepare("INSERT INTO empleado_emergencia (id_empleado, nombre, relacion, telefono, direccion) VALUES (?, ?, ?, ?, ?)");
        foreach ($emergencia as $i => $nombre) {
            $nombre = textoLimpio($nombre, 150);
            if ($nombre === '') continue;
            $telefono = preg_replace('/\D/', '', hvItem($telefonos, $i));
            if ($telefono !== '' && !soloDigitos($telefono, 9)) {
                throw new InvalidArgumentException('Los telefonos de emergencia deben tener 9 digitos.');
            }
            $stmt->execute([
                $id_empleado,
                $nombre,
                textoLimpio(hvItem($relaciones, $i), 80),
                $telefono,
                textoLimpio(hvItem($direcciones, $i), 255)
            ]);
        }
    }

    $pdo->prepare("DELETE FROM empleado_experiencia WHERE id_empleado = ?")->execute([$id_empleado]);
    $empresas = hvArray('exp_empresa');
    $cargos = hvArray('exp_cargo');
    $inicios = hvArray('exp_inicio');
    $fines = hvArray('exp_fin');
    $descripciones = hvArray('exp_desc');
    if ($empresas) {
        $stmt = $pdo->prepare("INSERT INTO empleado_experiencia (id_empleado, empresa, cargo, fecha_inicio, fecha_fin, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($empresas as $i => $empresa) {
            $empresa = textoLimpio($empresa, 150);
            if ($empresa === '') continue;
            $inicio = hvValidarFecha(hvItem($inicios, $i), 'experiencia inicio');
            $fin = hvValidarFecha(hvItem($fines, $i), 'experiencia fin');
            if ($inicio && $fin && $inicio > $fin) {
                throw new InvalidArgumentException('La fecha fin de experiencia no puede ser anterior al inicio.');
            }
            $stmt->execute([
                $id_empleado,
                $empresa,
                textoLimpio(hvItem($cargos, $i), 120),
                $inicio,
                $fin,
                textoLimpio(hvItem($descripciones, $i), 1000)
            ]);
        }
    }

    $pdo->commit();
    Logger::log("Hoja de Vida actualizada para Empleado ID $id_empleado", "AUDIT");
    jsonResponse(['status' => 'success', 'message' => 'Informacion guardada correctamente.']);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(['status' => 'error', 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    Logger::error("Error al guardar Hoja de Vida ID $id_empleado: " . $e->getMessage());
    jsonResponse(['status' => 'error', 'message' => 'Error al guardar la hoja de vida.'], 500);
}
?>
