<?php
header('Content-Type: application/json');
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger datos con valores por defecto
    $nombre      = trim($_POST['nombre'] ?? '');
    $apat        = trim($_POST['apellido_pat'] ?? '');
    $amat        = trim($_POST['apellido_mat'] ?? '');
    $dni         = trim($_POST['dni'] ?? '');
    $genero      = $_POST['genero'] ?? '';
    $telefono    = trim($_POST['telefono'] ?? '');
    $direccion   = trim($_POST['direccion'] ?? '');
    $id_distrito = intval($_POST['id_distrito'] ?? 0);
    $id_cargo    = intval($_POST['id_cargo'] ?? 0);
    $id_grupo    = intval($_POST['id_grupo'] ?? 0);
    $descriptor  = $_POST['descriptor'] ?? null;

    // Validación estricta
    if (empty($nombre) || empty($apat) || empty($dni) || empty($descriptor)) {
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos personales o biométricos obligatorios.']);
        exit;
    }

    try {
        // Verificar duplicados
        $stmt_check = $pdo->prepare("SELECT pk_id_empleado FROM empleado WHERE dni_empl = ?");
        $stmt_check->execute([$dni]);
        if ($stmt_check->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'El DNI ingresado ya pertenece a un registro activo.']);
            exit;
        }

        // Insertar en la tabla empleado
        $sql = "INSERT INTO empleado (
                    nomb_empl, apat_empl, amat_empl, dni_empl, 
                    gene_empl, telf_empl, dire_empl,
                    rostro_embedding, id_distrito, id_cargo, id_grupo, esta_empl
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre, $apat, $amat, $dni, 
            $genero, $telefono, $direccion,
            $descriptor, $id_distrito, $id_cargo, $id_grupo
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Personal registrado exitosamente en la red médica.']);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error de integración: ' . $e->getMessage()]);
    }
}