<?php
header('Content-Type: application/json');
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';
require_once '../config/security.php';
require_once '../config/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit();
}

// Validar Token CSRF
if (!isset($_POST['csrf_token']) || !validarTokenCSRF($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'Error de seguridad: Token CSRF inválido.']);
    exit;
}

$id_empleado = intval($_POST['id_empleado'] ?? 0);

if ($id_empleado === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de empleado no válido']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Actualizar Datos Personales en tabla 'empleado'
    $sql_personal = "UPDATE empleado SET 
                        esta_civil = ?, 
                        nacionalidad = ?, 
                        celu_empl = ?, 
                        emai_empl = ?, 
                        dire_empl = ? 
                    WHERE pk_id_empleado = ?";
    $pdo->prepare($sql_personal)->execute([
        $_POST['esta_civil'] ?? null,
        $_POST['nacionalidad'] ?? null,
        $_POST['celu_empl'] ?? null,
        $_POST['emai_empl'] ?? null,
        $_POST['dire_empl'] ?? null,
        $id_empleado
    ]);

    // 2. Estudios (Limpiar e Insertar)
    $pdo->prepare("DELETE FROM empleado_estudios WHERE id_empleado = ?")->execute([$id_empleado]);
    if (!empty($_POST['estudio_titulo'])) {
        $sql_est = "INSERT INTO empleado_estudios (id_empleado, titulo, institucion, fecha_graduacion) VALUES (?, ?, ?, ?)";
        $stmt_est = $pdo->prepare($sql_est);
        foreach ($_POST['estudio_titulo'] as $i => $titulo) {
            if (trim($titulo) !== '') {
                $stmt_est->execute([$id_empleado, $titulo, $_POST['estudio_inst'][$i], !empty($_POST['estudio_fecha'][$i]) ? $_POST['estudio_fecha'][$i] : null]);
            }
        }
    }

    // 3. Bancos (Limpiar e Insertar con Encriptación)
    $pdo->prepare("DELETE FROM empleado_bancos WHERE id_empleado = ?")->execute([$id_empleado]);
    if (!empty($_POST['banco_nombre'])) {
        $sql_bnc = "INSERT INTO empleado_bancos (id_empleado, banco, tipo_cuenta, numero_cuenta) VALUES (?, ?, ?, ?)";
        $stmt_bnc = $pdo->prepare($sql_bnc);
        foreach ($_POST['banco_nombre'] as $i => $banco) {
            if (trim($banco) !== '') {
                $num_encriptado = encriptarDato($_POST['banco_numero'][$i]);
                $stmt_bnc->execute([$id_empleado, $banco, $_POST['banco_tipo'][$i], $num_encriptado]);
            }
        }
    }

    // 4. Familia (Limpiar e Insertar)
    $pdo->prepare("DELETE FROM empleado_familia WHERE id_empleado = ?")->execute([$id_empleado]);
    if (!empty($_POST['fam_nombre'])) {
        $sql_fam = "INSERT INTO empleado_familia (id_empleado, nombre, parentesco, fecha_nacimiento, ocupacion) VALUES (?, ?, ?, ?, ?)";
        $stmt_fam = $pdo->prepare($sql_fam);
        foreach ($_POST['fam_nombre'] as $i => $nombre) {
            if (trim($nombre) !== '') {
                $stmt_fam->execute([$id_empleado, $nombre, $_POST['fam_paren'][$i], !empty($_POST['fam_fecha'][$i]) ? $_POST['fam_fecha'][$i] : null, $_POST['fam_ocup'][$i]]);
            }
        }
    }

    // 5. Emergencia (Limpiar e Insertar)
    $pdo->prepare("DELETE FROM empleado_emergencia WHERE id_empleado = ?")->execute([$id_empleado]);
    if (!empty($_POST['eme_nombre'])) {
        $sql_eme = "INSERT INTO empleado_emergencia (id_empleado, nombre, relacion, telefono, direccion) VALUES (?, ?, ?, ?, ?)";
        $stmt_eme = $pdo->prepare($sql_eme);
        foreach ($_POST['eme_nombre'] as $i => $nombre) {
            if (trim($nombre) !== '') {
                $stmt_eme->execute([$id_empleado, $nombre, $_POST['eme_rel'][$i], $_POST['eme_tel'][$i], $_POST['eme_dir'][$i]]);
            }
        }
    }

    // 6. Experiencia (Limpiar e Insertar)
    $pdo->prepare("DELETE FROM empleado_experiencia WHERE id_empleado = ?")->execute([$id_empleado]);
    if (!empty($_POST['exp_empresa'])) {
        $sql_exp = "INSERT INTO empleado_experiencia (id_empleado, empresa, cargo, fecha_inicio, fecha_fin, descripcion) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_exp = $pdo->prepare($sql_exp);
        foreach ($_POST['exp_empresa'] as $i => $empresa) {
            if (trim($empresa) !== '') {
                $stmt_exp->execute([$id_empleado, $empresa, $_POST['exp_cargo'][$i], !empty($_POST['exp_inicio'][$i]) ? $_POST['exp_inicio'][$i] : null, !empty($_POST['exp_fin'][$i]) ? $_POST['exp_fin'][$i] : null, $_POST['exp_desc'][$i]]);
            }
        }
    }

    $pdo->commit();
    Logger::log("Hoja de Vida actualizada para Empleado ID $id_empleado");
    echo json_encode(['status' => 'success', 'message' => 'Información guardada correctamente']);

} catch (Exception $e) {
    $pdo->rollBack();
    Logger::error("Error al guardar Hoja de Vida ID $id_empleado: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar datos: ' . $e->getMessage()]);
}
?>
