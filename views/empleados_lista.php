<?php
require_once '../config/auth.php';
requerirPermiso('empleados');
require_once '../config/db.php';
require_once '../config/empleados_report.php';
require_once '../config/ui_helpers.php';

$porPagina = 15;
$pagina = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($pagina - 1) * $porPagina;
$filtros = empleadosFiltros($_GET);
$catalogos = empleadosCatalogos($pdo);
$totalRegistros = empleadosTotal($pdo, $filtros);
$totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
    $offset = ($pagina - 1) * $porPagina;
}

$empleados = empleadosConsulta($pdo, $filtros, $porPagina, $offset);
$chips = empleadosChips($catalogos, $filtros);
$csrfToken = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestion de Personal - AMFURI PERU S.A.C</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/empleados_lista.css">
    <link rel="stylesheet" href="../assets/css/ui_common.css">
    <link rel="stylesheet" href="../assets/css/responsive_tables.css">
</head>
<body>
    <div class="container-fluid py-5 px-4">
        <div class="mb-3">
            <a href="dashboard.php" class="btn-back">
                <i class="bi bi-arrow-left-circle-fill me-1"></i> Volver al Panel
            </a>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-0">Directorio de Colaboradores</h2>
                <p class="text-muted mb-0">Gestion de datos maestros, referencias y fotochecks</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="../models/exportar_empleados_excel.php?<?= empleadosQueryString($filtros) ?>" class="btn btn-outline-success bg-white shadow-sm <?= $totalRegistros === 0 ? 'disabled' : '' ?>" data-bs-toggle="tooltip" title="Exportar directorio filtrado a Excel" aria-label="Exportar directorio filtrado a Excel">
                    <i class="bi bi-file-earmark-excel me-1"></i> Excel
                </a>
                <a href="../models/exportar_empleados_pdf.php?<?= empleadosQueryString($filtros) ?>" class="btn btn-outline-danger bg-white shadow-sm <?= $totalRegistros === 0 ? 'disabled' : '' ?>" data-bs-toggle="tooltip" title="Exportar directorio filtrado a PDF" aria-label="Exportar directorio filtrado a PDF">
                    <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                </a>
                <a href="registro_empleado.php" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-person-plus-fill me-2"></i> Nuevo Registro
                </a>
            </div>
        </div>

        <div class="table-container filter-panel p-3 mb-3">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">DNI</label>
                    <input type="text" name="dni" class="form-control only-digits" inputmode="numeric" maxlength="8" value="<?= htmlspecialchars($filtros['dni']) ?>" placeholder="DNI exacto">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted">Nombre, DNI o correo</label>
                    <input type="text" name="buscar" class="form-control" value="<?= htmlspecialchars($filtros['buscar']) ?>" placeholder="Buscar referencia">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Cargo</label>
                    <select name="id_cargo" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($catalogos['cargos'] as $cargo): ?>
                            <option value="<?= $cargo['pk_id_cargo'] ?>" <?= $filtros['id_cargo'] === (int) $cargo['pk_id_cargo'] ? 'selected' : '' ?>><?= htmlspecialchars($cargo['nomb_carg']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Grupo</label>
                    <select name="id_grupo" class="form-select">
                        <option value="">Todos</option>
                        <?php foreach ($catalogos['grupos'] as $grupo): ?>
                            <option value="<?= $grupo['pk_id_grupo'] ?>" <?= $filtros['id_grupo'] === (int) $grupo['pk_id_grupo'] ? 'selected' : '' ?>><?= htmlspecialchars($grupo['nomb_grup']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Sede</label>
                    <select name="id_distrito" class="form-select">
                        <option value="">Todas</option>
                        <?php foreach ($catalogos['sedes'] as $sede): ?>
                            <option value="<?= $sede['pk_id_distrito'] ?>" <?= $filtros['id_distrito'] === (int) $sede['pk_id_distrito'] ? 'selected' : '' ?>><?= htmlspecialchars($sede['nomb_dist']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">Estado documental</label>
                    <select name="estado_doc" class="form-select">
                        <option value="">Todos</option>
                        <option value="completo" <?= $filtros['estado_doc'] === 'completo' ? 'selected' : '' ?>>Completo</option>
                        <option value="sin_foto" <?= $filtros['estado_doc'] === 'sin_foto' ? 'selected' : '' ?>>Sin foto</option>
                        <option value="sin_descriptor" <?= $filtros['estado_doc'] === 'sin_descriptor' ? 'selected' : '' ?>>Sin descriptor facial</option>
                        <option value="sin_sede" <?= $filtros['estado_doc'] === 'sin_sede' ? 'selected' : '' ?>>Sin sede</option>
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                </div>
                <div class="col-12">
                    <a href="empleados_lista.php" class="btn btn-sm btn-light border"><i class="bi bi-x-circle me-1"></i> Limpiar filtros</a>
                </div>
            </form>
        </div>

        <?php if ($chips): ?>
            <div class="mb-3 d-flex flex-wrap gap-2">
                <?php foreach ($chips as $chip): ?>
                    <span class="filter-chip"><?= htmlspecialchars($chip) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="text-muted small">Mostrando <?= count($empleados) ?> de <?= $totalRegistros ?> colaboradores<?= $chips ? ' para: ' . htmlspecialchars(implode(' | ', $chips)) : '' ?>.</p>

        <div class="table-container p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-compact table-sticky">
                    <thead class="table-light">
                        <tr>
                            <th>Foto</th>
                            <th>Datos Personales</th>
                            <th>Cargo / Grupo</th>
                            <th>Contacto</th>
                            <th>Ubicacion</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $emp): ?>
                            <?php
                                $rutaFoto = !empty($emp['foto_empl']) ? "../uploads/fotos/" . $emp['foto_empl'] : "../assets/img/default-user.png";
                                $docCompleto = !empty($emp['foto_empl']) && !empty($emp['rostro_embedding']) && !empty($emp['nomb_dist']);
                            ?>
                            <tr>
                                <td><img src="<?= htmlspecialchars($rutaFoto) ?>" class="img-empleado" alt="Perfil"></td>
                                <td>
                                    <div class="info-principal"><?= htmlspecialchars(trim($emp['apat_empl'] . ' ' . $emp['amat_empl'] . ', ' . $emp['nomb_empl'])) ?></div>
                                    <div class="info-secundaria">DNI: <?= htmlspecialchars($emp['dni_empl']) ?></div>
                                    <span class="badge <?= $docCompleto ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> border">
                                        <?= $docCompleto ? 'Documentacion completa' : 'Documentacion pendiente' ?>
                                    </span><br>
                                    <a class="small text-decoration-none" href="asistencia_detalle.php?dni=<?= urlencode($emp['dni_empl']) ?>">
                                        <i class="bi bi-clock-history"></i> Ver asistencias
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2"><?= htmlspecialchars($emp['nomb_carg'] ?? 'Sin cargo') ?></span>
                                    <div class="info-secundaria mt-1"><?= htmlspecialchars($emp['nomb_grup'] ?? 'Sin grupo') ?></div>
                                </td>
                                <td>
                                    <div class="info-secundaria">
                                        <i class="bi bi-telephone-fill text-success"></i> <?= htmlspecialchars($emp['celu_empl'] ?: '---') ?><br>
                                        <i class="bi bi-envelope-at-fill text-primary"></i> <?= htmlspecialchars($emp['emai_empl'] ?: '---') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="info-secundaria">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> <?= htmlspecialchars(($emp['nomb_dist'] ?? 'Sin sede') . ' - ' . ($emp['dire_empl'] ?: 'S/D')) ?><br>
                                        <i class="bi bi-cake2-fill text-warning"></i> <?= $emp['fnac_empl'] ? date('d/m/Y', strtotime($emp['fnac_empl'])) : '---' ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group action-buttons">
                                        <a href="hoja_vida.php?id=<?= (int) $emp['pk_id_empleado'] ?>" class="btn btn-sm btn-outline-secondary" title="Hoja de vida" data-bs-toggle="tooltip" aria-label="Abrir hoja de vida">
                                            <i class="bi bi-file-earmark-person"></i>
                                        </a>
                                        <a href="editar_empleado.php?id=<?= (int) $emp['pk_id_empleado'] ?>" class="btn btn-sm btn-outline-primary" title="Editar" data-bs-toggle="tooltip" aria-label="Editar colaborador">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button" onclick="confirmarEliminar(<?= (int) $emp['pk_id_empleado'] ?>, '<?= htmlspecialchars($emp['nomb_empl'], ENT_QUOTES) ?>')" class="btn btn-sm btn-outline-danger" title="Dar de baja" data-bs-toggle="tooltip" aria-label="Dar de baja colaborador">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$empleados): ?>
                            <tr><td colspan="6" class="empty-state"><span class="empty-state-icon"><i class="bi bi-search"></i></span><div class="fw-bold text-dark">Sin resultados</div><div>No se encontraron colaboradores con los filtros aplicados.</div></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?= renderPagination($pagina, $totalPaginas, fn($p) => '?' . empleadosQueryString($filtros, ['p' => $p])) ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/ui_feedback.js"></script>
    <script src="../assets/js/ui_accessibility.js"></script>
    <script>
        document.querySelectorAll('.only-digits').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, Number(input.getAttribute('maxlength')) || 20);
            });
        });

        const params = new URLSearchParams(window.location.search);
        if (params.get('msg') === 'desactivado') {
            UIFeedback.success('Colaborador dado de baja');
        } else if (params.get('msg') === 'error') {
            UIFeedback.error('No se pudo completar la accion', 'Revise la solicitud e intentelo nuevamente.');
        }

        function postAction(url, fields = {}) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            Object.entries({ csrf_token: '<?= $csrfToken ?>', ...fields }).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }

        function confirmarEliminar(id, nombre) {
            UIFeedback.confirm('Dar de baja', `El colaborador ${nombre} pasara a estado inactivo.`, {
                confirmButtonText: 'Si, dar de baja',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    postAction('../models/eliminar_empleado.php', { id });
                }
            });
        }
    </script>
</body>

</html>
