<?php
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';

// Parámetros de paginación
$por_pagina = 10;
$pagina = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = ($pagina - 1) * $por_pagina;

$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// 1. Contar total para paginación
$count_sql = "SELECT COUNT(*) FROM empleado WHERE esta_empl = 1";
if ($buscar !== '') {
    $count_sql .= " AND (nomb_empl LIKE :query OR dni_empl LIKE :query OR apat_empl LIKE :query)";
}
$count_stmt = $pdo->prepare($count_sql);
if ($buscar !== '') {
    $count_stmt->execute([':query' => "%$buscar%"]);
} else {
    $count_stmt->execute();
}
$total_registros = $count_stmt->fetchColumn();
$total_paginas = ceil($total_registros / $por_pagina);

// 2. Consulta con LIMIT y OFFSET
$sql = "SELECT e.pk_id_empleado, e.nomb_empl, e.apat_empl, e.amat_empl, e.dni_empl, 
               e.celu_empl, e.emai_empl, e.dire_empl, e.fnac_empl, e.foto_empl,
               c.nomb_carg, g.nomb_grup, d.nomb_dist
        FROM empleado e
        INNER JOIN cargo c ON e.id_cargo = c.pk_id_cargo
        INNER JOIN grupo g ON e.id_grupo = g.pk_id_grupo
        INNER JOIN distrito d ON e.id_distrito = d.pk_id_distrito
        WHERE e.esta_empl = 1";

if ($buscar !== '') {
    $sql .= " AND (e.nomb_empl LIKE :query OR e.dni_empl LIKE :query OR e.apat_empl LIKE :query)";
}

$sql .= " ORDER BY e.pk_id_empleado DESC LIMIT $por_pagina OFFSET $offset";

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
    <title>Gestión de Personal - AMFURI PERU S.A.C</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/empleados_lista.css">
</head>
<body>
    <div class="container-fluid py-5 px-4">
        <div class="mb-3">
            <a href="dashboard.php" class="btn-back">
                <i class="bi bi-arrow-left-circle-fill me-1"></i> Volver al Panel
            </a>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Directorio de Colaboradores</h2>
                <p class="text-muted">Gestión de datos maestros y fotochecks</p>
            </div>
            <div class="d-flex gap-2">
                <form class="d-flex" action="" method="GET">
                    <input type="text" name="buscar" class="form-control me-2" placeholder="Buscar por DNI o Nombre..." value="<?= htmlspecialchars($buscar) ?>">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
                <a href="registro_empleado.php" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-person-plus-fill me-2"></i> Nuevo Registro
                </a>
            </div>
        </div>

        <div class="table-container p-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Foto</th>
                            <th>Datos Personales</th>
                            <th>Cargo / Grupo</th>
                            <th>Contacto y Correo</th>
                            <th>Ubicación y Nacimiento</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $emp): ?>
                            <tr>
                                <td>
                                    <?php
                                    $ruta_foto = !empty($emp['foto_empl']) ? "../uploads/fotos/" . $emp['foto_empl'] : "../assets/img/default-user.png";
                                    ?>
                                    <img src="<?= $ruta_foto ?>" class="img-empleado" alt="Perfil">
                                </td>
                                <td>
                                    <div class="info-principal"><?= htmlspecialchars($emp['apat_empl'] . ' ' . $emp['amat_empl'] . ', ' . $emp['nomb_empl']) ?></div>
                                    <div class="info-secundaria">DNI: <?= $emp['dni_empl'] ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2"><?= htmlspecialchars($emp['nomb_carg']) ?></span>
                                    <div class="info-secundaria mt-1"><?= htmlspecialchars($emp['nomb_grup']) ?></div>
                                </td>
                                <td>
                                    <div class="info-secundaria">
                                        <i class="bi bi-telephone-fill text-success"></i> <?= $emp['celu_empl'] ?: '---' ?><br>
                                        <i class="bi bi-envelope-at-fill text-primary"></i> <?= htmlspecialchars($emp['emai_empl'] ?: '---') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="info-secundaria">
                                        <i class="bi bi-geo-alt-fill text-danger"></i> <?= htmlspecialchars($emp['nomb_dist'] . " - " . ($emp['dire_empl'] ?: 'S/D')) ?><br>
                                        <i class="bi bi-cake2-fill text-warning"></i> <?= $emp['fnac_empl'] ? date('d/m/Y', strtotime($emp['fnac_empl'])) : '---' ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="editar_empleado.php?id=<?= $emp['pk_id_empleado'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button onclick="confirmarEliminar(<?= $emp['pk_id_empleado'] ?>, '<?= $emp['nomb_empl'] ?>')" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= $pagina - 1 ?>&buscar=<?= urlencode($buscar) ?>">Anterior</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?= $i == $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="?p=<?= $i ?>&buscar=<?= urlencode($buscar) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= $pagina + 1 ?>&buscar=<?= urlencode($buscar) ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmarEliminar(id, nombre) {
            Swal.fire({
                title: '¿Dar de baja?',
                text: `El colaborador ${nombre} pasará a estado inactivo.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Sí, dar de baja',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../models/eliminar_empleado.php?id=${id}`;
                }
            })
        }
    </script>
</body>

</html>