<?php
require_once '../config/auth.php';
requerirPermiso('empleados');
require_once '../config/db.php';
$id = $_GET['id'] ?? null;
if (!$id) { header("Location: empleados_lista.php"); exit(); }
// 1. Consulta para traer los datos actuales del empleado
$stmt = $pdo->prepare("SELECT * FROM empleado WHERE pk_id_empleado = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$emp) { header("Location: empleados_lista.php"); exit(); }
// 2. Cargar catálogos para los selectores
$cargos = $pdo->query("SELECT * FROM cargo WHERE esta_carg = 1 ORDER BY nomb_carg ASC")->fetchAll();
$grupos = $pdo->query("SELECT * FROM grupo WHERE esta_grup = 1 ORDER BY nomb_grup ASC")->fetchAll();
$distritos = $pdo->query("SELECT * FROM distrito WHERE esta_dist = 1 ORDER BY nomb_dist ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Colaborador | AMFURI PERU S.A.C.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/ui_common.css">
    <style>
        body { background-color: #f4f7f6; }
        .img-edit { width: 120px; height: 120px; object-fit: cover; border-radius: 15px; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="mb-3">
            <a href="empleados_lista.php" class="btn btn-link text-decoration-none text-muted fw-bold">
                <i class="bi bi-arrow-left-circle me-2"></i> Volver a la lista
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary fw-bold"><i class="bi bi-pencil-square me-2"></i> Editar Información de Personal</h5>
            </div>
            <div class="card-body p-4">
                <form id="formEditarEmpleado" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_empleado" value="<?= (int) $emp['pk_id_empleado'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    
                    <div class="row">
                        <div class="col-lg-3 text-center border-end">
                            <label class="form-label d-block fw-bold small mb-3">Foto de Fotocheck</label>
                            <?php 
                                $ruta_foto = !empty($emp['foto_empl']) ? "../uploads/fotos/" . $emp['foto_empl'] : "../assets/img/default-user.png";
                            ?>
                            <img src="<?= htmlspecialchars($ruta_foto) ?>" class="img-edit mb-3" id="preview-foto" alt="Foto actual del colaborador">
                            <input type="file" name="foto_perfil" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this)" aria-describedby="fotoHelp">
                            <p id="fotoHelp" class="text-muted small mt-2">JPG, PNG o WEBP. Maximo 2 MB. Suba una foto solo si desea cambiarla.</p>
                        </div>

                        <div class="col-lg-9 ps-lg-4">
                            <h6 class="text-uppercase text-muted small fw-bold mb-3">Datos Maestros</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Nombres</label>
                                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($emp['nomb_empl']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ap. Paterno</label>
                                    <input type="text" name="apellido_pat" class="form-control" value="<?= htmlspecialchars($emp['apat_empl']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ap. Materno</label>
                                    <input type="text" name="apellido_mat" class="form-control" value="<?= htmlspecialchars($emp['amat_empl']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">DNI</label>
                                    <input type="text" name="dni" class="form-control" value="<?= htmlspecialchars($emp['dni_empl']) ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nac" class="form-control" value="<?= htmlspecialchars($emp['fnac_empl'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Género</label>
                                    <select name="genero" class="form-select">
                                        <option value="M" <?= $emp['gene_empl'] == 'M' ? 'selected' : '' ?>>Masculino</option>
                                        <option value="F" <?= $emp['gene_empl'] == 'F' ? 'selected' : '' ?>>Femenino</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="text-uppercase text-muted small fw-bold mb-3">Contacto y Asignación</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Teléfono / Celular</label>
                                    <input type="text" name="telefono" class="form-control only-digits" inputmode="numeric" maxlength="9" pattern="[0-9]{9}" value="<?= htmlspecialchars($emp['celu_empl'] ?? '') ?>">
                                    <div class="invalid-feedback">Ingrese exactamente 9 digitos numericos.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Correo Electrónico</label>
                                    <input type="email" name="emai_empl" class="form-control" value="<?= htmlspecialchars($emp['emai_empl']) ?>">
                                    <div class="invalid-feedback">Ingrese un correo valido.</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Dirección Domiciliaria</label>
                                    <input type="text" name="dire_empl" class="form-control" value="<?= htmlspecialchars($emp['dire_empl']) ?>">
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">Distrito / Sede</label>
                                    <select name="id_distrito" class="form-select" required>
                                        <?php foreach ($distritos as $d): ?>
                                            <option value="<?= (int) $d['pk_id_distrito'] ?>" <?= $emp['id_distrito'] == $d['pk_id_distrito'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($d['nomb_dist']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Especialidad / Cargo</label>
                                    <select name="id_cargo" class="form-select" required>
                                        <?php foreach ($cargos as $c): ?>
                                            <option value="<?= (int) $c['pk_id_cargo'] ?>" <?= $emp['id_cargo'] == $c['pk_id_cargo'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['nomb_carg']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Grupo de Trabajo</label>
                                    <select name="id_grupo" class="form-select" required>
                                        <?php foreach ($grupos as $g): ?>
                                            <option value="<?= (int) $g['pk_id_grupo'] ?>" <?= $emp['id_grupo'] == $g['pk_id_grupo'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($g['nomb_grup']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <h6 class="text-uppercase text-muted small fw-bold mb-3">Información Adicional</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label">Observaciones</label>
                                    <textarea name="obsv_empl" class="form-control" rows="2"><?= htmlspecialchars($emp['obsv_empl'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary fw-bold p-3">
                                    <i class="bi bi-save me-2"></i> Guardar Cambios Actualizados
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/ui_feedback.js"></script>
    <script src="../assets/js/ui_accessibility.js"></script>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-foto').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.querySelectorAll('.only-digits').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, Number(input.getAttribute('maxlength')) || 20);
                input.classList.toggle('is-invalid', input.value !== '' && !/^\d{9}$/.test(input.value));
            });
        });

        function validarEdicion() {
            const form = document.getElementById('formEditarEmpleado');
            let valid = true;
            form.querySelectorAll('.is-invalid').forEach((input) => input.classList.remove('is-invalid'));

            form.querySelectorAll('[required]').forEach((input) => {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    valid = false;
                }
            });

            const telefono = form.querySelector('input[name="telefono"]');
            if (telefono.value !== '' && !/^\d{9}$/.test(telefono.value)) {
                telefono.classList.add('is-invalid');
                valid = false;
            }

            const email = form.querySelector('input[name="emai_empl"]');
            if (email.value !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                email.classList.add('is-invalid');
                valid = false;
            }

            if (!valid) UIFeedback.warning('Revise los datos', 'Corrija los campos marcados antes de guardar.');
            return valid;
        }

        document.getElementById('formEditarEmpleado').onsubmit = function(e) {
            e.preventDefault();
            if (!validarEdicion()) return;
            const formData = new FormData(this);
            
            fetch('../models/actualizar_empleado.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    UIFeedback.success(data.message).then(() => {
                        window.location.href = 'empleados_lista.php';
                    });
                } else {
                    UIFeedback.error('Error', data.message);
                }
            }).catch(() => UIFeedback.error('Error', 'Fallo de comunicacion con el servidor.'));
        };
    </script>
</body>
</html>
