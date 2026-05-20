<?php
require_once '../config/auth.php';
requerirPermiso('empleados');
require_once '../config/db.php';
require_once '../config/ui_helpers.php';

$porPagina = 15;
$pagina = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

$total = (int) $pdo->query("SELECT COUNT(*) FROM empleado WHERE esta_empl = 1 AND (rostro_embedding IS NULL OR rostro_embedding = '')")->fetchColumn();
$totalPaginas = max(1, (int) ceil($total / $porPagina));

$stmt = $pdo->prepare("SELECT e.pk_id_empleado, e.dni_empl, e.nomb_empl, e.apat_empl, e.amat_empl, e.foto_empl, c.nomb_carg, d.nomb_dist
    FROM empleado e
    LEFT JOIN cargo c ON e.id_cargo = c.pk_id_cargo
    LEFT JOIN distrito d ON e.id_distrito = d.pk_id_distrito
    WHERE e.esta_empl = 1 AND (e.rostro_embedding IS NULL OR e.rostro_embedding = '')
    ORDER BY e.apat_empl ASC
    LIMIT $porPagina OFFSET $offset");
$stmt->execute();
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados sin Rostro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/responsive_tables.css">
</head>
<body class="bg-light">
    <div class="container-fluid py-5 px-4">
        <div class="mb-3">
            <a href="dashboard.php" class="btn btn-link text-decoration-none text-muted fw-bold">
                <i class="bi bi-arrow-left-circle me-2"></i> Volver al Dashboard
            </a>
        </div>

        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
            <div>
                <h2 class="fw-bold mb-0"><i class="bi bi-person-x text-warning me-2"></i> Empleados sin rostro registrado</h2>
                <p class="text-muted mb-0">Colaboradores activos que todavía no pueden marcar por reconocimiento facial.</p>
            </div>
            <a href="registro_empleado.php" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Registrar nuevo rostro</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover table-compact align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>DNI</th><th>Empleado</th><th>Cargo</th><th>Sede</th><th class="text-end">Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $emp): ?>
                            <tr>
                                <td><?= htmlspecialchars($emp['dni_empl']) ?></td>
                                <td><strong><?= htmlspecialchars(trim($emp['apat_empl'] . ' ' . $emp['amat_empl'] . ', ' . $emp['nomb_empl'])) ?></strong></td>
                                <td><?= htmlspecialchars($emp['nomb_carg'] ?? 'Sin cargo') ?></td>
                                <td><?= htmlspecialchars($emp['nomb_dist'] ?? 'Sin sede') ?></td>
                                <td class="text-end">
                                    <a href="editar_empleado.php?id=<?= (int) $emp['pk_id_empleado'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i> Editar ficha</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$empleados): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Todos los empleados activos tienen descriptor facial.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?= renderPagination($pagina, $totalPaginas, fn($p) => '?p=' . $p) ?>
    </div>
</body>
</html>
