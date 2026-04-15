<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capturamos los IDs y datos del formulario
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : 0;
    $nombre      = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $apellido    = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
    $id_cargo    = isset($_POST['id_cargo']) ? intval($_POST['id_cargo']) : 0;
    $id_grupo    = isset($_POST['id_grupo']) ? intval($_POST['id_grupo']) : 0;
    $id_distrito = isset($_POST['id_distrito']) ? intval($_POST['id_distrito']) : 0;

    if ($id_empleado > 0 && !empty($nombre)) {
        try {
            // Preparamos la actualización
            $sql = "UPDATE empleado SET 
                        nomb_empl = :nombre, 
                        apat_empl = :apellido, 
                        id_cargo = :cargo, 
                        id_grupo = :grupo, 
                        id_distrito = :distrito 
                    WHERE pk_id_empleado = :id";

            $stmt = $pdo->prepare($sql);
            $resultado = $stmt->execute([
                ':nombre'   => $nombre,
                ':apellido' => $apellido,
                ':cargo'    => $id_cargo,
                ':grupo'    => $id_grupo,
                ':distrito' => $id_distrito,
                ':id'       => $id_empleado
            ]);

            if ($resultado) {
                // Redirigir con éxito (puedes añadir un parámetro para mostrar una alerta)
                header("Location: ../views/empleados_lista.php?status=success&m=emp_actualizado");
                exit();
            } else {
                echo "Error al intentar actualizar los datos.";
            }

        } catch (PDOException $e) {
            echo "Error crítico en la base de datos: " . $e->getMessage();
        }
    } else {
        echo "Datos incompletos para procesar la actualización.";
    }
} else {
    header("Location: ../views/empleados_lista.php");
    exit();
}