<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

// Obtener usuarios
$stmt = $pdo->query("SELECT l.*, e.nomb_empl, e.apat_empl FROM login l LEFT JOIN empleado e ON l.id_empleado = e.pk_id_empleado");
$usuarios = $stmt->fetchAll();

// Obtener empleados que no tienen usuario (para el modal de crear)
$empleados_sin_user = $pdo->query("SELECT pk_id_empleado, nomb_empl, apat_empl FROM empleado WHERE pk_id_empleado NOT IN (SELECT id_empleado FROM login WHERE id_empleado IS NOT NULL) AND esta_empl = 1")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios | AMFURI PERU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="bi bi-shield-lock me-2"></i> Usuarios del Sistema</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                <i class="bi bi-plus-circle me-2"></i> Nuevo Usuario
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive p-3">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Empleado Asociado</th>
                            <th>Perfil</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['usuario']) ?></strong></td>
                            <td><?= $u['nomb_empl'] ? htmlspecialchars($u['nomb_empl'] . ' ' . $u['apat_empl']) : '<span class="text-muted">No vinculado</span>' ?></td>
                            <td><span class="badge bg-info"><?= $u['perfil'] ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(<?= $u['pk_id_login'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Crear -->
    <div class="modal fade" id="modalCrearUsuario" tabindex="-1">
        <div class="modal-dialog">
            <form action="../models/guardar_usuario.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Vincular a Empleado</label>
                        <select name="id_empleado" class="form-select">
                            <option value="">Ninguno (Usuario Externo)</option>
                            <?php foreach ($empleados_sin_user as $e): ?>
                                <option value="<?= $e['pk_id_empleado'] ?>"><?= htmlspecialchars($e['nomb_empl'] . ' ' . $e['apat_empl']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre de Usuario</label>
                        <input type="text" name="usuario" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="clave" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Perfil / Rol</label>
                        <select name="perfil" class="form-select" required>
                            <option value="Administrador">Administrador</option>
                            <option value="Visualizador">Visualizador</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100">Crear Acceso</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function eliminarUsuario(id) {
            Swal.fire({
                title: '¿Eliminar acceso?',
                text: "El usuario ya no podrá ingresar al sistema.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `../models/eliminar_usuario.php?id=${id}`;
                }
            });
        }
    </script>
</body>
</html>
