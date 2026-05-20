<?php
function cargarHojaVidaData(PDO $pdo, array $input): array {
    $idEmpleado = isset($input['id']) ? (int) $input['id'] : 0;
    $estadoDoc = $input['estado_doc'] ?? '';

    if ($idEmpleado === 0) {
        $idEmpleado = (int) $pdo->query("SELECT pk_id_empleado FROM empleado WHERE esta_empl = 1 LIMIT 1")->fetchColumn();
        if ($idEmpleado === 0) return ['redirect' => 'dashboard.php'];
    }

    $stmt = $pdo->prepare("SELECT e.*, c.nomb_carg, g.nomb_grup, d.nomb_dist
                           FROM empleado e
                           LEFT JOIN cargo c ON e.id_cargo = c.pk_id_cargo
                           LEFT JOIN grupo g ON e.id_grupo = g.pk_id_grupo
                           LEFT JOIN distrito d ON e.id_distrito = d.pk_id_distrito
                           WHERE e.pk_id_empleado = ?");
    $stmt->execute([$idEmpleado]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$empleado) return ['redirect' => 'empleados_lista.php'];

    $detalles = [];
    foreach ([
        'estudios' => 'empleado_estudios',
        'bancos' => 'empleado_bancos',
        'familia' => 'empleado_familia',
        'emergencia' => 'empleado_emergencia',
        'experiencia' => 'empleado_experiencia',
    ] as $key => $tabla) {
        $stmt = $pdo->prepare("SELECT * FROM $tabla WHERE id_empleado = ?");
        $stmt->execute([$idEmpleado]);
        $detalles[$key] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $whereSelector = ["esta_empl = 1"];
    switch ($estadoDoc) {
        case 'sin_foto':
            $whereSelector[] = "(foto_empl IS NULL OR foto_empl = '')";
            break;
        case 'sin_descriptor':
            $whereSelector[] = "(rostro_embedding IS NULL OR rostro_embedding = '')";
            break;
        case 'sin_sede':
            $whereSelector[] = "(id_distrito IS NULL OR id_distrito = 0)";
            break;
        case 'completo':
            $whereSelector[] = "(foto_empl IS NOT NULL AND foto_empl <> '' AND rostro_embedding IS NOT NULL AND rostro_embedding <> '' AND id_distrito IS NOT NULL AND id_distrito > 0)";
            break;
        default:
            $estadoDoc = '';
    }

    $todosEmpleados = $pdo->query("SELECT pk_id_empleado, nomb_empl, apat_empl, dni_empl
                                   FROM empleado
                                   WHERE " . implode(' AND ', $whereSelector) . "
                                   ORDER BY apat_empl ASC")->fetchAll(PDO::FETCH_ASSOC);

    return [
        'redirect' => null,
        'id_empleado' => $idEmpleado,
        'estado_doc' => $estadoDoc,
        'empleado' => $empleado,
        'todos_empleados' => $todosEmpleados,
    ] + $detalles;
}
?>
