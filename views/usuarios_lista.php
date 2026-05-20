<?php
require_once '../config/auth.php';
requerirPermiso('usuarios');
require_once '../config/db.php';

$stmt = $pdo->query("SELECT l.*, e.nomb_empl, e.apat_empl FROM login l LEFT JOIN empleado e ON l.id_empleado = e.pk_id_empleado WHERE l.esta_login = 1 ORDER BY l.pk_id_login DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
$empleados_sin_user = $pdo->query("SELECT pk_id_empleado, nomb_empl, apat_empl FROM empleado WHERE pk_id_empleado NOT IN (SELECT id_empleado FROM login WHERE id_empleado IS NOT NULL AND esta_login = 1) AND esta_empl = 1 ORDER BY apat_empl ASC")->fetchAll(PDO::FETCH_ASSOC);
$csrfToken = generarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Usuarios | AMFURI PERU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/responsive_tables.css">
    <style>body{background-color:#f8f9fa}.card{border-radius:14px;border:none}</style>
</head>
<body>
    <div class="container py-5">
        <div class="mb-3"><a href="dashboard.php" class="btn btn-link text-decoration-none text-muted fw-bold"><i class="bi bi-arrow-left-circle me-2"></i> Volver al Panel</a></div>
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h2 class="fw-bold mb-0"><i class="bi bi-shield-lock me-2"></i> Usuarios del Sistema</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario"><i class="bi bi-plus-circle me-2"></i> Nuevo Usuario</button>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive p-3">
                <table class="table table-hover table-compact align-middle">
                    <thead><tr><th>Usuario</th><th>Empleado Asociado</th><th>Perfil</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['usuario']) ?></strong></td>
                                <td><?= $u['nomb_empl'] ? htmlspecialchars($u['nomb_empl'] . ' ' . $u['apat_empl']) : '<span class="text-muted">No vinculado</span>' ?></td>
                                <td><span class="badge text-bg-info"><?= htmlspecialchars($u['perfil']) ?></span></td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="resetPassword(<?= (int) $u['pk_id_login'] ?>, '<?= htmlspecialchars($u['usuario'], ENT_QUOTES) ?>')" title="Reiniciar contrasena"><i class="bi bi-key"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(<?= (int) $u['pk_id_login'] ?>)" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCrearUsuario" tabindex="-1">
        <div class="modal-dialog">
            <form action="../models/guardar_usuario.php" method="POST" class="modal-content">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <div class="modal-header"><h5 class="modal-title">Registrar Nuevo Usuario</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Vincular a Empleado</label>
                        <select name="id_empleado" class="form-select">
                            <option value="">Ninguno (Usuario Externo)</option>
                            <?php foreach ($empleados_sin_user as $e): ?>
                                <option value="<?= $e['pk_id_empleado'] ?>"><?= htmlspecialchars($e['apat_empl'] . ' ' . $e['nomb_empl']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Nombre de Usuario</label><input type="text" name="usuario" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Contrasena</label><input type="password" name="clave" class="form-control" minlength="8" required></div>
                    <div class="mb-3">
                        <label class="form-label">Perfil / Rol</label>
                        <select name="perfil" class="form-select" required>
                            <option value="Administrador">Administrador</option>
                            <option value="RRHH">RRHH</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Visualizador">Visualizador</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary w-100">Crear Acceso</button></div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/ui_feedback.js"></script>
    <script>
        function postAction(url, fields = {}) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            Object.entries({ csrf_token: '<?= $csrfToken ?>', ...fields }).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }

        UIFeedback.fromQuery({
            success: { icon: 'success', title: 'Usuario creado correctamente' },
            deleted: { icon: 'success', title: 'Usuario desactivado' },
            password_reset: { icon: 'success', title: 'Contrasena reiniciada' },
            weak_password: { icon: 'warning', title: 'Contrasena debil' },
            self_delete_error: { icon: 'error', title: 'No puedes eliminar tu propio usuario administrativo' },
            error: { icon: 'error', title: 'No se pudo procesar la solicitud' }
        }, 'status');

        function eliminarUsuario(id) {
            UIFeedback.confirm('Desactivar acceso', 'El usuario ya no podra ingresar al sistema.', { confirmButtonText: 'Si, desactivar' })
                .then((result) => { if (result.isConfirmed) postAction('../models/eliminar_usuario.php', { id }); });
        }

        function resetPassword(id, usuario) {
            Swal.fire({
                title: `Nueva contrasena para ${usuario}`,
                input: 'password',
                inputAttributes: { minlength: 8, autocomplete: 'new-password' },
                showCancelButton: true,
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => (!value || value.length < 8) ? 'Minimo 8 caracteres' : null
            }).then((result) => { if (result.isConfirmed) postAction('../models/reset_usuario_password.php', { id, clave: result.value }); });
        }
    </script>
</body>
</html>
