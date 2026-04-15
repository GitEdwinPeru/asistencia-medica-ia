<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_id'])) { header("Location: index.php"); exit(); }
require_once '../config/db.php';

// Cargar catálogos
$cargos = $pdo->query("SELECT * FROM cargo ORDER BY nomb_carg ASC")->fetchAll();
$grupos = $pdo->query("SELECT * FROM grupo ORDER BY nomb_grup ASC")->fetchAll();
$distritos = $pdo->query("SELECT * FROM distrito ORDER BY nomb_dist ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Personal - Centro de Salud</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/registro_empleado.css">
</head>
<body>
    <div class="container py-5">
        <div class="card">
            <div class="card-header py-3">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="bi bi-hospital me-2"></i> Registro de Nuevo Personal Médico / Administrativo
                </h5>
            </div>
            <div class="card-body p-4">
                <form id="formRegistroEmpleado">
                    <div class="row">
                        <div class="col-lg-4 text-center border-end pe-lg-4">
                            <h6 class="section-title">Validación Biométrica</h6>
                            <div id="contenedor-camara" class="mb-3">
                                <video id="video" autoplay muted></video>
                                <canvas id="overlay" style="position: absolute; top:0; left:0;"></canvas>
                            </div>
                            <div id="status-ia" class="small text-muted mb-4">
                                <i class="bi bi-info-circle me-1"></i> Encuadre el rostro para habilitar el registro
                            </div>
                        </div>

                        <div class="col-lg-8 ps-lg-4">
                            <h6 class="section-title">Información Personal</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Nombres</label>
                                    <input type="text" name="nombre" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ap. Paterno</label>
                                    <input type="text" name="apellido_pat" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ap. Materno</label>
                                    <input type="text" name="apellido_mat" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">DNI / Documento</label>
                                    <input type="text" name="dni" class="form-control" maxlength="8" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Género</label>
                                    <select name="genero" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <option value="M">Masculino</option>
                                        <option value="F">Femenino</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" name="telefono" class="form-control">
                                </div>
                            </div>

                            <h6 class="section-title">Asignación de Sede y Cargo</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label">Especialidad / Cargo</label>
                                    <select name="id_cargo" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($cargos as $c): ?>
                                            <option value="<?= $c['pk_id_cargo'] ?>"><?= $c['nomb_carg'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Grupo de Trabajo</label>
                                    <select name="id_grupo" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($grupos as $g): ?>
                                            <option value="<?= $g['pk_id_grupo'] ?>"><?= $g['nomb_grup'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Distrito / Sede</label>
                                    <select name="id_distrito" class="form-select" required>
                                        <option value="">Seleccione...</option>
                                        <?php foreach ($distritos as $d): ?>
                                            <option value="<?= $d['pk_id_distrito'] ?>"><?= $d['nomb_dist'] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Dirección / Domicilio</label>
                                    <input type="text" name="direccion" class="form-control">
                                </div>
                            </div>

                            <input type="hidden" name="descriptor" id="descriptor_input">
                            <button type="submit" id="btn-guardar" class="btn btn-medical w-100 fw-bold" disabled>
                                <i class="bi bi-shield-check me-2"></i> Procesar Registro Médico
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/lib/face-api.js"></script>
    <script src="../assets/js/registro_logica.js"></script>
</body>
</html>