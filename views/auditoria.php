<?php
require_once '../config/auth.php';
requerirPermiso('auditoria');
require_once '../config/db.php';
require_once '../config/validators.php';
require_once '../config/ui_helpers.php';

$porPagina = 20;
$pagina = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($pagina - 1) * $porPagina;
$filtros = [
    'accion' => textoLimpio($_GET['accion'] ?? '', 80),
    'entidad' => textoLimpio($_GET['entidad'] ?? '', 80),
    'actor' => (int) ($_GET['actor'] ?? 0),
    'desde' => $_GET['desde'] ?? '',
    'hasta' => $_GET['hasta'] ?? '',
];
if (!validarFechaOpcional($filtros['desde'])) $filtros['desde'] = '';
if (!validarFechaOpcional($filtros['hasta'])) $filtros['hasta'] = '';

$where = [];
$params = [];
if ($filtros['accion'] !== '') { $where[] = 'a.accion LIKE ?'; $params[] = '%' . $filtros['accion'] . '%'; }
if ($filtros['entidad'] !== '') { $where[] = 'a.entidad LIKE ?'; $params[] = '%' . $filtros['entidad'] . '%'; }
if ($filtros['actor'] > 0) { $where[] = 'a.actor_id = ?'; $params[] = $filtros['actor']; }
if ($filtros['desde'] !== '') { $where[] = 'DATE(a.creado_el) >= ?'; $params[] = $filtros['desde']; }
if ($filtros['hasta'] !== '') { $where[] = 'DATE(a.creado_el) <= ?'; $params[] = $filtros['hasta']; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM auditoria_eventos a $whereSql");
$stmtTotal->execute($params);
$total = (int) $stmtTotal->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$sql = "SELECT a.*, l.usuario, l.perfil
        FROM auditoria_eventos a
        LEFT JOIN login l ON a.actor_id = l.pk_id_login
        $whereSql
        ORDER BY a.creado_el DESC
        LIMIT $porPagina OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$usuarios = $pdo->query("SELECT pk_id_login, usuario FROM login ORDER BY usuario ASC")->fetchAll(PDO::FETCH_ASSOC);
$qs = function(array $extra = []) use ($filtros) {
    return http_build_query(array_merge(array_filter($filtros, fn($v) => $v !== '' && $v !== 0), $extra));
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria del Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/ui_common.css">
    <link rel="stylesheet" href="../assets/css/responsive_tables.css">
</head>
<body class="bg-light">
    <div class="container-fluid py-5 px-4">
        <div class="mb-3"><a href="dashboard.php" class="btn btn-link text-decoration-none text-muted fw-bold"><i class="bi bi-arrow-left-circle me-2"></i> Volver al Dashboard</a></div>
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i> Auditoria del Sistema</h2>
                <p class="text-muted mb-0">Registro de quien creo, edito, elimino o configuro datos.</p>
            </div>
            <span class="filter-chip"><?= $total ?> eventos</span>
        </div>

        <div class="card filter-panel border-0 mb-3">
            <div class="card-body">
                <form class="row g-3 align-items-end" method="GET">
                    <div class="col-md-2"><label class="form-label small fw-bold">Accion</label><input type="text" name="accion" class="form-control" value="<?= htmlspecialchars($filtros['accion']) ?>"></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">Entidad</label><input type="text" name="entidad" class="form-control" value="<?= htmlspecialchars($filtros['entidad']) ?>"></div>
                    <div class="col-md-3"><label class="form-label small fw-bold">Usuario</label><select name="actor" class="form-select"><option value="">Todos</option><?= renderOptions($usuarios, 'pk_id_login', 'usuario', $filtros['actor']) ?></select></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">Desde</label><input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($filtros['desde']) ?>"></div>
                    <div class="col-md-2"><label class="form-label small fw-bold">Hasta</label><input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($filtros['hasta']) ?>"></div>
                    <div class="col-md-1 d-grid"><button class="btn btn-primary" data-bs-toggle="tooltip" title="Filtrar auditoria" aria-label="Filtrar auditoria"><i class="bi bi-search"></i></button></div>
                    <div class="col-12"><a href="auditoria.php" class="btn btn-sm btn-light border"><i class="bi bi-x-circle me-1"></i> Limpiar filtros</a></div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover table-compact table-sticky align-middle mb-0">
                    <thead class="table-light"><tr><th>Fecha</th><th>Usuario</th><th>Accion</th><th>Entidad</th><th>ID</th><th>Detalle</th><th>IP</th></tr></thead>
                    <tbody>
                        <?php foreach ($eventos as $ev): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($ev['creado_el']))) ?></td>
                                <td><strong><?= htmlspecialchars($ev['usuario'] ?? 'Sistema') ?></strong><div class="text-muted small"><?= htmlspecialchars($ev['perfil'] ?? '') ?></div></td>
                                <td><span class="badge text-bg-primary"><?= htmlspecialchars($ev['accion']) ?></span></td>
                                <td><?= htmlspecialchars($ev['entidad']) ?></td>
                                <td><?= htmlspecialchars($ev['entidad_id'] ?? '---') ?></td>
                                <td style="white-space:normal;min-width:260px"><?= htmlspecialchars($ev['detalle'] ?? '---') ?></td>
                                <td><?= htmlspecialchars($ev['ip_address'] ?? '---') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$eventos): ?><tr><td colspan="7" class="empty-state"><span class="empty-state-icon"><i class="bi bi-search"></i></span><div class="fw-bold text-dark">Sin resultados</div><div>No hay eventos con los filtros aplicados.</div></td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?= renderPagination($pagina, $totalPaginas, fn($p) => '?'.$qs(['p' => $p])) ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/ui_accessibility.js"></script>
</body>
</html>
