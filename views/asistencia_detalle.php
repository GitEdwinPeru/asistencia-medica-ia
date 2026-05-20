<?php
require_once '../config/auth.php';
requerirPermiso('asistencia');
require_once '../config/db.php';
require_once '../config/asistencia_report.php';

$filtros = asistenciaFiltros($_GET);
$porPagina = 15;
$pagina = max(1, intval($_GET['p'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

try {
    $catalogos = asistenciaCatalogos($pdo);
    $totalRegistros = asistenciaTotal($pdo, $filtros);
    $totalPaginas = (int) ceil($totalRegistros / $porPagina);
    $asistencias = asistenciaConsulta($pdo, $filtros, $porPagina, $offset);
    $resumen = asistenciaResumen($pdo, $filtros);
    $chips = asistenciaFiltrosActivos($pdo, $filtros);
    $empleadoReferencia = asistenciaEmpleadoReferencia($pdo, $filtros);
} catch (PDOException $e) {
    die("Error al cargar asistencias.");
}

$tituloPrincipal = $empleadoReferencia
    ? trim($empleadoReferencia['nomb_empl'] . ' ' . $empleadoReferencia['apat_empl'])
    : 'Panel de Control de Asistencias';
$subtitulo = $empleadoReferencia
    ? 'Historial individual de marcaciones - DNI ' . $empleadoReferencia['dni_empl']
    : 'Reporte general del personal - AMFURI PERU S.A.C.';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencias | AMFURI PERU S.A.C.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/ui_common.css">
    <link rel="stylesheet" href="../assets/css/asistencia_detalle.css">
    <style>
        @media (max-width: 576px) {
            .main-content { padding: 18px 12px !important; max-width: 100%; overflow-x: hidden; }
            .main-content > .d-flex:first-child { align-items: flex-start !important; flex-direction: column !important; }
            .main-content > .d-flex:first-child > .d-flex { display: grid !important; gap: 8px; grid-template-columns: 1fr 1fr; width: 100%; }
            .main-content > .d-flex:first-child .dropdown { grid-column: 1 / -1; }
            .main-content > .d-flex:first-child .btn,
            .main-content > .d-flex:first-child .dropdown-toggle { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-end mb-4 no-print">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Panel</a></li>
                        <li class="breadcrumb-item active">Asistencias</li>
                    </ol>
                </nav>
                <h1 class="fw-bold mb-1"><?= htmlspecialchars($tituloPrincipal) ?></h1>
                <p class="text-muted mb-0"><?= htmlspecialchars($subtitulo) ?></p>
                <?php if ($empleadoReferencia): ?>
                    <a href="hoja_vida.php?id=<?= intval($empleadoReferencia['pk_id_empleado']) ?>" class="btn btn-sm btn-outline-primary mt-2">
                        <i class="bi bi-file-earmark-person"></i> Ver Hoja de Vida
                    </a>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-funnel me-1"></i> Presets
                    </button>
                    <ul class="dropdown-menu">
                        <?php foreach (['hoy' => 'Hoy', 'ayer' => 'Ayer', 'semana' => 'Ultima Semana', 'mes' => 'Ultimo Mes'] as $preset => $label): ?>
                            <li><a class="dropdown-item" href="?<?= asistenciaQueryString($filtros, ['preset' => $preset, 'p' => 1]) ?>"><?= $label ?></a></li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="asistencia_detalle.php">Ver Todo</a></li>
                    </ul>
                </div>
                <button class="btn btn-outline-success" onclick="exportarExcel()" <?= $totalRegistros === 0 ? 'disabled' : '' ?> data-bs-toggle="tooltip" title="Exportar resultados a Excel" aria-label="Exportar resultados a Excel">
                    <i class="bi bi-file-earmark-excel me-2"></i> Excel
                </button>
                <button class="btn btn-outline-danger" onclick="exportarPDF()" <?= $totalRegistros === 0 ? 'disabled' : '' ?> data-bs-toggle="tooltip" title="Exportar resultados a PDF" aria-label="Exportar resultados a PDF">
                    <i class="bi bi-file-earmark-pdf me-2"></i> PDF
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4 no-print">
            <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><span class="text-muted small">Registros</span><strong class="fs-4"><?= $resumen['total'] ?></strong></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><span class="text-muted small">Puntuales</span><strong class="fs-4 text-success"><?= $resumen['puntuales'] ?></strong></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><span class="text-muted small">Tardanzas</span><strong class="fs-4 text-danger"><?= $resumen['tardanzas'] ?></strong></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm p-3"><span class="text-muted small">Sin salida / Horas</span><strong class="fs-6"><?= $resumen['sin_salida'] ?> / <?= htmlspecialchars($resumen['horas_total']) ?></strong></div></div>
        </div>

        <div class="card filter-panel border-0 mb-4 no-print">
            <div class="card-body py-3">
                <form class="row g-3 align-items-end" method="GET" id="formFiltros">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">DNI</label>
                        <input type="text" name="dni" maxlength="8" inputmode="numeric" class="form-control" value="<?= htmlspecialchars($filtros['dni']) ?>" placeholder="DNI exacto">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Nombre o DNI</label>
                        <input type="text" name="buscar" class="form-control" value="<?= htmlspecialchars($filtros['buscar']) ?>" placeholder="Buscar referencia...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($filtros['fecha_inicio']) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($filtros['fecha_fin']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="">Todos</option>
                            <option value="puntual" <?= $filtros['estado'] === 'puntual' ? 'selected' : '' ?>>Puntual</option>
                            <option value="tardanza" <?= $filtros['estado'] === 'tardanza' ? 'selected' : '' ?>>Tardanza</option>
                            <option value="sin_salida" <?= $filtros['estado'] === 'sin_salida' ? 'selected' : '' ?>>Sin salida</option>
                            <option value="con_salida" <?= $filtros['estado'] === 'con_salida' ? 'selected' : '' ?>>Con salida</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Sede</label>
                        <select name="id_distrito" class="form-select">
                            <option value="">Todas</option>
                            <?php foreach ($catalogos['sedes'] as $sede): ?>
                                <option value="<?= $sede['pk_id_distrito'] ?>" <?= $filtros['id_distrito'] === (int) $sede['pk_id_distrito'] ? 'selected' : '' ?>><?= htmlspecialchars($sede['nomb_dist']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Cargo</label>
                        <select name="id_cargo" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($catalogos['cargos'] as $cargo): ?>
                                <option value="<?= $cargo['pk_id_cargo'] ?>" <?= $filtros['id_cargo'] === (int) $cargo['pk_id_cargo'] ? 'selected' : '' ?>><?= htmlspecialchars($cargo['nomb_carg']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Grupo</label>
                        <select name="id_grupo" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($catalogos['grupos'] as $grupo): ?>
                                <option value="<?= $grupo['pk_id_grupo'] ?>" <?= $filtros['id_grupo'] === (int) $grupo['pk_id_grupo'] ? 'selected' : '' ?>><?= htmlspecialchars($grupo['nomb_grup']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                        <a href="asistencia_detalle.php" class="btn btn-light border">
                            <i class="bi bi-x-circle me-1"></i> Limpiar filtros
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($chips): ?>
            <div class="mb-3 no-print d-flex flex-wrap gap-2">
                <?php foreach ($chips as $chip): ?>
                    <span class="filter-chip"><?= htmlspecialchars($chip) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="text-muted small no-print">
            Mostrando <?= count($asistencias) ?> de <?= $totalRegistros ?> registros<?= $chips ? ' para: ' . htmlspecialchars(implode(' | ', $chips)) : '' ?>.
        </p>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Colaborador</th>
                            <th>Sede del Dia</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Estado / Tardanza</th>
                            <th class="text-end">Horas Trabajadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asistencias as $asist): ?>
                            <tr>
                                <td>
                                    <a href="?<?= asistenciaQueryString(['id' => (int) $asist['id_empleado'], 'dni' => '', 'buscar' => '', 'id_distrito' => 0, 'id_cargo' => 0, 'id_grupo' => 0, 'estado' => '', 'fecha_inicio' => '', 'fecha_fin' => '', 'preset' => '']) ?>" class="text-decoration-none">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars(trim(($asist['nomb_empl'] ?? '') . ' ' . ($asist['apat_empl'] ?? ''))) ?></div>
                                        <div class="text-muted small">DNI: <?= htmlspecialchars($asist['dni_empl'] ?? '---') ?></div>
                                    </a>
                                </td>
                                <td><span class="badge bg-light text-dark border"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($asist['sede_marcacion'] ?? 'No especificada') ?></span></td>
                                <td><div class="text-primary fw-semibold"><?= date('H:i:s', strtotime($asist['fech_ingr'])) ?></div><div class="text-muted small"><?= date('d/m/Y', strtotime($asist['fech_ingr'])) ?></div></td>
                                <td>
                                    <?php if (!empty($asist['fech_sali'])): ?>
                                        <div class="text-danger fw-semibold"><?= date('H:i:s', strtotime($asist['fech_sali'])) ?></div>
                                        <div class="text-muted small"><?= date('d/m/Y', strtotime($asist['fech_sali'])) ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Sin salida</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($asist['horas_tard'] ?? '00:00:00') === '00:00:00'): ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success border border-success">PUNTUAL</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger">TARDANZA (<?= htmlspecialchars($asist['horas_tard']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-dark"><?= !empty($asist['horas_trab']) ? htmlspecialchars($asist['horas_trab']) : '--:--:--' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($asistencias)): ?>
                            <tr><td colspan="6" class="empty-state"><span class="empty-state-icon"><i class="bi bi-search"></i></span><div class="fw-bold text-dark">Sin resultados</div><div>No se encontraron registros con los filtros aplicados.</div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPaginas > 1): ?>
            <nav class="mt-4 no-print">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= asistenciaQueryString($filtros, ['p' => $pagina - 1]) ?>">Anterior</a></li>
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>"><a class="page-link" href="?<?= asistenciaQueryString($filtros, ['p' => $i]) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>"><a class="page-link" href="?<?= asistenciaQueryString($filtros, ['p' => $pagina + 1]) ?>">Siguiente</a></li>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="mt-4 no-print text-center">
            <a href="dashboard.php" class="text-decoration-none text-muted small fw-semibold"><i class="bi bi-chevron-left"></i> REGRESAR AL DASHBOARD</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/ui_feedback.js"></script>
    <script src="../assets/js/ui_accessibility.js"></script>
    <script>
        function exportarExcel() {
            const params = new URLSearchParams(window.location.search);
            UIFeedback.success('Preparando Excel', { icon: 'info', title: 'Preparando Excel', timer: 900 });
            setTimeout(() => { window.location.href = `../models/exportar_excel.php?${params.toString()}`; }, 250);
        }

        function exportarPDF() {
            const params = new URLSearchParams(window.location.search);
            UIFeedback.success('Preparando PDF', { icon: 'info', title: 'Preparando PDF', timer: 900 });
            setTimeout(() => { window.location.href = `../models/exportar_pdf.php?${params.toString()}`; }, 250);
        }
    </script>
</body>
</html>
