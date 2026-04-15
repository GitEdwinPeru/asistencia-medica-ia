<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_id'])) { header("Location: index.php"); exit(); }
require_once '../config/db.php';

$sql = "SELECT g.*, (SELECT COUNT(*) FROM empleado e WHERE e.id_grupo = g.pk_id_grupo AND e.esta_empl = 1) as total 
        FROM grupo g ORDER BY nomb_grup ASC";
$grupos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Grupos Técnicos | Medical Cloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/grupos_lista.css">
</head>
<body class="bg-light">

    <div class="main-content container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1" style="letter-spacing: -1px;">Gestión de Equipos</h1>
                <p class="text-muted small">Organización de grupos técnicos y guardias médicas.</p>
            </div>
            <button class="btn btn-primary px-4 fw-bold shadow-sm" style="border-radius: 10px;" data-bs-toggle="modal" data-bs-target="#modalGrupo">
                <i class="bi bi-plus-circle me-2"></i>Nuevo Grupo
            </button>
        </div>

        <ul class="nav nav-tabs border-0 mb-4 gap-2">
            <li class="nav-item">
                <a class="nav-link rounded-pill border shadow-sm px-4 bg-white text-secondary" href="sedes_lista.php">Sedes (Distritos)</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active rounded-pill border shadow-sm px-4 bg-primary text-white" href="grupos_lista.php">Grupos Técnicos</a>
            </li>
        </ul>

        <div class="card shadow-sm border-0" style="border-radius: 20px;">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold">NOMBRE DEL GRUPO</th>
                            <th class="text-center text-muted small fw-bold">INTEGRANTES</th>
                            <th class="text-end pe-4 text-muted small fw-bold">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($grupos as $g): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-microsoft-teams"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($g['nomb_grup']) ?></div>
                                        <div class="text-muted small">ID: #<?= $g['pk_id_grupo'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                    <i class="bi bi-person-check me-1"></i> <?= $g['total'] ?> miembros
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-white border shadow-sm rounded-3 me-1 btn-edit" 
                                        data-id="<?= $g['pk_id_grupo'] ?>" 
                                        data-nombre="<?= htmlspecialchars($g['nomb_grup']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarGrupo">
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
                                <button class="btn btn-sm btn-white border shadow-sm rounded-3" 
                                        onclick="confirmarEliminar(<?= $g['pk_id_grupo'] ?>, '<?= htmlspecialchars($g['nomb_grup']) ?>')">
                                    <i class="bi bi-trash text-danger"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="dashboard.php" class="btn btn-link text-decoration-none text-muted fw-bold back-link">
                <i class="bi bi-arrow-left-circle me-2"></i> REGRESAR AL PANEL DE CONTROL
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Lógica de Modales y SweetAlert2 aquí...
    </script>
</body>
</html>