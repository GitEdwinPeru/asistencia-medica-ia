<?php
require_once __DIR__ . '/validators.php';

function empleadosFiltros(array $input): array {
    return [
        'buscar' => textoLimpio($input['buscar'] ?? '', 80),
        'dni' => preg_replace('/\D/', '', $input['dni'] ?? ''),
        'id_cargo' => (int) ($input['id_cargo'] ?? 0),
        'id_grupo' => (int) ($input['id_grupo'] ?? 0),
        'id_distrito' => (int) ($input['id_distrito'] ?? 0),
        'estado_doc' => $input['estado_doc'] ?? '',
    ];
}

function empleadosCatalogos(PDO $pdo): array {
    return [
        'cargos' => $pdo->query("SELECT pk_id_cargo, nomb_carg FROM cargo ORDER BY nomb_carg ASC")->fetchAll(PDO::FETCH_ASSOC),
        'grupos' => $pdo->query("SELECT pk_id_grupo, nomb_grup FROM grupo ORDER BY nomb_grup ASC")->fetchAll(PDO::FETCH_ASSOC),
        'sedes' => $pdo->query("SELECT pk_id_distrito, nomb_dist FROM distrito ORDER BY nomb_dist ASC")->fetchAll(PDO::FETCH_ASSOC),
    ];
}

function empleadosWhere(array $filtros): array {
    $where = ["e.esta_empl = 1"];
    $params = [];

    if ($filtros['dni'] !== '') {
        $where[] = "e.dni_empl = ?";
        $params[] = $filtros['dni'];
    }

    if ($filtros['buscar'] !== '') {
        $where[] = "(e.nomb_empl LIKE ? OR e.apat_empl LIKE ? OR e.amat_empl LIKE ? OR e.dni_empl LIKE ? OR e.emai_empl LIKE ?)";
        $like = '%' . $filtros['buscar'] . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }

    if ($filtros['id_cargo'] > 0) {
        $where[] = "e.id_cargo = ?";
        $params[] = $filtros['id_cargo'];
    }

    if ($filtros['id_grupo'] > 0) {
        $where[] = "e.id_grupo = ?";
        $params[] = $filtros['id_grupo'];
    }

    if ($filtros['id_distrito'] > 0) {
        $where[] = "e.id_distrito = ?";
        $params[] = $filtros['id_distrito'];
    }

    switch ($filtros['estado_doc']) {
        case 'sin_foto':
            $where[] = "(e.foto_empl IS NULL OR e.foto_empl = '')";
            break;
        case 'sin_descriptor':
            $where[] = "(e.rostro_embedding IS NULL OR e.rostro_embedding = '')";
            break;
        case 'sin_sede':
            $where[] = "(e.id_distrito IS NULL OR e.id_distrito = 0)";
            break;
        case 'completo':
            $where[] = "(e.foto_empl IS NOT NULL AND e.foto_empl <> '' AND e.rostro_embedding IS NOT NULL AND e.rostro_embedding <> '' AND e.id_distrito IS NOT NULL AND e.id_distrito > 0)";
            break;
    }

    return ['sql' => 'WHERE ' . implode(' AND ', $where), 'params' => $params];
}

function empleadosSelectBase(): string {
    return "FROM empleado e
        LEFT JOIN cargo c ON e.id_cargo = c.pk_id_cargo
        LEFT JOIN grupo g ON e.id_grupo = g.pk_id_grupo
        LEFT JOIN distrito d ON e.id_distrito = d.pk_id_distrito";
}

function empleadosConsulta(PDO $pdo, array $filtros, ?int $limit = null, int $offset = 0): array {
    $where = empleadosWhere($filtros);
    $sql = "SELECT e.pk_id_empleado, e.nomb_empl, e.apat_empl, e.amat_empl, e.dni_empl,
                   e.celu_empl, e.emai_empl, e.dire_empl, e.fnac_empl, e.foto_empl,
                   e.rostro_embedding, c.nomb_carg, g.nomb_grup, d.nomb_dist
            " . empleadosSelectBase() . "
            {$where['sql']}
            ORDER BY e.pk_id_empleado DESC";

    if ($limit !== null) {
        $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($where['params']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function empleadosTotal(PDO $pdo, array $filtros): int {
    $where = empleadosWhere($filtros);
    $stmt = $pdo->prepare("SELECT COUNT(*) " . empleadosSelectBase() . " {$where['sql']}");
    $stmt->execute($where['params']);
    return (int) $stmt->fetchColumn();
}

function empleadosChips(array $catalogos, array $filtros): array {
    $chips = [];
    if ($filtros['dni'] !== '') $chips[] = 'DNI: ' . $filtros['dni'];
    if ($filtros['buscar'] !== '') $chips[] = 'Busqueda: ' . $filtros['buscar'];

    foreach ($catalogos['cargos'] as $cargo) {
        if ((int) $cargo['pk_id_cargo'] === $filtros['id_cargo']) $chips[] = 'Cargo: ' . $cargo['nomb_carg'];
    }
    foreach ($catalogos['grupos'] as $grupo) {
        if ((int) $grupo['pk_id_grupo'] === $filtros['id_grupo']) $chips[] = 'Grupo: ' . $grupo['nomb_grup'];
    }
    foreach ($catalogos['sedes'] as $sede) {
        if ((int) $sede['pk_id_distrito'] === $filtros['id_distrito']) $chips[] = 'Sede: ' . $sede['nomb_dist'];
    }

    $estados = [
        'sin_foto' => 'Estado documental: Sin foto',
        'sin_descriptor' => 'Estado documental: Sin descriptor facial',
        'sin_sede' => 'Estado documental: Sin sede',
        'completo' => 'Estado documental: Completo',
    ];
    if (isset($estados[$filtros['estado_doc']])) $chips[] = $estados[$filtros['estado_doc']];

    return $chips;
}

function empleadosQueryString(array $filtros, array $extra = []): string {
    $params = array_filter([
        'buscar' => $filtros['buscar'] ?: null,
        'dni' => $filtros['dni'] ?: null,
        'id_cargo' => $filtros['id_cargo'] ?: null,
        'id_grupo' => $filtros['id_grupo'] ?: null,
        'id_distrito' => $filtros['id_distrito'] ?: null,
        'estado_doc' => $filtros['estado_doc'] ?: null,
    ], fn($value) => $value !== null && $value !== '');

    return http_build_query(array_merge($params, $extra));
}

function empleadosNombreArchivo(array $filtros, string $extension): string {
    $base = 'Directorio_Empleados';
    if ($filtros['dni'] !== '') $base .= '_DNI_' . $filtros['dni'];
    return $base . '_' . date('Ymd') . '.' . $extension;
}
?>
