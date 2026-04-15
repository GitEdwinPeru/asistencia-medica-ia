<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../config/db.php';

date_default_timezone_set('America/Lima');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : null;
    $ahora = date('Y-m-d H:i:s');
    $hoy = date('Y-m-d');
    $hora_actual = date('H:i:s');
    $hora_entrada_oficial = "08:00:00"; 

    try {
        // 1. Verificar si ya existe entrada hoy (Asegúrate que las columnas coincidan con tu DB)
        // He cambiado 'pk_id_asistencia' por 'id_asistencia' que es lo más común, 
        // pero cámbialo si tu columna tiene otro nombre.
        $sql_check = "SELECT * FROM asistencia 
                      WHERE id_empleado = :id 
                      AND DATE(fech_ingr) = :hoy 
                      AND fech_sali IS NULL LIMIT 1";
        
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':id' => $id_empleado, ':hoy' => $hoy]);
        $registro = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($registro) {
            // MARCAR SALIDA
            // IMPORTANTE: Aquí usamos la columna que sea tu llave primaria. 
            // Si en el SELECT anterior viste que se llama diferente, cámbialo aquí también.
            $id_a_actualizar = array_values($registro)[0]; // Esto toma la primera columna automáticamente

            $sql_update = "UPDATE asistencia SET fech_sali = :ahora WHERE " . array_keys($registro)[0] . " = :id_asist";
            $pdo->prepare($sql_update)->execute([':ahora' => $ahora, ':id_asist' => $id_a_actualizar]);
            
            echo json_encode(['status' => 'success', 'message' => 'SALIDA REGISTRADA', 'detalle' => "Hora: $hora_actual"]);
        } else {
            // MARCAR ENTRADA
            $es_tardanza = ($hora_actual > $hora_entrada_oficial);
            $tardanza = "00:00:00";

            if ($es_tardanza) {
                $diff = (new DateTime($hora_entrada_oficial))->diff(new DateTime($hora_actual));
                $tardanza = $diff->format('%H:%I:%S');
            }

            $sql_insert = "INSERT INTO asistencia (id_empleado, fech_ingr, horas_tard) VALUES (?, ?, ?)";
            $pdo->prepare($sql_insert)->execute([$id_empleado, $ahora, $tardanza]);

            echo json_encode([
                'status' => 'success', 
                'message' => $es_tardanza ? 'TARDANZA REGISTRADA' : 'ENTRADA PUNTUAL',
                'detalle' => "Hora: $hora_actual"
            ]);
        }
    } catch (PDOException $e) {
        // Si hay error de SQL, lo enviamos al JS para saber qué columna falta
        echo json_encode(['status' => 'error', 'message' => 'Error de Base de Datos', 'detalle' => $e->getMessage()]);
    }
}
exit;