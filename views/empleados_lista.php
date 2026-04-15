<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
require_once '../config/db.php';

$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Consulta profesional con JOINs para traer nombres en lugar de IDs
$sql = "SELECT e.pk_id_empleado, e.nomb_empl, e.apat_empl, e.dni_empl, 
               c.nomb_carg, g.nomb_grup, d.nomb_dist
        FROM empleado e
        INNER JOIN cargo c ON e.id_cargo = c.pk_id_cargo
        INNER JOIN grupo g ON e.id_grupo = g.pk_id_grupo
        INNER JOIN distrito d ON e.id_distrito = d.pk_id_distrito
        WHERE e.esta_empl = 1";

if ($buscar !== '') {
    $sql .= " AND (e.nomb_empl LIKE :query OR e.dni_empl LIKE :query OR e.apat_empl LIKE :query)";
}

$sql .= " ORDER BY e.pk_id_empleado DESC";

$stmt = $pdo->prepare($sql);
if ($buscar !== '') {
    $stmt->execute([':query' => "%$buscar%"]);
} else {
    $stmt->execute();
}
$empleados = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Personal | Medical Cloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/empleados_lista.css">
</head>

<body>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1" style="letter-spacing: -1px;">Directorio Médicos</h1>
                <p class="text-muted small">Administra la información de todo el personal registrado en el sistema.</p>
            </div>
            <a href="registro_empleado.php" class="btn btn-dark px-4 fw-bold" style="border-radius: 10px;">
                <i class="bi bi-person-plus me-2"></i>Registrar Nuevo
            </a>
        </div>

        <div class="card-cloud mb-4 p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group search-container">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="buscar" class="form-control border-0 shadow-none" placeholder="Buscar por Nombre, DNI o Apellido..." value="<?= htmlspecialchars($buscar) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 12px;">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="card-cloud">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Personal</th>
                            <th>Identificación</th>
                            <th>Especialidad / Cargo</th>
                            <th>Equipo / Sede</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($empleados) > 0): ?>
                            <?php foreach ($empleados as $emp): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($emp['nomb_empl'] . " " . $emp['apat_empl']) ?></div>
                                                <small class="text-muted">ID Sistema: #<?= $emp['pk_id_empleado'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-medium"><?= htmlspecialchars($emp['dni_empl']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-medical bg-cargo">
                                            <i class="bi bi-briefcase"></i> <?= htmlspecialchars($emp['nomb_carg']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge-medical bg-grupo">
                                                <i class="bi bi-people"></i> <?= htmlspecialchars($emp['nomb_grup']) ?>
                                            </span>
                                            <span class="badge-medical bg-sede">
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($emp['nomb_dist']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="asistencia_detalle.php?id=<?= $emp['pk_id_empleado'] ?>" class="btn-action btn-light border text-primary" title="Ver Historial">
                                                <i class="bi bi-calendar3"></i>
                                            </a>
                                            <a href="editar_empleado.php?id=<?= $emp['pk_id_empleado'] ?>" class="btn-action btn-light border text-warning" title="Editar Perfil">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-search fs-1 mb-2 d-block"></i>
                                        <p>No se encontraron resultados para "<strong><?= htmlspecialchars($buscar) ?></strong>"</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <a href="dashboard.php" class="text-decoration-none text-muted small fw-bold">
                <i class="bi bi-arrow-left me-1"></i> VOLVER AL DASHBOARD
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'success') {
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                text: 'La información del personal se guardó correctamente.',
                timer: 2500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    </script>
</body>
</html>