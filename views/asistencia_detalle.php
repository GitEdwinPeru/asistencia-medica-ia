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
        $subtitulo = "Reporte general del personal - Hospital Huacho";
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

        // AQUÍ ESTÁ EL CAMBIO: Agregamos e.nomb_empl, e.apat_empl y e.dni_empl
        $stmt_asist = $pdo->prepare("SELECT a.*, e.nomb_empl, e.apat_empl, e.dni_empl, c.nomb_carg, g.nomb_grup 
                                FROM asistencia a 
                                JOIN empleado e ON a.id_empleado = e.pk_id_empleado
                                JOIN cargo c ON e.id_cargo = c.pk_id_cargo
                                JOIN grupo g ON e.id_grupo = g.pk_id_grupo
                                WHERE a.id_empleado = ? ORDER BY a.fech_ingr DESC");
        $stmt_asist->execute([$id_empleado]);
        $asistencias = $stmt_asist->fetchAll(PDO::FETCH_ASSOC);
        // ... después de obtener $asistencias ...
        $total_registros = count($asistencias);
        $total_tardanzas = 0;
        foreach ($asistencias as $a) {
            if ($a['horas_tard'] != "00:00:00") $total_tardanzas++;
        }
        $puntualidad = $total_registros > 0 ? round((($total_registros - $total_tardanzas) / $total_registros) * 100) : 100;
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Medical Cloud | Asistencias</title>
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
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Cloud</a></li>
                        <li class="breadcrumb-item active">Asistencias</li>
                    </ol>
                </nav>
                <h1 class="fw-bold mb-1" style="letter-spacing: -1px;"><?= $titulo_principal ?></h1>
                <p class="text-muted mb-0"><?= $subtitulo ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="../models/exportar_excel.php" class="btn btn-medical">
                    <i class="bi bi-file-earmark-excel me-2 text-success"></i> Reporte Excel
                </a>
                <button onclick="window.print()" class="btn btn-medical">
                    <i class="bi bi-printer me-2 text-primary"></i> Generar PDF
                </button>
            </div>
        </div>

        <div class="card-cloud">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Personal Médico</th>
                            <th>Especialidad / Grupo</th>
                            <th>Registro de Entrada</th>
                            <th>Estado</th>
                            <th class="text-end">Horas Trab.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asistencias as $asist): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($asist['nomb_empl'] . " " . $asist['apat_empl']) ?></div>
                                    <div class="text-muted" style="font-size: 0.8rem;">DNI: <?= $asist['dni_empl'] ?></div>
                                </td>
                                <td>
                                    <div class="badge-cloud bg-blue-soft d-inline-block">
                                        <?= htmlspecialchars($asist['nomb_carg']) ?>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.75rem;">Grupo: <?= htmlspecialchars($asist['nomb_grup']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= date('H:i:s', strtotime($asist['fech_ingr'])) ?></div>
                                    <div class="text-muted small"><?= date('d M, Y', strtotime($asist['fech_ingr'])) ?></div>
                                </td>
                                <td>
                                    <?php if ($asist['horas_tard'] == "00:00:00"): ?>
                                        <span class="badge-cloud bg-success-soft">PUNTUAL</span>
                                    <?php else: ?>
                                        <span class="badge-cloud bg-danger-soft">TARDANZA (<?= $asist['horas_tard'] ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold" style="color: var(--accent-blue);">
                                        <?= $asist['horas_trab'] ?? '--:--' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 no-print text-center">
            <a href="dashboard.php" class="text-decoration-none text-muted small fw-semibold">
                <i class="bi bi-chevron-left"></i> REGRESAR AL PANEL
            </a>
        </div>
    </div>

</body>

</html>