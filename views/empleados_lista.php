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
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Empleados | AMFURI PERU S.A.C</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --accent-blue: #0d6efd; }
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .table-container { background: white; border-radius: 15px; overflow: hidden; }
        .table thead { background-color: #f1f4f9; }
        .btn-action { padding: 5px 10px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark">Gestión de Personal</h3>
            <a href="registro_empleado.php" class="btn btn-primary shadow-sm">
                <i class="bi bi-person-plus-fill me-2"></i>Nuevo Empleado
            </a>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar por DNI o Apellidos..." value="<?= htmlspecialchars($buscar) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100">Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-container shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">DNI</th>
                            <th>Nombres y Apellidos</th>
                            <th>Cargo</th>
                            <th>Sede / Distrito</th>
                            <th class="text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($empleados) > 0): ?>
                            <?php foreach ($empleados as $emp): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= $emp['dni_empl'] ?></td>
                                    <td><?= htmlspecialchars($emp['nomb_empl'] . ' ' . $emp['apat_empl']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($emp['nomb_carg']) ?></span></td>
                                    <td><?= htmlspecialchars($emp['nomb_dist']) ?></td>
                                    <td class="text-end pe-4">
                                        <a href="editar_empleado.php?id=<?= $emp['pk_id_empleado'] ?>" class="btn btn-sm btn-outline-primary btn-action" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <button onclick="confirmarEliminar(<?= $emp['pk_id_empleado'] ?>, '<?= htmlspecialchars($emp['nomb_empl']) ?>')" class="btn btn-sm btn-outline-danger btn-action" title="Eliminar">
                                            <i class="bi bi-trash3"></i>
                                        </button>
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
        // Alerta de confirmación para eliminar
        function confirmarEliminar(id, nombre) {
            Swal.fire({
                title: '¿Dar de baja?',
                text: `El empleado "${nombre}" pasará a estado inactivo.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, dar de baja',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../models/eliminar_empleado.php?id=${id}`;
                }
            })
        }

        // Notificación de éxito al actualizar o eliminar
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');

        if (status === 'success' || msg === 'eliminado') {
            Swal.fire({
                icon: 'success',
                title: '¡Operación exitosa!',
                text: msg === 'eliminado' ? 'El personal ha sido dado de baja.' : 'Información actualizada correctamente.',
                timer: 2500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    </script>
</body>
</html>