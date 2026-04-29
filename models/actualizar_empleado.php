<?php
header('Content-Type: application/json'); // Cambiado a JSON para manejar la respuesta con SweetAlert
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Captura de datos básicos e IDs
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : 0;
    $nombre      = trim($_POST['nombre'] ?? '');
    $apat        = trim($_POST['apellido_pat'] ?? '');
    $amat        = trim($_POST['apellido_mat'] ?? '');
    $fnac        = $_POST['fecha_nac'] ?? null;
    $genero      = $_POST['genero'] ?? '';
    $telefono    = trim($_POST['telefono'] ?? '');
    $email       = trim($_POST['emai_empl'] ?? '');
    $direccion   = trim($_POST['dire_empl'] ?? '');
    $id_cargo    = intval($_POST['id_cargo'] ?? 0);
    $id_grupo    = intval($_POST['id_grupo'] ?? 0);
    $id_distrito = intval($_POST['id_distrito'] ?? 0);
    $observacion = trim($_POST['obsv_empl'] ?? '');

    if ($id_empleado > 0 && !empty($nombre)) {
        try {
            // 2. Gestión de la Nueva Foto (Opcional)
            $nombre_foto = null;
            $cambio_foto = false;

            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
                $extension = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
                // Usamos el ID para nombrar la foto y evitar duplicados
                $nombre_foto = "FOTO_UPD_" . $id_empleado . "_" . time() . "." . $extension;
                $ruta_destino = "../uploads/fotos/" . $nombre_foto;
                
                if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $ruta_destino)) {
                    $cambio_foto = true;
                }
            }

            // 3. Construcción dinámica del SQL de actualización
            $sql = "UPDATE empleado SET 
                        nomb_empl = :nombre, 
                        apat_empl = :apat, 
                        amat_empl = :amat,
                        fnac_empl = :fnac,
                        gene_empl = :genero,
                        celu_empl = :celu,
                        emai_empl = :email,
                        dire_empl = :direccion,
                        id_cargo = :cargo, 
                        id_grupo = :grupo, 
                        id_distrito = :distrito,
                        obsv_empl = :obsv";
            
            // Solo actualizamos la columna de foto si se subió una nueva
            if ($cambio_foto) {
                $sql .= ", foto_empl = :foto";
            }

            $sql .= " WHERE pk_id_empleado = :id";

            $params = [
                ':nombre'    => $nombre,
                ':apat'      => $apat,
                ':amat'      => $amat,
                ':fnac'      => $fnac,
                ':genero'    => $genero,
                ':celu'      => $telefono,
                ':email'     => $email,
                ':direccion' => $direccion,
                ':cargo'     => $id_cargo,
                ':grupo'     => $id_grupo,
                ':distrito'  => $id_distrito,
                ':obsv'      => $observacion,
                ':id'        => $id_empleado
            ];

            if ($cambio_foto) {
                $params[':foto'] = $nombre_foto;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['status' => 'success', 'message' => 'Información actualizada correctamente.']);

        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error en base de datos: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para actualizar.']);
    }
} else {
    header("Location: ../views/empleados_lista.php");
    exit();
}