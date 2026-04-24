<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
require_once '../config/db.php';

$id_empleado = isset($_GET['id']) ? intval($_GET['id']) : 0;
$es_general = ($id_empleado === 0);

try {
    if ($es_general) {
        $titulo_principal = "Panel de Control de Asistencias";
        $subtitulo = "Reporte general del personal - AMFURI PERU S.A.C.";
        
        // Consulta unificada para mostrar entrada, salida y horas totales
        $sql = "SELECT e.dni_empl, e.nomb_empl, e.apat_empl, c.nomb_carg, g.nomb_grup,
                       a.fech_ingr, a.fech_sali, a.horas_tard, a.horas_trab
                FROM asistencia a
                INNER JOIN empleado e ON a.id_empleado = e.pk_id_empleado
                INNER JOIN cargo c ON e.id_cargo = c.pk_id_cargo
                INNER JOIN grupo g ON e.id_grupo = g.pk_id_grupo
                ORDER BY a.fech_ingr DESC";
        $asistencias = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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

        $stmt_asist = $pdo->prepare("SELECT a.*, e.nomb_empl, e.apat_empl, e.dni_empl, c.nomb_carg, g.nomb_grup 
                                FROM asistencia a 
                                JOIN empleado e ON a.id_empleado = e.pk_id_empleado
                                JOIN cargo c ON e.id_cargo = c.pk_id_cargo
                                JOIN grupo g ON e.id_grupo = g.pk_id_grupo
                                WHERE a.id_empleado = ? ORDER BY a.fech_ingr DESC");
        $stmt_asist->execute([$id_empleado]);
        $asistencias = $stmt_asist->fetchAll(PDO::FETCH_ASSOC);
        
        $total_registros = count($asistencias);
        $total_tardanzas = 0;
        foreach ($asistencias as $a) {
            if ($a['horas_tard'] != "00:00:00") $total_tardanzas++;
        }
    }
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
        <div class="d-flex justify-content-between align-items-end mb-5 no-print">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Panel</a></li>
                        <li class="breadcrumb-item active">Asistencias</li>
                    </ol>
                </nav>
                <h1 class="fw-bold mb-1" style="letter-spacing: -1px;"><?= $titulo_principal ?></h1>
                <p class="text-muted mb-0"><?= $subtitulo ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="../models/exportar_excel.php" class="btn btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-2"></i> Excel
                </a>
                <button onclick="window.print()" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-2"></i> PDF
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Colaborador</th>
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
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($asist['nomb_empl'] . " " . $asist['apat_empl']) ?></div>
                                    <div class="text-muted small">DNI: <?= $asist['dni_empl'] ?></div>
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

</body>
</html>