<?php
require_once __DIR__ . '/validators.php';

function asistenciaPresets(array $input): array {
    $preset = $input['preset'] ?? '';
    if ($preset === '') return $input;

    $input['fecha_fin'] = date('Y-m-d');
    switch ($preset) {
        case 'hoy':
            $input['fecha_inicio'] = date('Y-m-d');
            break;
        case 'ayer':
            $input['fecha_inicio'] = date('Y-m-d', strtotime('-1 day'));
            $input['fecha_fin'] = $input['fecha_inicio'];
            break;
        case 'semana':
            $input['fecha_inicio'] = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'mes':
            $input['fecha_inicio'] = date('Y-m-d', strtotime('-30 days'));
            break;
    }

    return $input;
}

function asistenciaFiltros(array $input): array {
    $input = asistenciaPresets($input);
    $fechaInicio = $input['fecha_inicio'] ?? '';
    $fechaFin = $input['fecha_fin'] ?? '';

    if (!validarFechaOpcional($fechaInicio)) $fechaInicio = '';
    if (!validarFechaOpcional($fechaFin)) $fechaFin = '';

    if ($fechaInicio !== '' && $fechaFin !== '' && $fechaInicio > $fechaFin) {
        [$fechaInicio, $fechaFin] = [$fechaFin, $fechaInicio];
    }

    return [
        'id' => intval($input['id'] ?? 0),
        'dni' => preg_replace('/\D/', '', $input['dni'] ?? ''),
        'buscar' => textoLimpio($input['buscar'] ?? '', 100),
        'id_distrito' => intval($input['id_distrito'] ?? 0),
        'id_cargo' => intval($input['id_cargo'] ?? 0),
        'id_grupo' => intval($input['id_grupo'] ?? 0),
        'estado' => $input['estado'] ?? '',
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'preset' => $input['preset'] ?? '',
    ];
}

function asistenciaCatalogos(PDO $pdo): array {
    return [
        'sedes' => $pdo->query("SELECT pk_id_distrito, nomb_dist FROM distrito ORDER BY nomb_dist ASC")->fetchAll(PDO::FETCH_ASSOC),
        'cargos' => $pdo->query("SELECT pk_id_cargo, nomb_carg FROM cargo ORDER BY nomb_carg ASC")->fetchAll(PDO::FETCH_ASSOC),
        'grupos' => $pdo->query("SELECT pk_id_grupo, nomb_grup FROM grupo ORDER BY nomb_grup ASC")->fetchAll(PDO::FETCH_ASSOC),
    ];
}

function asistenciaWhere(array $filtros): array {
    $where = [];
    $params = [];

    if ($filtros['id'] > 0) {
        $where[] = "a.id_empleado = ?";
        $params[] = $filtros['id'];
    }

    if ($filtros['dni'] !== '') {
        $where[] = "e.dni_empl = ?";
        $params[] = $filtros['dni'];
    }

    if ($filtros['buscar'] !== '') {
        $where[] = "(e.nomb_empl LIKE ? OR e.apat_empl LIKE ? OR e.amat_empl LIKE ? OR e.dni_empl LIKE ?)";
        $like = '%' . $filtros['buscar'] . '%';
        array_push($params, $like, $like, $like, $like);
    }

    if ($filtros['id_distrito'] > 0) {
        $where[] = "a.id_distrito = ?";
        $params[] = $filtros['id_distrito'];
    }

    if ($filtros['id_cargo'] > 0) {
        $where[] = "e.id_cargo = ?";
        $params[] = $filtros['id_cargo'];
    }

    if ($filtros['id_grupo'] > 0) {
        $where[] = "e.id_grupo = ?";
        $params[] = $filtros['id_grupo'];
    }

    if ($filtros['fecha_inicio'] !== '') {
        $where[] = "DATE(a.fech_ingr) >= ?";
        $params[] = $filtros['fecha_inicio'];
    }

    if ($filtros['fecha_fin'] !== '') {
        $where[] = "DATE(a.fech_ingr) <= ?";
        $params[] = $filtros['fecha_fin'];
    }

    switch ($filtros['estado']) {
        case 'puntual':
            $where[] = "(a.horas_tard IS NULL OR a.horas_tard = '00:00:00')";
            break;
        case 'tardanza':
            $where[] = "a.horas_tard > '00:00:00'";
            break;
        case 'sin_salida':
            $where[] = "a.fech_sali IS NULL";
            break;
        case 'con_salida':
            $where[] = "a.fech_sali IS NOT NULL";
            break;
    }

    return [
        'sql' => $where ? 'WHERE ' . implode(' AND ', $where) : '',
        'params' => $params,
    ];
}

function asistenciaSelectBase(): string {
    return "FROM asistencia a
        INNER JOIN empleado e ON a.id_empleado = e.pk_id_empleado
        INNER JOIN cargo c ON e.id_cargo = c.pk_id_cargo
        INNER JOIN grupo g ON e.id_grupo = g.pk_id_grupo
        LEFT JOIN distrito d_asist ON a.id_distrito = d_asist.pk_id_distrito
        LEFT JOIN distrito d_emp ON e.id_distrito = d_emp.pk_id_distrito";
}

function asistenciaConsulta(PDO $pdo, array $filtros, ?int $limit = null, int $offset = 0): array {
    $where = asistenciaWhere($filtros);
    $sql = "SELECT a.id_asistencia, a.id_empleado, a.id_distrito, a.fech_ingr, a.fech_sali,
                   a.horas_tard, a.horas_trab,
                   e.dni_empl, e.nomb_empl, e.apat_empl, e.amat_empl,
                   c.nomb_carg, g.nomb_grup,
                   d_emp.nomb_dist AS distrito_base,
                   d_asist.nomb_dist AS sede_marcacion
            " . asistenciaSelectBase() . "
            {$where['sql']}
            ORDER BY a.fech_ingr DESC";

    if ($limit !== null) {
        $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function asistenciaTotal(PDO $pdo, array $filtros): int {
    $where = asistenciaWhere($filtros);
    $stmt = $pdo->prepare("SELECT COUNT(*) " . asistenciaSelectBase() . " {$where['sql']}");
    $stmt->execute($where['params']);
    return (int) $stmt->fetchColumn();
}

function asistenciaResumen(PDO $pdo, array $filtros): array {
    $where = asistenciaWhere($filtros);
    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN a.horas_tard > '00:00:00' THEN 1 ELSE 0 END) AS tardanzas,
                SUM(CASE WHEN a.fech_sali IS NULL THEN 1 ELSE 0 END) AS sin_salida,
                SEC_TO_TIME(SUM(COALESCE(TIME_TO_SEC(a.horas_trab), 0))) AS horas_total
            " . asistenciaSelectBase() . " {$where['sql']}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'total' => (int) ($row['total'] ?? 0),
        'tardanzas' => (int) ($row['tardanzas'] ?? 0),
        'sin_salida' => (int) ($row['sin_salida'] ?? 0),
        'puntuales' => max(0, (int) ($row['total'] ?? 0) - (int) ($row['tardanzas'] ?? 0)),
        'horas_total' => $row['horas_total'] ?? '00:00:00',
    ];
}

function asistenciaEmpleadoReferencia(PDO $pdo, array $filtros): ?array {
    if ($filtros['id'] <= 0 && $filtros['dni'] === '') return null;

    if ($filtros['id'] > 0) {
        $stmt = $pdo->prepare("SELECT pk_id_empleado, dni_empl, nomb_empl, apat_empl, amat_empl FROM empleado WHERE pk_id_empleado = ? LIMIT 1");
        $stmt->execute([$filtros['id']]);
    } else {
        $stmt = $pdo->prepare("SELECT pk_id_empleado, dni_empl, nomb_empl, apat_empl, amat_empl FROM empleado WHERE dni_empl = ? LIMIT 1");
        $stmt->execute([$filtros['dni']]);
    }

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function asistenciaFiltrosActivos(PDO $pdo, array $filtros): array {
    $chips = [];
    $empleado = asistenciaEmpleadoReferencia($pdo, $filtros);

    if ($empleado) {
        $chips[] = 'Empleado: ' . trim($empleado['nomb_empl'] . ' ' . $empleado['apat_empl']) . ' - DNI ' . $empleado['dni_empl'];
    } elseif ($filtros['dni'] !== '') {
        $chips[] = 'DNI: ' . $filtros['dni'];
    }

    if ($filtros['buscar'] !== '') $chips[] = 'Busqueda: ' . $filtros['buscar'];
    if ($filtros['fecha_inicio'] !== '') $chips[] = 'Desde: ' . $filtros['fecha_inicio'];
    if ($filtros['fecha_fin'] !== '') $chips[] = 'Hasta: ' . $filtros['fecha_fin'];

    $catalogos = asistenciaCatalogos($pdo);
    foreach ($catalogos['sedes'] as $sede) {
        if ((int) $sede['pk_id_distrito'] === $filtros['id_distrito']) $chips[] = 'Sede: ' . $sede['nomb_dist'];
    }
    foreach ($catalogos['cargos'] as $cargo) {
        if ((int) $cargo['pk_id_cargo'] === $filtros['id_cargo']) $chips[] = 'Cargo: ' . $cargo['nomb_carg'];
    }
    foreach ($catalogos['grupos'] as $grupo) {
        if ((int) $grupo['pk_id_grupo'] === $filtros['id_grupo']) $chips[] = 'Grupo: ' . $grupo['nomb_grup'];
    }

    $estados = [
        'puntual' => 'Estado: Puntual',
        'tardanza' => 'Estado: Tardanza',
        'sin_salida' => 'Estado: Sin salida',
        'con_salida' => 'Estado: Con salida',
    ];
    if (isset($estados[$filtros['estado']])) $chips[] = $estados[$filtros['estado']];

    return $chips;
}

function asistenciaQueryString(array $filtros, array $extra = []): string {
    $params = array_filter([
        'id' => $filtros['id'] ?: null,
        'dni' => $filtros['dni'] ?: null,
        'buscar' => $filtros['buscar'] ?: null,
        'id_distrito' => $filtros['id_distrito'] ?: null,
        'id_cargo' => $filtros['id_cargo'] ?: null,
        'id_grupo' => $filtros['id_grupo'] ?: null,
        'estado' => $filtros['estado'] ?: null,
        'fecha_inicio' => $filtros['fecha_inicio'] ?: null,
        'fecha_fin' => $filtros['fecha_fin'] ?: null,
        'preset' => $filtros['preset'] ?: null,
    ], fn($value) => $value !== null && $value !== '');

    return http_build_query(array_merge($params, $extra));
}

function asistenciaNombreArchivo(PDO $pdo, array $filtros, string $extension): string {
    $empleado = asistenciaEmpleadoReferencia($pdo, $filtros);
    $base = 'Reporte_Asistencia';
    if ($empleado) {
        $base .= '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $empleado['dni_empl'] . '_' . $empleado['apat_empl']);
    }

    return $base . '_' . date('Ymd') . '.' . $extension;
}
?>
