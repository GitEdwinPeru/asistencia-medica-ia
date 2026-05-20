<?php
require_once '../config/auth.php';
requerirPermiso('catalogos');
require_once '../config/db.php';

// Consulta para obtener cargos y contar cuántos empleados tienen asignado cada cargo
$sql = "SELECT c.*, (SELECT COUNT(*) FROM empleado e WHERE e.id_cargo = c.pk_id_cargo AND e.esta_empl = 1) as total 
        FROM cargo c WHERE c.esta_carg = 1 ORDER BY nomb_carg ASC";
$cargos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargos y Especialidades | AMFURI PERU S.A.C.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/ui_common.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .nav-pill-custom { background: white; border: 1px solid #dee2e6; border-radius: 50px; padding: 5px; }
        .nav-link-custom { border-radius: 50px; padding: 8px 25px; transition: 0.3s; color: #6c757d; font-weight: 600; text-decoration: none; }
        .nav-link-custom.active { background: #0d6efd; color: white; box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2); }
        .card-custom { border-radius: 20px; border: none; }
    </style>
</head>
<body>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1">Configuración Maestro</h1>
                <p class="text-muted small">Gestión de especialidades y rangos de personal.</p>
            </div>
            <button class="btn btn-primary px-4 fw-bold shadow-sm" style="border-radius: 12px;" data-bs-toggle="modal" data-bs-target="#modalNuevoCargo">
                <i class="bi bi-plus-circle me-2"></i>Nuevo Cargo
            </button>
        </div>

        <ul class="nav nav-pills nav-pill-custom mb-4 gap-2 border-0">
            <li class="nav-item">
                <a class="nav-link nav-link-custom" href="sedes_lista.php">
                    <i class="bi bi-geo-alt me-1"></i> Sedes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-custom" href="grupos_lista.php">
                    <i class="bi bi-people me-1"></i> Grupos Técnicos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-custom active" href="cargos_lista.php">
                    <i class="bi bi-briefcase me-1"></i> Cargos / Especialidades
                </a>
            </li>
        </ul>

        <div class="card card-custom shadow-sm bg-white">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold">DESCRIPCIÓN DEL CARGO</th>
                            <th class="text-center text-muted small fw-bold">PERSONAL ACTIVO</th>
                            <th class="text-end pe-4 text-muted small fw-bold">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cargos)): ?>
                        <tr>
                            <td colspan="3" class="text-center py-5">
                                <div class="mx-auto mb-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                                    <i class="bi bi-briefcase text-muted fs-4"></i>
                                </div>
                                <div class="fw-bold text-dark">No hay cargos registrados</div>
                                <div class="text-muted small">Crea el primer cargo para clasificar al personal.</div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach($cargos as $c): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-award-fill"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($c['nomb_carg']) ?></div>
                                        <div class="text-muted small">ID: #<?= $c['pk_id_cargo'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
                                    <?= $c['total'] ?> empleados
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-white border shadow-sm rounded-3 me-1 btn-edit btn-icon" 
                                        data-id="<?= $c['pk_id_cargo'] ?>" 
                                        data-nombre="<?= htmlspecialchars($c['nomb_carg']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarCargo"
                                        title="Editar cargo" aria-label="Editar cargo">
                                    <i class="bi bi-pencil text-primary"></i>
                                </button>
                                <button class="btn btn-sm btn-white border shadow-sm rounded-3 btn-icon" 
                                        data-bs-toggle="tooltip" title="Desactivar cargo" aria-label="Desactivar cargo"
                                        onclick="confirmarEliminarCargo(<?= $c['pk_id_cargo'] ?>, '<?= htmlspecialchars($c['nomb_carg']) ?>')">
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

    <div class="modal fade" id="modalNuevoCargo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Registrar Nuevo Cargo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="../models/guardar_cargo.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre del Cargo / Especialidad</label>
                            <input type="text" name="nomb_carg" class="form-control" placeholder="Ej. Técnico en Logística" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarCargo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Editar Cargo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="../models/editar_cargo.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <div class="modal-body">
                        <input type="hidden" name="id_cargo" id="edit_id_cargo">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Descripción Actualizada</label>
                            <input type="text" name="nomb_carg" id="edit_nomb_carg" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-4">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/ui_feedback.js"></script>
    <script src="../assets/js/ui_accessibility.js"></script>
    <script>
        function postAction(url, fields = {}) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            Object.entries({ csrf_token: '<?= generarTokenCSRF() ?>', ...fields }).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }

        // Lógica para cargar datos en el modal de edición de Cargo
        const modalEditarCargo = document.getElementById('modalEditarCargo');
        if (modalEditarCargo) {
            modalEditarCargo.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');

                document.getElementById('edit_id_cargo').value = id;
                document.getElementById('edit_nomb_carg').value = nombre;
            });
        }

        function confirmarEliminarCargo(id, nombre) {
            UIFeedback.confirm(
                'Eliminar cargo',
                `Se desactivara "${nombre}". Si tiene personal activo, el sistema bloqueara la accion para proteger la informacion.`,
                {
                icon: 'warning',
                confirmButtonText: 'Si, desactivar',
                cancelButtonText: 'Cancelar'
                }
            ).then((result) => {
                if (result.isConfirmed) postAction('../models/eliminar_cargo.php', { id });
            });
        }

        UIFeedback.fromQuery({
            editado: { icon: 'success', title: 'Cargo actualizado' },
            guardado: { icon: 'success', title: 'Cargo registrado' },
            eliminado: { icon: 'success', title: 'Cargo desactivado' },
            error_integridad: { icon: 'error', title: 'Cargo en uso', text: 'Reasigna o desactiva primero al personal vinculado.' },
            error: { icon: 'error', title: 'No se pudo procesar la solicitud' }
        });
    </script>
</body>
</html>
