<?php
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';
// --- CONSULTAS DE DATOS ---
$total_empleados = $pdo->query("SELECT COUNT(*) FROM empleado WHERE esta_empl = 1")->fetchColumn();
$total_hoy = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE DATE(fech_ingr) = CURDATE()")->fetchColumn();
$tardanzas_hoy = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE DATE(fech_ingr) = CURDATE() AND horas_tard > '00:00:00'")->fetchColumn();

// Datos para Gráfico de Grupos
$sql_grupos = "SELECT g.nomb_grup, COUNT(e.pk_id_empleado) as total 
               FROM grupo g
               LEFT JOIN empleado e ON g.pk_id_grupo = e.id_grupo AND e.esta_empl = 1
               GROUP BY g.nomb_grup";
$res_grupos = $pdo->query($sql_grupos)->fetchAll(PDO::FETCH_ASSOC);
$labels_grupos = array_column($res_grupos, 'nomb_grup');
$datos_grupos = array_column($res_grupos, 'total');

// Datos para Gráfico de Asistencia (Doughnut)
$puntuales = $total_hoy - $tardanzas_hoy;
$datos_asistencia = [$puntuales, $tardanzas_hoy];
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
            <a class="nav-link" href="registro_empleado.php"><i class="bi bi-person-plus"></i> Registrar Personal</a>
            <a class="nav-link" href="asistencia_detalle.php?general=true"><i class="bi bi-calendar2-check"></i> Asistencias</a>
            <a class="nav-link" href="sedes_lista.php"><i class="bi bi-building"></i> Sedes/Grupos</a>
            
            <a href="empleados_lista.php" class="nav-link d-flex align-items-center gap-2 <?= basename($_SERVER['PHP_SELF']) == 'empleados_lista.php' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Lista de Empleados</span>
            </a>

            <?php if (esAdministrador()): ?>
            <a class="nav-link" href="usuarios_lista.php"><i class="bi bi-shield-lock"></i> Gestión Usuarios</a>
            <a class="nav-link ms-3 small text-muted" href="hoja_vida.php"><i class="bi bi-file-earmark-person"></i> Hoja de Vida</a>
            <?php endif; ?>
            
            <hr class="mx-3 my-4">
            
            <a class="nav-link text-danger mt-auto" href="../controllers/logout.php">
                <i class="bi bi-box-arrow-left"></i> Salir del Sistema
            </a>
        </nav>
    </div>

    <div class="main-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="header-title mb-1">Resumen de Operaciones</h2>
            </div>
            <div class="text-end">
                <span class="badge bg-white text-dark border p-2 px-3 shadow-sm">
                    <i class="bi bi-calendar-event me-2 text-primary"></i> <?= date('d/m/Y') ?>
                </span>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card-stat">
                    <div class="icon-box bg-soft-blue"><i class="bi bi-people"></i></div>
                    <div>
                        <p class="text-muted small mb-0 fw-bold">Personal Activo</p>
                        <h3 class="mb-0 fw-bold"><?= $total_empleados ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-stat">
                    <div class="icon-box bg-soft-green"><i class="bi bi-check2-circle"></i></div>
                    <div>
                        <p class="text-muted small mb-0 fw-bold">Asistencias Hoy</p>
                        <h3 class="mb-0 fw-bold"><?= $total_hoy ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-stat">
                    <div class="icon-box bg-soft-red"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <p class="text-muted small mb-0 fw-bold">Tardanzas Hoy</p>
                        <h3 class="mb-0 fw-bold"><?= $tardanzas_hoy ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="chart-container shadow-sm border-0">
                    <h6 class="fw-bold mb-4 text-muted text-uppercase small" style="letter-spacing: 1px;">Distribución por Grupos</h6>
                    <canvas id="chartGrupos"></canvas>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="chart-container shadow-sm border-0">
                    <h6 class="fw-bold mb-4 text-muted text-uppercase small" style="letter-spacing: 1px;">Estado de Puntualidad</h6>
                    <canvas id="chartAsistencia"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Configuración Gráfico de Grupos
        new Chart(document.getElementById('chartGrupos'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels_grupos) ?>,
                datasets: [{
                    label: 'Personal',
                    data: <?= json_encode($datos_grupos) ?>,
                    backgroundColor: '#3498db',
                    borderRadius: 8,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Configuración Gráfico de Asistencia
        new Chart(document.getElementById('chartAsistencia'), {
            type: 'doughnut',
            data: {
                labels: ['Puntuales', 'Tardanzas'],
                datasets: [{
                    data: <?= json_encode($datos_asistencia) ?>,
                    backgroundColor: ['#2ecc71', '#e74c3c'],
                    hoverOffset: 4,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, usePointStyle: true }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>