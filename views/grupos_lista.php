<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

// Consulta para obtener grupos y contar sus integrantes activos
$sql = "SELECT g.*, (SELECT COUNT(*) FROM empleado e WHERE e.id_grupo = g.pk_id_grupo AND e.esta_empl = 1) as total 
        FROM grupo g ORDER BY nomb_grup ASC";
$grupos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Equipos | AMFURI PERU S.A.C.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .nav-pill-custom { background: white; border: 1px solid #dee2e6; border-radius: 50px; padding: 5px; }
        .nav-link-custom { border-radius: 50px; padding: 8px 25px; transition: 0.3s; color: #6c757d; font-weight: 600; }
        .nav-link-custom.active { background: #0d6efd; color: white; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2); }
        .card-custom { border-radius: 20px; border: none; }
    </style>
</head>
<body>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Configuración Maestro</h1>
                <p class="text-muted small">Gestión de agrupaciones, sedes y especialidades de la empresa.</p>
            </div>
            <button class="btn btn-primary px-4 fw-bold shadow-sm" style="border-radius: 12px;" data-bs-toggle="modal" data-bs-target="#modalGrupo">
                <i class="bi bi-plus-circle me-2"></i>Nuevo Grupo
            </button>
        </div>

        <ul class="nav nav-pills nav-pill-custom mb-4 gap-2 border-0">
            <li class="nav-item">
                <a class="nav-link nav-link-custom" href="sedes_lista.php">
                    <i class="bi bi-geo-alt me-1"></i> Sedes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-custom active" href="grupos_lista.php">
                    <i class="bi bi-people me-1"></i> Grupos Técnicos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-custom" href="cargos_lista.php">
                    <i class="bi bi-briefcase me-1"></i> Cargos / Especialidades
                </a>
            </li>
        </ul>

        <div class="card card-custom shadow-sm bg-white">
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
                                    <?= $g['total'] ?> miembros
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
            <a href="dashboard.php" class="btn btn-link text-decoration-none text-muted fw-bold">
                <i class="bi bi-arrow-left-circle me-2"></i> REGRESAR AL PANEL DE CONTROL
            </a>
        </div>
    </div>

    <div class="modal fade" id="modalGrupo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Registrar Nuevo Grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../models/guardar_grupo.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre del Grupo</label>
                            <input type="text" name="nomb_grup" class="form-control" placeholder="Ej. EQUIPO TÉCNICO A" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4">Guardar Grupo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarGrupo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Editar Nombre del Grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="../models/editar_grupo.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_grupo" id="edit_id_grupo">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nuevo Nombre</label>
                            <input type="text" name="nomb_grup" id="edit_nomb_grup" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Manejo de notificaciones
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('msj')) {
            const msj = urlParams.get('msj');
            if (msj === 'registrado') {
                Swal.fire('¡Éxito!', 'El grupo ha sido registrado correctamente.', 'success');
            } else if (msj === 'editado') {
                Swal.fire('¡Éxito!', 'El grupo ha sido actualizado correctamente.', 'success');
            } else if (msj === 'error') {
                Swal.fire('Error', 'Hubo un problema al procesar la solicitud.', 'error');
            }
            // Limpiar URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        const modalEditar = document.getElementById('modalEditarGrupo');
        if (modalEditar) {
            modalEditar.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                document.getElementById('edit_id_grupo').value = id;
                document.getElementById('edit_nomb_grup').value = nombre;
            });
        }

        function confirmarEliminar(id, nombre) {
            Swal.fire({
                title: '¿Eliminar Grupo?',
                text: `¿Estás seguro de eliminar "${nombre}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../models/eliminar_grupo.php?id=${id}`;
                }
            });
        }
    </script>
</body>
</html>