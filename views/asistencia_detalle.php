<?php
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';

$id_empleado = isset($_GET['id']) ? intval($_GET['id']) : 0;
$es_general = ($id_empleado === 0);

// Filtros de fecha
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$preset = $_GET['preset'] ?? '';

// Lógica de presets
if ($preset) {
    $fecha_fin = date('Y-m-d');
    switch ($preset) {
        case 'hoy': $fecha_inicio = date('Y-m-d'); break;
        case 'ayer': $fecha_inicio = date('Y-m-d', strtotime('-1 day')); $fecha_fin = $fecha_inicio; break;
        case 'semana': $fecha_inicio = date('Y-m-d', strtotime('-7 days')); break;
        case 'mes': $fecha_inicio = date('Y-m-d', strtotime('-30 days')); break;
    }
}

try {
    $where = [];
    $params = [];

    if (!$es_general) {
        $where[] = "a.id_empleado = ?";
        $params[] = $id_empleado;
    }

    if ($fecha_inicio) {
        $where[] = "DATE(a.fech_ingr) >= ?";
        $params[] = $fecha_inicio;
    }
    if ($fecha_fin) {
        $where[] = "DATE(a.fech_ingr) <= ?";
        $params[] = $fecha_fin;
    }

    $where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

    if ($es_general) {
        $titulo_principal = "Panel de Control de Asistencias";
        $subtitulo = "Reporte general del personal - AMFURI PERU S.A.C.";
        
        $sql = "SELECT e.dni_empl, e.nomb_empl, e.apat_empl, c.nomb_carg, g.nomb_grup,
                       a.fech_ingr, a.fech_sali, a.horas_tard, a.horas_trab, d.nomb_dist as sede_marcacion,
                       a.id_empleado
                FROM asistencia a
                INNER JOIN empleado e ON a.id_empleado = e.pk_id_empleado
                INNER JOIN cargo c ON e.id_cargo = c.pk_id_cargo
                INNER JOIN grupo g ON e.id_grupo = g.pk_id_grupo
                LEFT JOIN distrito d ON a.id_distrito = d.pk_id_distrito
                $where_sql
                ORDER BY a.fech_ingr DESC";
    } else {
        $stmt_emp = $pdo->prepare("SELECT e.*, c.nomb_carg FROM empleado e JOIN cargo c ON e.id_cargo = c.pk_id_cargo WHERE pk_id_empleado = ?");
        $stmt_emp->execute([$id_empleado]);
        $empleado = $stmt_emp->fetch();
        
        if (!$empleado) {
            header("Location: empleados_lista.php");
            exit();
        }

        $titulo_principal = $empleado['nomb_empl'] . " " . $empleado['apat_empl'];
        $subtitulo = "Historial individual de marcaciones";

        $sql = "SELECT a.*, e.nomb_empl, e.apat_empl, e.dni_empl, c.nomb_carg, g.nomb_grup, d.nomb_dist as sede_marcacion
                FROM asistencia a 
                JOIN empleado e ON a.id_empleado = e.pk_id_empleado
                JOIN cargo c ON e.id_cargo = c.pk_id_cargo
                JOIN grupo g ON e.id_grupo = g.pk_id_grupo
                LEFT JOIN distrito d ON a.id_distrito = d.pk_id_distrito
                $where_sql
                ORDER BY a.fech_ingr DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Asistencias | AMFURI PERU S.A.C.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/asistencia_detalle.css">
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
                <h1 class="fw-bold mb-1" style="letter-spacing: -1px;"><?= $titulo_principal ?></h1>
                <p class="text-muted mb-0"><?= $subtitulo ?></p>
                <?php if (!$es_general): ?>
                    <a href="hoja_vida.php?id=<?= $id_empleado ?>" class="btn btn-sm btn-outline-primary mt-2">
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
                        <li><a class="dropdown-menu-item dropdown-item" href="?preset=hoy&id=<?= $id_empleado ?>">Hoy</a></li>
                        <li><a class="dropdown-menu-item dropdown-item" href="?preset=ayer&id=<?= $id_empleado ?>">Ayer</a></li>
                        <li><a class="dropdown-menu-item dropdown-item" href="?preset=semana&id=<?= $id_empleado ?>">Última Semana</a></li>
                        <li><a class="dropdown-menu-item dropdown-item" href="?preset=mes&id=<?= $id_empleado ?>">Último Mes</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-menu-item dropdown-item" href="?id=<?= $id_empleado ?>">Ver Todo</a></li>
                    </ul>
                </div>
                <button class="btn btn-outline-success" onclick="exportarExcel()">
                    <i class="bi bi-file-earmark-excel me-2"></i> Excel
                </button>
                <button class="btn btn-outline-danger" onclick="exportarPDF()">
                    <i class="bi bi-file-earmark-pdf me-2"></i> PDF
                </button>
            </div>
        </div>

        <!-- Filtro Personalizado -->
        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body py-3">
                <form class="row g-3 align-items-end" method="GET">
                    <input type="hidden" name="id" value="<?= $id_empleado ?>">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-search me-1"></i> Filtrar
                        </button>
                        <a href="?id=<?= $id_empleado ?>" class="btn btn-light border">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Colaborador</th>
                            <th>Sede del Día</th>
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
                                    <a href="?id=<?= $asist['id_empleado'] ?>" class="text-decoration-none">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars(($asist['nomb_empl'] ?? '') . " " . ($asist['apat_empl'] ?? '')) ?></div>
                                        <div class="text-muted small">DNI: <?= htmlspecialchars($asist['dni_empl'] ?? '---') ?></div>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                        <?= htmlspecialchars($asist['sede_marcacion'] ?? 'No especificada') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-primary fw-semibold">
                                        <i class="bi bi-box-arrow-in-right me-1"></i>
                                        <?= date('H:i:s', strtotime($asist['fech_ingr'])) ?>
                                    </div>
                                    <div class="text-muted small"><?= date('d/m/Y', strtotime($asist['fech_ingr'])) ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($asist['fech_sali'])): ?>
                                        <div class="text-danger fw-semibold">
                                            <i class="bi bi-box-arrow-right me-1"></i>
                                            <?= date('H:i:s', strtotime($asist['fech_sali'])) ?>
                                        </div>
                                        <div class="text-muted small"><?= date('d/m/Y', strtotime($asist['fech_sali'])) ?></div>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-clock-history me-1"></i> En labores
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($asist['horas_tard'] == "00:00:00"): ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success border border-success">PUNTUAL</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger">
                                            TARDANZA (<?= $asist['horas_tard'] ?>)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    <?= !empty($asist['horas_trab']) ? $asist['horas_trab'] : '--:--:--' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($asistencias)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No se encontraron registros de asistencia.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 no-print text-center">
            <a href="dashboard.php" class="text-decoration-none text-muted small fw-semibold">
                <i class="bi bi-chevron-left"></i> REGRESAR AL DASHBOARD
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportarExcel() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = `../models/exportar_excel.php?${params.toString()}`;
        }

        function exportarPDF() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = `../models/exportar_pdf.php?${params.toString()}`;
        }
    </script>
</body>
</html>