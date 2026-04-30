<?php
header('Content-Type: application/json');
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar Token CSRF
    if (!isset($_POST['csrf_token']) || !validarTokenCSRF($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Error de seguridad: Token CSRF inválido.']);
        exit;
    }

    // 1. Recoger datos del formulario
    $nombre      = trim($_POST['nombre'] ?? '');
    $apat        = trim($_POST['apellido_pat'] ?? '');
    $amat        = trim($_POST['apellido_mat'] ?? '');
    $dni         = trim($_POST['dni'] ?? '');
    $fnac        = $_POST['fecha_nac'] ?? null; // Nuevo campo
    $genero      = $_POST['genero'] ?? '';
    $telefono    = trim($_POST['telefono'] ?? '');
    $email       = trim($_POST['emai_empl'] ?? ''); // Coincide con el name del HTML
    $direccion   = trim($_POST['dire_empl'] ?? ''); // Coincide con el name del HTML
    $id_distrito = intval($_POST['id_distrito'] ?? 0);
    $id_cargo    = intval($_POST['id_cargo'] ?? 0);
    $id_grupo    = intval($_POST['id_grupo'] ?? 0);
    $observacion = trim($_POST['obsv_empl'] ?? '');
    $descriptor  = $_POST['descriptor'] ?? null;

    // Validación básica
    if (empty($nombre) || empty($apat) || empty($dni) || empty($descriptor)) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios o la validación facial.']);
        exit;
    }

    try {
        // 2. Verificar si el DNI ya existe
        $stmt_check = $pdo->prepare("SELECT pk_id_empleado FROM empleado WHERE dni_empl = ?");
        $stmt_check->execute([$dni]);
        if ($stmt_check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'Este DNI ya está registrado.']);
            exit;
        }

        // 3. Procesar la FOTO para el Fotocheck
        $nombre_foto = null;
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
            $extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
            $nombre_foto = "FOTO_" . $dni . "_" . time() . "." . $extension;
            $ruta_destino = "../uploads/fotos/" . $nombre_foto;
            
            // Crear carpeta si no existe
            if (!is_dir('../uploads/fotos/')) {
                mkdir('../uploads/fotos/', 0777, true);
            }
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $ruta_destino);
        }

        // 4. Insertar en la Base de Datos
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
            $direccion, $id_distrito, $id_cargo, $id_grupo,
            $descriptor, $nombre_foto, $observacion
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Empleado registrado con éxito.']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}