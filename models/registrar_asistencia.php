<?php
// error_reporting(0); // Eliminamos para auditoría interna, se controla con display_errors
ini_set('display_errors', 0);
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/logger.php';

date_default_timezone_set('America/Lima');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitización básica
    $id_empleado = isset($_POST['id_empleado']) ? intval($_POST['id_empleado']) : null;
    $tipo_registro = isset($_POST['tipo_registro']) ? filter_var($_POST['tipo_registro'], FILTER_SANITIZE_STRING) : null; 
    $id_distrito = isset($_POST['id_distrito']) ? intval($_POST['id_distrito']) : null;
    
    $ahora = date('Y-m-d H:i:s');
    $hoy = date('Y-m-d');
    $hora_actual = date('H:i:s');
    $hora_entrada_oficial = "08:15:00"; 

    if (!$id_empleado || !$tipo_registro || ($tipo_registro === 'entrada' && !$id_distrito)) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos para el registro']);
        exit;
    }

    try {
        // Verificar existencia del empleado
        $stmt_emp = $pdo->prepare("SELECT nomb_empl, apat_empl FROM empleado WHERE pk_id_empleado = ? AND esta_empl = 1");
        $stmt_emp->execute([$id_empleado]);
        $empleado = $stmt_emp->fetch();

        if (!$empleado) {
            echo json_encode(['status' => 'error', 'message' => 'Empleado no encontrado o inactivo']);
            exit;
        }

        if ($tipo_registro === 'entrada') {
            // 1. Verificar si ya tiene una entrada el día de hoy
            $sql_check = "SELECT id_asistencia FROM asistencia 
                          WHERE id_empleado = ? AND DATE(fech_ingr) = ? LIMIT 1";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$id_empleado, $hoy]);
            
            if ($stmt_check->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Ya registraste tu entrada el día de hoy']);
                exit;
            }

            // 2. Calcular tardanza
            $es_tardanza = ($hora_actual > $hora_entrada_oficial);
            $tardanza = "00:00:00";
            if ($es_tardanza) {
                $diff = (new DateTime($hora_entrada_oficial))->diff(new DateTime($hora_actual));
                $tardanza = $diff->format('%H:%I:%S');
            }

            // 3. Insertar Entrada con Sede del día
            $sql_insert = "INSERT INTO asistencia (id_empleado, id_distrito, fech_ingr, horas_tard) VALUES (?, ?, ?, ?)";
            $pdo->prepare($sql_insert)->execute([$id_empleado, $id_distrito, $ahora, $tardanza]);

            Logger::log("Asistencia registrada: Empleado " . $empleado['nomb_empl'] . " " . $empleado['apat_empl'] . " (ID $id_empleado) en Sede ID $id_distrito - Tipo: Entrada", "AUDIT");

            echo json_encode([
                'status' => 'success', 
                'message' => $es_tardanza ? "TARDANZA REGISTRADA ($tardanza)" : "ENTRADA PUNTUAL REGISTRADA",
                'detalle' => "Hora: $hora_actual"
            ]);

        } else if ($tipo_registro === 'salida') {
            // 1. Buscar la entrada abierta (sin fecha de salida) del día de hoy
            $sql_find = "SELECT id_asistencia, fech_ingr, id_distrito FROM asistencia 
                         WHERE id_empleado = ? AND DATE(fech_ingr) = ? AND fech_sali IS NULL 
                         ORDER BY id_asistencia DESC LIMIT 1";
            $stmt_find = $pdo->prepare($sql_find);
            $stmt_find->execute([$id_empleado, $hoy]);
            $asistencia = $stmt_find->fetch(PDO::FETCH_ASSOC);

            if (!$asistencia) {
                echo json_encode(['status' => 'error', 'message' => 'No se encontró una entrada abierta para hoy']);
                exit;
            }

            // 2. Actualizar Salida y calcular horas trabajadas
            $id_asistencia = $asistencia['id_asistencia'];
            $id_distrito_actual = $asistencia['id_distrito'];
            $fech_ingr = new DateTime($asistencia['fech_ingr']);
            $fech_sali = new DateTime($ahora);
            
            $intervalo = $fech_ingr->diff($fech_sali);
            $horas_trabajadas = $intervalo->format('%H:%I:%S');

            $sql_update = "UPDATE asistencia SET fech_sali = ?, horas_trab = ? WHERE id_asistencia = ?";
            $pdo->prepare($sql_update)->execute([$ahora, $horas_trabajadas, $id_asistencia]);

            Logger::log("Asistencia registrada: Empleado " . $empleado['nomb_empl'] . " " . $empleado['apat_empl'] . " (ID $id_empleado) en Sede ID $id_distrito_actual - Tipo: Salida", "AUDIT");

            echo json_encode([
                'status' => 'success', 
                'message' => 'SALIDA REGISTRADA',
                'detalle' => "Horas cumplidas: $horas_trabajadas"
            ]);
        }

    } catch (PDOException $e) {
        Logger::error("Error en registro de asistencia para Empleado ID $id_empleado: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error interno del servidor']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}