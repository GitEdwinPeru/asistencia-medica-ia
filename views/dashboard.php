<?php
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';

$total_empleados = (int) $pdo->query("SELECT COUNT(*) FROM empleado WHERE esta_empl = 1")->fetchColumn();
$total_hoy = (int) $pdo->query("SELECT COUNT(DISTINCT id_empleado) FROM asistencia WHERE DATE(fech_ingr) = CURDATE()")->fetchColumn();
$tardanzas_hoy = (int) $pdo->query("SELECT COUNT(*) FROM asistencia WHERE DATE(fech_ingr) = CURDATE() AND horas_tard > '00:00:00'")->fetchColumn();
$sin_salida_hoy = (int) $pdo->query("SELECT COUNT(*) FROM asistencia WHERE DATE(fech_ingr) = CURDATE() AND fech_sali IS NULL")->fetchColumn();
$presentes_hoy = $total_hoy;
$ausentes_hoy = max(0, $total_empleados - $presentes_hoy);
$puntuales = max(0, $presentes_hoy - $tardanzas_hoy);

$res_grupos = $pdo->query("SELECT COALESCE(g.nomb_grup, 'Sin grupo') AS nombre, COUNT(e.pk_id_empleado) AS total
    FROM empleado e
    LEFT JOIN grupo g ON e.id_grupo = g.pk_id_grupo
    WHERE e.esta_empl = 1
    GROUP BY nombre
    ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

$res_sedes_hoy = $pdo->query("SELECT COALESCE(d.nomb_dist, 'Sin sede') AS nombre, COUNT(a.id_asistencia) AS total
    FROM asistencia a
    LEFT JOIN distrito d ON a.id_distrito = d.pk_id_distrito
    WHERE DATE(a.fech_ingr) = CURDATE()
    GROUP BY nombre
    ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

$res_tardanzas_sede = $pdo->query("SELECT COALESCE(d.nomb_dist, 'Sin sede') AS nombre, COUNT(a.id_asistencia) AS total
    FROM asistencia a
    LEFT JOIN distrito d ON a.id_distrito = d.pk_id_distrito
    WHERE DATE(a.fech_ingr) = CURDATE() AND a.horas_tard > '00:00:00'
    GROUP BY nombre
    ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

$alertas = [
    'sin_descriptor' => (int) $pdo->query("SELECT COUNT(*) FROM empleado WHERE esta_empl = 1 AND (rostro_embedding IS NULL OR rostro_embedding = '')")->fetchColumn(),
    'sin_foto' => (int) $pdo->query("SELECT COUNT(*) FROM empleado WHERE esta_empl = 1 AND (foto_empl IS NULL OR foto_empl = '')")->fetchColumn(),
    'sin_sede' => (int) $pdo->query("SELECT COUNT(*) FROM empleado WHERE esta_empl = 1 AND (id_distrito IS NULL OR id_distrito = 0)")->fetchColumn(),
    'sin_salida' => $sin_salida_hoy,
];

$config = $pdo->query("SELECT hora_entrada, tolerancia_minutos FROM asistencia_config WHERE activo = 1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC)
    ?: ['hora_entrada' => '08:15:00', 'tolerancia_minutos' => 0];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header text-center py-4 px-3">
            <img src="../assets/img/logo.png" alt="Logo Medical" class="img-fluid sidebar-logo mb-2">
        </div>

        <nav class="nav flex-column mt-3">
            <a class="nav-link active" href="#"><i class="bi bi-house-door"></i> Dashboard</a>
            <?php if (tienePermiso('empleados')): ?><a class="nav-link" href="registro_empleado.php"><i class="bi bi-person-plus"></i> Registrar Personal</a><?php endif; ?>
            <?php if (tienePermiso('asistencia')): ?><a class="nav-link" href="asistencia_detalle.php?general=true"><i class="bi bi-calendar2-check"></i> Asistencias</a><?php endif; ?>
            <?php if (tienePermiso('catalogos')): ?><a class="nav-link" href="sedes_lista.php"><i class="bi bi-building"></i> Sedes/Grupos</a><?php endif; ?>
            <?php if (tienePermiso('empleados')): ?><a href="empleados_lista.php" class="nav-link d-flex align-items-center gap-2"><i class="bi bi-people-fill"></i><span>Lista de Empleados</span></a><?php endif; ?>

            <?php if (tienePermiso('usuarios')): ?>
            <a class="nav-link" href="usuarios_lista.php"><i class="bi bi-shield-lock"></i> Gestion Usuarios</a>
            <?php endif; ?>
            <?php if (tienePermiso('configuracion')): ?>
            <a class="nav-link" href="asistencia_config.php"><i class="bi bi-clock-history"></i> Horario Asistencia</a>
            <?php endif; ?>
            <?php if (tienePermiso('auditoria')): ?>
            <a class="nav-link" href="auditoria.php"><i class="bi bi-activity"></i> Auditoria</a>
            <?php endif; ?>
            <?php if (tienePermiso('hoja_vida')): ?>
            <a class="nav-link ms-3 small text-muted" href="hoja_vida.php"><i class="bi bi-file-earmark-person"></i> Hoja de Vida</a>
            <?php endif; ?>
            <a class="nav-link ms-3 small text-muted" href="mi_password.php"><i class="bi bi-key"></i> Mi Contrasena</a>

            <hr class="mx-3 my-4">
            <a class="nav-link text-danger mt-auto" href="../controllers/logout.php">
                <i class="bi bi-box-arrow-left"></i> Salir del Sistema
            </a>
        </nav>
    </div>

    <div class="main-wrapper">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h2 class="header-title mb-1">Resumen de Operaciones</h2>
                <p class="text-muted mb-0">Hora entrada: <?= htmlspecialchars(substr($config['hora_entrada'], 0, 5)) ?> | Tolerancia: <?= (int) $config['tolerancia_minutos'] ?> min</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (esAdministrador()): ?>
                    <a href="asistencia_config.php" class="btn btn-outline-primary bg-white shadow-sm"><i class="bi bi-sliders me-1"></i> Configurar horario</a>
                <?php endif; ?>
                <span class="badge bg-white text-dark border p-2 px-3 shadow-sm">
                    <i class="bi bi-calendar-event me-2 text-primary"></i> <?= date('d/m/Y') ?>
                </span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card-stat"><div class="icon-box bg-soft-blue"><i class="bi bi-people"></i></div><div><p class="text-muted small mb-0 fw-bold">Personal Activo</p><h3 class="mb-0 fw-bold"><?= $total_empleados ?></h3></div></div></div>
            <div class="col-md-3"><div class="card-stat"><div class="icon-box bg-soft-green"><i class="bi bi-person-check"></i></div><div><p class="text-muted small mb-0 fw-bold">Presentes Hoy</p><h3 class="mb-0 fw-bold"><?= $presentes_hoy ?></h3></div></div></div>
            <div class="col-md-3"><div class="card-stat"><div class="icon-box bg-soft-red"><i class="bi bi-person-dash"></i></div><div><p class="text-muted small mb-0 fw-bold">Ausentes Hoy</p><h3 class="mb-0 fw-bold"><?= $ausentes_hoy ?></h3></div></div></div>
            <div class="col-md-3"><div class="card-stat"><div class="icon-box bg-soft-yellow"><i class="bi bi-door-open"></i></div><div><p class="text-muted small mb-0 fw-bold">Sin Salida</p><h3 class="mb-0 fw-bold"><?= $sin_salida_hoy ?></h3></div></div></div>
        </div>

        <?php if (array_sum($alertas) > 0): ?>
            <div class="row g-3 mb-4">
                <div class="col-12"><h6 class="text-uppercase small fw-bold text-muted mb-0">Alertas administrativas</h6></div>
                <div class="col-md-3"><a href="empleados_sin_rostro.php" class="alert-card text-decoration-none"><i class="bi bi-person-bounding-box"></i><span><?= $alertas['sin_descriptor'] ?> sin descriptor facial</span></a></div>
                <div class="col-md-3"><a href="empleados_lista.php" class="alert-card text-decoration-none"><i class="bi bi-image"></i><span><?= $alertas['sin_foto'] ?> sin foto</span></a></div>
                <div class="col-md-3"><a href="empleados_lista.php" class="alert-card text-decoration-none"><i class="bi bi-geo-alt"></i><span><?= $alertas['sin_sede'] ?> sin sede base</span></a></div>
                <div class="col-md-3"><a href="asistencia_detalle.php?estado=sin_salida&preset=hoy" class="alert-card text-decoration-none"><i class="bi bi-exclamation-circle"></i><span><?= $alertas['sin_salida'] ?> marcaciones sin salida</span></a></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6"><div class="chart-container shadow-sm border-0"><h6 class="fw-bold mb-4 text-muted text-uppercase small">Distribucion por Grupos</h6><canvas id="chartGrupos"></canvas></div></div>
            <div class="col-lg-6"><div class="chart-container shadow-sm border-0"><h6 class="fw-bold mb-4 text-muted text-uppercase small">Asistencias por Sede Hoy</h6><canvas id="chartSedes"></canvas></div></div>
            <div class="col-lg-6"><div class="chart-container shadow-sm border-0"><h6 class="fw-bold mb-4 text-muted text-uppercase small">Estado de Asistencia Hoy</h6><canvas id="chartEstado"></canvas></div></div>
            <div class="col-lg-6"><div class="chart-container shadow-sm border-0"><h6 class="fw-bold mb-4 text-muted text-uppercase small">Tardanzas por Sede Hoy</h6><canvas id="chartTardanzasSede"></canvas></div></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/ui_feedback.js"></script>
    <script>
        UIFeedback.fromQuery({
            sin_permiso: { icon: 'warning', title: 'No tienes permiso para abrir ese modulo' },
            password_changed: { icon: 'success', title: 'Contrasena actualizada' }
        });

        const palette = ['#2563eb', '#16a34a', '#dc2626', '#f59e0b', '#7c3aed', '#0891b2', '#db2777', '#4b5563'];
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } }, x: { grid: { display: false } } }
        };

        new Chart(document.getElementById('chartGrupos'), {
            type: 'bar',
            data: { labels: <?= json_encode(array_column($res_grupos, 'nombre')) ?>, datasets: [{ label: 'Personal', data: <?= json_encode(array_map('intval', array_column($res_grupos, 'total'))) ?>, backgroundColor: '#2563eb', borderRadius: 8 }] },
            options: commonOptions
        });

        new Chart(document.getElementById('chartSedes'), {
            type: 'bar',
            data: { labels: <?= json_encode(array_column($res_sedes_hoy, 'nombre')) ?>, datasets: [{ label: 'Marcaciones', data: <?= json_encode(array_map('intval', array_column($res_sedes_hoy, 'total'))) ?>, backgroundColor: '#16a34a', borderRadius: 8 }] },
            options: commonOptions
        });

        new Chart(document.getElementById('chartEstado'), {
            type: 'doughnut',
            data: { labels: ['Puntuales', 'Tardanzas', 'Ausentes', 'Sin salida'], datasets: [{ data: <?= json_encode([$puntuales, $tardanzas_hoy, $ausentes_hoy, $sin_salida_hoy]) ?>, backgroundColor: ['#16a34a', '#dc2626', '#64748b', '#f59e0b'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true } } }, cutout: '62%' }
        });

        new Chart(document.getElementById('chartTardanzasSede'), {
            type: 'bar',
            data: { labels: <?= json_encode(array_column($res_tardanzas_sede, 'nombre')) ?>, datasets: [{ label: 'Tardanzas', data: <?= json_encode(array_map('intval', array_column($res_tardanzas_sede, 'total'))) ?>, backgroundColor: '#dc2626', borderRadius: 8 }] },
            options: commonOptions
        });
    </script>
</body>
</html>
