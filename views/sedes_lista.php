<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_id'])) { header("Location: index.php"); exit(); }
require_once '../config/db.php';

// Consulta para Sedes y conteo de personal activo por sede
$sql = "SELECT d.*, (SELECT COUNT(*) FROM empleado e WHERE e.id_distrito = d.pk_id_distrito AND e.esta_empl = 1) as total 
        FROM distrito d ORDER BY nomb_dist ASC";
$sedes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sedes Hospitalarias | Medical Cloud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/grupos_lista.css">
</head>
<body class="bg-light">

    <div class="main-content container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1" style="letter-spacing: -1px;">Sedes y Distritos</h1>
                <p class="text-muted small">Gestión de ubicaciones y centros de atención médica.</p>
            </div>
            <button class="btn btn-primary px-4 fw-bold shadow-sm" style="border-radius: 10px;" data-bs-toggle="modal" data-bs-target="#modalSede">
                <i class="bi bi-geo-alt me-2"></i>Nueva Sede
            </button>
        </div>

        <ul class="nav nav-tabs border-0 mb-4 gap-2">
            <li class="nav-item">
                <a class="nav-link active rounded-pill border shadow-sm px-4 bg-primary text-white" href="sedes_lista.php">Sedes (Distritos)</a>
            </li>
            <li class="nav-item">
                <a class="nav-link rounded-pill border shadow-sm px-4 bg-white text-secondary" href="grupos_lista.php">Grupos Técnicos</a>
            </li>
        </ul>

        <div class="card shadow-sm border-0" style="border-radius: 20px;">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold">NOMBRE DE LA SEDE / DISTRITO</th>
                            <th class="text-center text-muted small fw-bold">PERSONAL ASIGNADO</th>
                            <th class="text-end pe-4 text-muted small fw-bold">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($sedes as $s): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background-color: #e0f2fe;">
                                        <i class="bi bi-building-check"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['nomb_dist']) ?></div>
                                        <div class="text-muted small">Sucursal ID: #<?= $s['pk_id_distrito'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-2" style="background-color: #eef2ff;">
                                    <i class="bi bi-people me-1"></i> <?= $s['total'] ?> empleados
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-white border shadow-sm rounded-3 me-1 btn-edit-sede" 
                                        data-id="<?= $s['pk_id_distrito'] ?>" 
                                        data-nombre="<?= htmlspecialchars($s['nomb_dist']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarSede">
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
                                
                                <button class="btn btn-sm btn-white border shadow-sm rounded-3" 
                                        onclick="confirmarEliminarSede(<?= $s['pk_id_distrito'] ?>, '<?= htmlspecialchars($s['nomb_dist']) ?>')">
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

    <div class="modal fade" id="modalSede" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <form action="../models/guardar_sede.php" method="POST">
                    <div class="modal-body p-4">
                        <h5 class="fw-bold mb-4">Registrar Nueva Sede</h5>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre del Distrito / Sede</label>
                            <input type="text" name="nomb_dist" class="form-control form-control-lg border-2" placeholder="Ej. Miraflores" required style="border-radius: 12px;">
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-light w-100 fw-bold py-2" data-bs-dismiss="modal" style="border-radius: 12px;">Cancelar</button>
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2" style="border-radius: 12px;">Crear Sede</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarSede" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <form action="../models/editar_sede.php" method="POST">
                    <input type="hidden" name="id_distrito" id="edit_id_distrito">
                    <div class="modal-body p-4">
                        <h5 class="fw-bold mb-4">Modificar Sede</h5>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre del Distrito</label>
                            <input type="text" name="nomb_dist" id="edit_nomb_dist" class="form-control form-control-lg border-2" required style="border-radius: 12px;">
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-light w-100 fw-bold py-2" data-bs-dismiss="modal" style="border-radius: 12px;">Cancelar</button>
                            <button type="submit" class="btn btn-warning w-100 fw-bold py-2 text-white" style="border-radius: 12px;">Guardar Cambios</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Cargar datos en modal editar
        document.querySelectorAll('.btn-edit-sede').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id_distrito').value = btn.getAttribute('data-id');
                document.getElementById('edit_nomb_dist').value = btn.getAttribute('data-nombre');
            });
        });

        // Alerta de eliminación
        function confirmarEliminarSede(id, nombre) {
            Swal.fire({
                title: '¿Eliminar sede?',
                text: `Se eliminará la sede "${nombre}". Asegúrese de que no tenga personal asignado.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, eliminar sede',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                customClass: { popup: 'rounded-4' }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../models/eliminar_sede.php?id=${id}`;
                }
            });
        }

        // Notificaciones de respuesta
        const urlParams = new URLSearchParams(window.location.search);
        const msj = urlParams.get('msj');
        if (msj === 'registrado') Swal.fire({ icon: 'success', title: '¡Sede Creada!', timer: 2000, showConfirmButton: false });
        if (msj === 'editado') Swal.fire({ icon: 'success', title: '¡Sede Actualizada!', timer: 2000, showConfirmButton: false });
        if (msj === 'eliminado') Swal.fire({ icon: 'success', title: '¡Sede Eliminada!', timer: 2000, showConfirmButton: false });
    </script>
</body>
</html>