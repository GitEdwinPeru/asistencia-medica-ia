<?php
require_once '../config/auth.php';
requerirPermiso('empleados');
require_once '../config/db.php';

$cargos = $pdo->query("SELECT * FROM cargo WHERE esta_carg = 1 ORDER BY nomb_carg ASC")->fetchAll();
$grupos = $pdo->query("SELECT * FROM grupo WHERE esta_grup = 1 ORDER BY nomb_grup ASC")->fetchAll();
$distritos = $pdo->query("SELECT * FROM distrito WHERE esta_dist = 1 ORDER BY nomb_dist ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Personal - AMFURI PERU S.A.C.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/ui_common.css">
    <link rel="stylesheet" href="../assets/css/registro_empleado.css">
</head>
<body>
    <div class="container py-5">
        <div class="mb-3">
            <a href="dashboard.php" class="btn btn-link text-decoration-none text-muted fw-bold">
                <i class="bi bi-arrow-left-circle me-2"></i> Volver al Panel Principal
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header py-3 bg-white text-primary fw-bold">
                <i class="bi bi-person-plus-fill me-2"></i> Registro de Nuevo Personal
            </div>
            
            <div class="card-body p-4">
                <form id="formRegistroEmpleado" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <div class="row">
                        <div class="col-lg-4 text-center border-end pe-lg-4">
                            <h6 class="section-title">Validación Biométrica</h6>
                            <div id="contenedor-camara" class="mb-3 position-relative">
                                <video id="video" autoplay muted style="width: 100%; border-radius: 8px; background:#000;"></video>
                                <canvas id="overlay" style="position: absolute; top:0; left:0; width: 100%;"></canvas>
                            </div>
                            <div id="status-ia" class="small text-muted mb-3">
                                <i class="bi bi-info-circle me-1"></i> Use la cámara para el descriptor facial.
                            </div>
                            
                            <div class="mt-3 text-start">
                                <label class="form-label fw-bold small">Foto para Fotocheck</label>
                                <input type="file" name="foto_perfil" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" aria-describedby="fotoHelp">
                                <div id="fotoHelp" class="form-text">JPG, PNG o WEBP. Maximo 2 MB.</div>
                            </div>
                        </div>

                        <div class="col-lg-8 ps-lg-4">
                            <h6 class="section-title">Información Personal</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4"><label class="form-label">Nombres</label><input type="text" name="nombre" class="form-control" required></div>
                                <div class="col-md-4"><label class="form-label">Ap. Paterno</label><input type="text" name="apellido_pat" class="form-control" required></div>
                                <div class="col-md-4"><label class="form-label">Ap. Materno</label><input type="text" name="apellido_mat" class="form-control" required></div>
                                <div class="col-md-4">
                                    <label class="form-label">DNI</label>
                                    <input type="text" name="dni" id="dni_registro" class="form-control only-digits" inputmode="numeric" maxlength="8" pattern="[0-9]{8}" required>
                                    <div id="dni-feedback" class="form-text"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nac" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Género</label>
                                    <select name="genero" class="form-select" required>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="section-title">Contacto y Ubicación</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" name="telefono" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Correo (Email)</label><input type="email" name="emai_empl" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">Dirección</label><input type="text" name="dire_empl" class="form-control"></div>
                            </div>

                            <h6 class="section-title">Asignación de Sede</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Cargo</label>
                                    <select name="id_cargo" class="form-select">
                                        <?php foreach ($cargos as $c): ?><option value="<?= (int) $c['pk_id_cargo'] ?>"><?= htmlspecialchars($c['nomb_carg']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Grupo</label>
                                    <select name="id_grupo" class="form-select">
                                        <?php foreach ($grupos as $g): ?><option value="<?= (int) $g['pk_id_grupo'] ?>"><?= htmlspecialchars($g['nomb_grup']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Distrito</label>
                                    <select name="id_distrito" class="form-select">
                                        <?php foreach ($distritos as $d): ?><option value="<?= (int) $d['pk_id_distrito'] ?>"><?= htmlspecialchars($d['nomb_dist']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <h6 class="section-title">Otros Datos</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label">Observaciones</label>
                                    <textarea name="obsv_empl" class="form-control" rows="2" placeholder="Información adicional del colaborador..."></textarea>
                                </div>
                            </div>

                            <input type="hidden" name="descriptor" id="descriptor_input">
                            <button type="submit" id="btn-guardar" class="btn btn-primary w-100 fw-bold shadow-sm" disabled>Finalizar Registro</button>
                        </div>
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
        const MODEL_URL = '/asistencia_facial/assets/models/';
        (() => {
            const telefonoInputRegistro = document.querySelector('input[name="telefono"]');
            if (telefonoInputRegistro) {
                telefonoInputRegistro.classList.add('only-digits');
                telefonoInputRegistro.setAttribute('inputmode', 'numeric');
                telefonoInputRegistro.setAttribute('maxlength', '9');
                telefonoInputRegistro.setAttribute('pattern', '[0-9]{9}');
                telefonoInputRegistro.setAttribute('placeholder', '9 digitos');
            }
            document.querySelectorAll('.only-digits').forEach((input) => {
                input.addEventListener('input', () => {
                    input.value = input.value.replace(/\D/g, '').slice(0, Number(input.getAttribute('maxlength')) || 20);
                });
            });

            const dniInput = document.getElementById('dni_registro');
            const dniFeedback = document.getElementById('dni-feedback');
            let dniTimer = null;
            if (dniInput && dniFeedback) {
                dniInput.addEventListener('input', () => {
                    clearTimeout(dniTimer);
                    dniInput.classList.remove('is-valid', 'is-invalid');
                    dniFeedback.textContent = '';
                    if (dniInput.value.length !== 8) return;

                    dniTimer = setTimeout(async () => {
                        try {
                            const response = await fetch(`../models/verificar_dni.php?dni=${encodeURIComponent(dniInput.value)}`);
                            const data = await response.json();
                            if (data.exists) {
                                dniInput.classList.add('is-invalid');
                                dniFeedback.className = 'invalid-feedback d-block';
                                dniFeedback.textContent = `DNI ya registrado para ${data.employee?.nombre || 'otro colaborador'}.`;
                            } else {
                                dniInput.classList.add('is-valid');
                                dniFeedback.className = 'valid-feedback d-block';
                                dniFeedback.textContent = 'DNI disponible.';
                            }
                        } catch {
                            dniFeedback.className = 'form-text text-muted';
                            dniFeedback.textContent = 'No se pudo verificar el DNI en este momento.';
                        }
                    }, 350);
                });
            }
        })();
    </script>
    <script src="../assets/js/lib/face-api.js"></script>
    <script src="../assets/js/registro_logica.js"></script>
</body>
</html>
