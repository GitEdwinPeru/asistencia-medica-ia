<?php
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';
require_once '../config/security.php';

$id_empleado = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si no hay ID, buscar el primer empleado activo para mostrar algo por defecto o redirigir
if ($id_empleado === 0) {
    $stmt_default = $pdo->query("SELECT pk_id_empleado FROM empleado WHERE esta_empl = 1 LIMIT 1");
    $id_empleado = $stmt_default->fetchColumn();
    
    if (!$id_empleado) {
        header("Location: dashboard.php");
        exit();
    }
}

// Obtener datos básicos
$stmt = $pdo->prepare("SELECT e.*, c.nomb_carg, g.nomb_grup, d.nomb_dist 
                       FROM empleado e 
                       LEFT JOIN cargo c ON e.id_cargo = c.pk_id_cargo 
                       LEFT JOIN grupo g ON e.id_grupo = g.pk_id_grupo 
                       LEFT JOIN distrito d ON e.id_distrito = d.pk_id_distrito 
                       WHERE e.pk_id_empleado = ?");
$stmt->execute([$id_empleado]);
$empleado = $stmt->fetch();

if (!$empleado) {
    header("Location: empleados_lista.php");
    exit();
}

// Obtener otros datos
$estudios = $pdo->prepare("SELECT * FROM empleado_estudios WHERE id_empleado = ?");
$estudios->execute([$id_empleado]);
$estudios = $estudios->fetchAll();

$bancos = $pdo->prepare("SELECT * FROM empleado_bancos WHERE id_empleado = ?");
$bancos->execute([$id_empleado]);
$bancos = $bancos->fetchAll();

$familia = $pdo->prepare("SELECT * FROM empleado_familia WHERE id_empleado = ?");
$familia->execute([$id_empleado]);
$familia = $familia->fetchAll();

$emergencia = $pdo->prepare("SELECT * FROM empleado_emergencia WHERE id_empleado = ?");
$emergencia->execute([$id_empleado]);
$emergencia = $emergencia->fetchAll();

$experiencia = $pdo->prepare("SELECT * FROM empleado_experiencia WHERE id_empleado = ?");
$experiencia->execute([$id_empleado]);
$experiencia = $experiencia->fetchAll();

// Obtener todos los empleados activos para el selector
$stmt_all = $pdo->query("SELECT pk_id_empleado, nomb_empl, apat_empl, dni_empl FROM empleado WHERE esta_empl = 1 ORDER BY apat_empl ASC");
$todos_empleados = $stmt_all->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoja de Vida - <?= htmlspecialchars($empleado['nomb_empl'] . ' ' . $empleado['apat_empl']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { 
            --medical-primary: #007bff; 
            --medical-secondary: #6c757d;
            --medical-teal: #00b8d4;
            --medical-bg: #f0f4f8;
            --medical-card-bg: #ffffff;
            --medical-text: #2d3436;
            --medical-success: #2ecc71;
            --medical-danger: #e74c3c;
        }
        
        body { 
            background-color: var(--medical-bg); 
            font-family: 'Inter', -apple-system, sans-serif; 
            color: var(--medical-text);
            line-height: 1.6;
        }

        .cv-container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        
        .cv-header { 
            background: var(--medical-card-bg); 
            border-radius: 20px; 
            padding: 35px; 
            margin-bottom: 25px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            border-top: 6px solid var(--medical-teal);
        }

        .cv-section { 
            background: var(--medical-card-bg); 
            border-radius: 20px; 
            padding: 30px; 
            margin-bottom: 25px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.04);
            transition: transform 0.2s ease;
        }

        .section-title { 
            color: var(--medical-primary); 
            font-weight: 700; 
            font-size: 1.25rem;
            border-bottom: 2px solid #f1f4f9; 
            padding-bottom: 12px; 
            margin-bottom: 25px; 
            display: flex; 
            align-items: center; 
            gap: 12px; 
        }

        .form-label { font-weight: 600; color: #636e72; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .form-control { 
            border-radius: 12px; 
            border: 2px solid #e1e8ed; 
            padding: 10px 15px;
            transition: all 0.2s ease;
        }

        .form-control:focus { 
            box-shadow: 0 0 0 4px rgba(0, 184, 212, 0.1); 
            border-color: var(--medical-teal); 
        }

        .form-control.is-invalid { border-color: var(--medical-danger); }

        .btn-medical { 
            border-radius: 12px; 
            padding: 12px 25px; 
            font-weight: 600; 
            transition: all 0.3s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
        }

        .btn-teal { background-color: var(--medical-teal); color: white; border: none; }
        .btn-teal:hover { background-color: #0097a7; transform: translateY(-2px); color: white; }

        .btn-remove { 
            position: absolute; top: -12px; right: -12px; 
            background: var(--medical-danger); color: white; 
            border: none; border-radius: 50%; width: 28px; height: 28px; 
            font-size: 14px; cursor: pointer; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.3);
        }

        .account-highlight { 
            background-color: #e0f7fa !important; 
            border: 2px solid var(--medical-teal) !important; 
            font-weight: 700; 
            color: #006064; 
        }

        .selector-card { 
            background: #ffffff; 
            border-radius: 20px; 
            padding: 25px; 
            margin-bottom: 25px; 
            border: 1px solid #e1e8ed; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.02);
        }

        .btn-back-dashboard { 
            position: fixed; top: 25px; right: 25px; z-index: 1050; 
            border-radius: 50px; padding: 12px 28px; font-weight: 700; 
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.2); 
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            background: var(--medical-primary);
            border: none;
            color: white;
            text-decoration: none;
        }

        .btn-back-dashboard:hover { transform: scale(1.05); background: #0056b3; color: white; }

        .spinner-container { display: none; margin-left: 8px; }

        @media (max-width: 768px) { 
            .cv-container { margin: 20px auto; } 
            .btn-back-dashboard { top: auto; bottom: 30px; right: 20px; }
        }
    </style>
</head>
<body>

    <a href="dashboard.php" class="btn-back-dashboard no-print" aria-label="Volver al Panel Principal">
        <i class="bi bi-arrow-left-short fs-4"></i>
        <span>Volver al Dashboard</span>
    </a>

    <div class="cv-container">
        <!-- Selector de Usuario -->
        <div class="selector-card no-print">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-search me-2 text-teal"></i> GESTIÓN DE COLABORADOR</h6>
                </div>
                <div class="col-md-8">
                    <div class="input-group">
                        <select id="userSelector" class="form-select" onchange="cambiarUsuario(this.value)" aria-label="Seleccionar empleado">
                            <?php foreach ($todos_empleados as $emp): ?>
                                <option value="<?= $emp['pk_id_empleado'] ?>" <?= $id_empleado == $emp['pk_id_empleado'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['apat_empl'] . ' ' . $emp['nomb_empl'] . ' (DNI: ' . $emp['dni_empl'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-teal px-4" type="button" onclick="cambiarUsuario(document.getElementById('userSelector').value)">
                            Cargar Ficha
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="cv-header d-flex justify-content-between align-items-center flex-wrap gap-4">
            <div class="d-flex align-items-center gap-4">
                <div class="position-relative">
                    <?php if ($empleado['foto_empl']): ?>
                        <img src="../uploads/fotos/<?= $empleado['foto_empl'] ?>" class="rounded-circle shadow-lg border border-4 border-white" style="width: 120px; height: 120px; object-fit: cover;" alt="Foto de <?= htmlspecialchars($empleado['nomb_empl']) ?>">
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border border-4 border-white" style="width: 120px; height: 120px;" role="img" aria-label="Sin foto de perfil">
                            <i class="bi bi-person text-muted fs-1"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="fw-bold mb-1 h3 text-dark"><?= htmlspecialchars($empleado['apat_empl'] . ', ' . $empleado['nomb_empl']) ?></h1>
                    <div class="d-flex gap-3 align-items-center">
                        <span class="badge bg-primary px-3 rounded-pill py-2"><?= htmlspecialchars($empleado['nomb_carg']) ?></span>
                        <span class="text-secondary small fw-bold">ID: <?= $empleado['dni_empl'] ?></span>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-3">
                <button onclick="generarPDF()" class="btn btn-medical btn-outline-danger" title="Exportar a PDF">
                    <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                </button>
                <button id="btnGuardarMaestro" onclick="guardarTodo()" class="btn btn-medical btn-teal shadow-sm">
                    <i class="bi bi-cloud-upload-fill"></i> 
                    <span>Guardar Todo</span>
                    <span class="spinner-border spinner-border-sm spinner-container" role="status"></span>
                </button>
            </div>
        </div>

        <form id="cvForm" novalidate>
            <input type="hidden" name="id_empleado" value="<?= $id_empleado ?>">
            <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">

            <!-- Ficha Datos Personales -->
            <div class="cv-section">
                <h5 class="section-title"><i class="bi bi-person-circle"></i> Información Personal</h5>
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label" for="dni_readonly">Documento Identidad (DNI)</label>
                        <input type="text" id="dni_readonly" class="form-control bg-light" value="<?= $empleado['dni_empl'] ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="fnac_empl">Fecha de Nacimiento</label>
                        <input type="date" name="fnac_empl" id="fnac_empl" class="form-control auto-save" 
                               value="<?= $empleado['fnac_empl'] ?>">
                        <div class="invalid-feedback">Debe ser mayor de edad (18+).</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="esta_civil">Estado Civil</label>
                        <select name="esta_civil" id="esta_civil" class="form-select auto-save">
                            <option value="">Seleccionar...</option>
                            <option value="Soltero/a" <?= $empleado['esta_civil'] == 'Soltero/a' ? 'selected' : '' ?>>Soltero/a</option>
                            <option value="Casado/a" <?= $empleado['esta_civil'] == 'Casado/a' ? 'selected' : '' ?>>Casado/a</option>
                            <option value="Divorciado/a" <?= $empleado['esta_civil'] == 'Divorciado/a' ? 'selected' : '' ?>>Divorciado/a</option>
                            <option value="Viudo/a" <?= $empleado['esta_civil'] == 'Viudo/a' ? 'selected' : '' ?>>Viudo/a</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="nacionalidad">Nacionalidad</label>
                        <input type="text" name="nacionalidad" id="nacionalidad" class="form-control auto-save only-letters" 
                               placeholder="Ej: Peruana" value="<?= htmlspecialchars($empleado['nacionalidad'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="celu_empl">Número Celular</label>
                        <input type="tel" name="celu_empl" id="celu_empl" class="form-control auto-save only-numbers" 
                               maxlength="9" placeholder="999888777" value="<?= htmlspecialchars($empleado['celu_empl'] ?? '') ?>">
                        <div class="invalid-feedback">Ingrese 9 dígitos numéricos.</div>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="emai_empl">Email Corporativo/Personal</label>
                        <input type="email" name="emai_empl" id="emai_empl" class="form-control auto-save" 
                               placeholder="usuario@ejemplo.com" value="<?= htmlspecialchars($empleado['emai_empl'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="dire_empl">Domicilio Actual</label>
                        <input type="text" name="dire_empl" id="dire_empl" class="form-control auto-save" 
                               placeholder="Dirección completa" value="<?= htmlspecialchars($empleado['dire_empl'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Estudios y Formación -->
            <div class="cv-section" id="section-estudios">
                <div class="d-flex justify-content-between align-items-center section-title">
                    <h5 class="m-0"><i class="bi bi-mortarboard"></i> Formación Académica</h5>
                    <button type="button" class="btn btn-sm btn-teal rounded-pill" onclick="agregarFila('estudios')" aria-label="Agregar estudio">
                        <i class="bi bi-plus-lg"></i> Agregar
                    </button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($estudios as $est): ?>
                        <div class="dynamic-row" data-id="<?= $est['id'] ?>">
                            <button type="button" class="btn-remove" onclick="eliminarFila(this, 'estudios')" aria-label="Eliminar fila">&times;</button>
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label class="form-label">Título / Certificación</label>
                                    <input type="text" name="estudio_titulo[]" class="form-control" value="<?= htmlspecialchars($est['titulo']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Institución</label>
                                    <input type="text" name="estudio_inst[]" class="form-control" value="<?= htmlspecialchars($est['institucion']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" name="estudio_fecha[]" class="form-control" value="<?= $est['fecha_graduacion'] ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Información Bancaria -->
            <div class="cv-section">
                <div class="d-flex justify-content-between align-items-center section-title">
                    <h5 class="m-0"><i class="bi bi-bank"></i> Cuentas Bancarias</h5>
                    <button type="button" class="btn btn-sm btn-teal rounded-pill" onclick="agregarFila('bancos')" aria-label="Agregar cuenta bancaria">
                        <i class="bi bi-plus-lg"></i> Agregar
                    </button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($bancos as $bnc): ?>
                        <div class="dynamic-row" data-id="<?= $bnc['id'] ?>">
                            <button type="button" class="btn-remove" onclick="eliminarFila(this, 'bancos')" aria-label="Eliminar fila">&times;</button>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Entidad Bancaria</label>
                                    <input type="text" name="banco_nombre[]" class="form-control" value="<?= htmlspecialchars($bnc['banco']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tipo</label>
                                    <select name="banco_tipo[]" class="form-select">
                                        <option value="Ahorros" <?= $bnc['tipo_cuenta'] == 'Ahorros' ? 'selected' : '' ?>>Ahorros</option>
                                        <option value="Corriente" <?= $bnc['tipo_cuenta'] == 'Corriente' ? 'selected' : '' ?>>Corriente</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Número de Cuenta / CCI</label>
                                    <input type="text" name="banco_numero[]" class="form-control account-highlight only-numbers" value="<?= desencriptarDato($bnc['numero_cuenta']) ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Carga Familiar -->
            <div class="cv-section">
                <div class="d-flex justify-content-between align-items-center section-title">
                    <h5 class="m-0"><i class="bi bi-people"></i> Carga Familiar</h5>
                    <button type="button" class="btn btn-sm btn-teal rounded-pill" onclick="agregarFila('familia')" aria-label="Agregar familiar">
                        <i class="bi bi-plus-lg"></i> Agregar
                    </button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($familia as $fam): ?>
                        <div class="dynamic-row" data-id="<?= $fam['id'] ?>">
                            <button type="button" class="btn-remove" onclick="eliminarFila(this, 'familia')" aria-label="Eliminar fila">&times;</button>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Nombre del Familiar</label>
                                    <input type="text" name="fam_nombre[]" class="form-control" value="<?= htmlspecialchars($fam['nombre']) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Parentesco</label>
                                    <input type="text" name="fam_paren[]" class="form-control" value="<?= htmlspecialchars($fam['parentesco']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Nacimiento</label>
                                    <input type="date" name="fam_fecha[]" class="form-control" value="<?= $fam['fecha_nacimiento'] ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ocupación</label>
                                    <input type="text" name="fam_ocup[]" class="form-control" value="<?= htmlspecialchars($fam['ocupacion']) ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contactos de Emergencia -->
            <div class="cv-section">
                <div class="d-flex justify-content-between align-items-center section-title">
                    <h5 class="m-0"><i class="bi bi-telephone-outbound"></i> Contactos de Emergencia</h5>
                    <button type="button" class="btn btn-sm btn-teal rounded-pill" onclick="agregarFila('emergencia')" aria-label="Agregar contacto de emergencia">
                        <i class="bi bi-plus-lg"></i> Agregar
                    </button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($emergencia as $eme): ?>
                        <div class="dynamic-row" data-id="<?= $eme['id'] ?>">
                            <button type="button" class="btn-remove" onclick="eliminarFila(this, 'emergencia')" aria-label="Eliminar fila">&times;</button>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Contacto</label>
                                    <input type="text" name="eme_nombre[]" class="form-control" value="<?= htmlspecialchars($eme['nombre']) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Relación</label>
                                    <input type="text" name="eme_rel[]" class="form-control" value="<?= htmlspecialchars($eme['relacion']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" name="eme_tel[]" class="form-control only-numbers" maxlength="9" value="<?= htmlspecialchars($eme['telefono']) ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" name="eme_dir[]" class="form-control" value="<?= htmlspecialchars($eme['direccion']) ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Experiencia Laboral -->
            <div class="cv-section">
                <div class="d-flex justify-content-between align-items-center section-title">
                    <h5 class="m-0"><i class="bi bi-briefcase"></i> Trayectoria Profesional</h5>
                    <button type="button" class="btn btn-sm btn-teal rounded-pill" onclick="agregarFila('experiencia')" aria-label="Agregar experiencia laboral">
                        <i class="bi bi-plus-lg"></i> Agregar
                    </button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($experiencia as $exp): ?>
                        <div class="dynamic-row" data-id="<?= $exp['id'] ?>">
                            <button type="button" class="btn-remove" onclick="eliminarFila(this, 'experiencia')" aria-label="Eliminar fila">&times;</button>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Empresa</label>
                                    <input type="text" name="exp_empresa[]" class="form-control" value="<?= htmlspecialchars($exp['empresa']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Cargo</label>
                                    <input type="text" name="exp_cargo[]" class="form-control" value="<?= htmlspecialchars($exp['cargo']) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Inicio</label>
                                    <input type="date" name="exp_inicio[]" class="form-control" value="<?= $exp['fecha_inicio'] ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Fin</label>
                                    <input type="date" name="exp_fin[]" class="form-control" value="<?= $exp['fecha_fin'] ?>">
                                </div>
                                <div class="col-12 mt-2">
                                    <label class="form-label">Funciones</label>
                                    <textarea name="exp_desc[]" class="form-control" rows="2"><?= htmlspecialchars($exp['descripcion']) ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </form>
    </div>

    <div id="saveStatus" class="save-status shadow-lg">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Guardando cambios...
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        const TEMPLATES = {
            estudios: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'estudios')" aria-label="Eliminar fila">&times;</button>
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Título / Certificación</label>
                            <input type="text" name="estudio_titulo[]" class="form-control" placeholder="Ej: Lic. en Administración">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Institución</label>
                            <input type="text" name="estudio_inst[]" class="form-control" placeholder="Ej: Universidad Nacional">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Graduación</label>
                            <input type="date" name="estudio_fecha[]" class="form-control">
                        </div>
                    </div>
                </div>`,
            bancos: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'bancos')" aria-label="Eliminar fila">&times;</button>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Entidad Bancaria</label>
                            <input type="text" name="banco_nombre[]" class="form-control" placeholder="Ej: BCP, BBVA">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tipo de Cuenta</label>
                            <select name="banco_tipo[]" class="form-select">
                                <option value="Ahorros">Ahorros</option>
                                <option value="Corriente">Corriente</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Número de Cuenta / CCI</label>
                            <input type="text" name="banco_numero[]" class="form-control account-highlight only-numbers" placeholder="Solo números">
                        </div>
                    </div>
                </div>`,
            familia: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'familia')" aria-label="Eliminar fila">&times;</button>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nombre Completo</label>
                            <input type="text" name="fam_nombre[]" class="form-control" placeholder="Nombre y apellidos">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Parentesco</label>
                            <input type="text" name="fam_paren[]" class="form-control" placeholder="Ej: Hijo/a">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha Nacimiento</label>
                            <input type="date" name="fam_fecha[]" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ocupación</label>
                            <input type="text" name="fam_ocup[]" class="form-control" placeholder="Ej: Estudiante">
                        </div>
                    </div>
                </div>`,
            emergencia: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'emergencia')" aria-label="Eliminar fila">&times;</button>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Contacto</label>
                            <input type="text" name="eme_nombre[]" class="form-control" placeholder="Nombre completo">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Relación</label>
                            <input type="text" name="eme_rel[]" class="form-control" placeholder="Ej: Esposo/a">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="eme_tel[]" class="form-control only-numbers" maxlength="9" placeholder="Solo números">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ubicación</label>
                            <input type="text" name="eme_dir[]" class="form-control" placeholder="Distrito/Dirección">
                        </div>
                    </div>
                </div>`,
            experiencia: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'experiencia')" aria-label="Eliminar fila">&times;</button>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Empresa / Entidad</label>
                            <input type="text" name="exp_empresa[]" class="form-control" placeholder="Nombre de la empresa">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cargo / Función</label>
                            <input type="text" name="exp_cargo[]" class="form-control" placeholder="Ej: Analista">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">F. Inicio</label>
                            <input type="date" name="exp_inicio[]" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">F. Fin</label>
                            <input type="date" name="exp_fin[]" class="form-control">
                        </div>
                        <div class="col-12 mt-2">
                            <label class="form-label">Logros / Funciones</label>
                            <textarea name="exp_desc[]" class="form-control" rows="2" placeholder="Describa brevemente..."></textarea>
                        </div>
                    </div>
                </div>`
        };

        // Bloquear letras en campos numéricos y números en campos de texto
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('only-numbers')) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            }
            if (e.target.classList.contains('only-letters')) {
                e.target.value = e.target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            }
        });

        function agregarFila(tipo) {
            const container = document.querySelector(`#cvForm .cv-section:has(h5 i.bi-${tipo === 'estudios' ? 'mortarboard' : tipo === 'bancos' ? 'bank' : tipo === 'familia' ? 'people' : tipo === 'emergencia' ? 'telephone-outbound' : 'briefcase'}) .container-dinamico`);
            const sections = document.querySelectorAll('.cv-section');
            let targetSection = null;
            
            // Buscar sección por icono (método más robusto que :has)
            sections.forEach(s => {
                const icon = s.querySelector('.section-title i');
                if (!icon) return;
                const cls = icon.className;
                if (tipo === 'estudios' && cls.includes('mortarboard')) targetSection = s;
                if (tipo === 'bancos' && cls.includes('bank')) targetSection = s;
                if (tipo === 'familia' && cls.includes('people')) targetSection = s;
                if (tipo === 'emergencia' && cls.includes('telephone')) targetSection = s;
                if (tipo === 'experiencia' && cls.includes('briefcase')) targetSection = s;
            });

            if (targetSection) {
                const target = targetSection.querySelector('.container-dinamico');
                target.insertAdjacentHTML('beforeend', TEMPLATES[tipo]);
            }
        }

        function eliminarFila(btn, tipo) {
            btn.closest('.dynamic-row').remove();
            autoSave();
        }

        function cambiarUsuario(id) {
            if (id && id !== "<?= $id_empleado ?>") {
                window.location.href = `hoja_vida.php?id=${id}`;
            }
        }

        function validarFormulario() {
            const form = document.getElementById('cvForm');
            let isValid = true;
            
            // Limpiar errores previos
            form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));

            // 1. Validar Correo
            const emailInput = form.querySelector('input[name="emai_empl"]');
            if (emailInput.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
                emailInput.classList.add('is-invalid');
                isValid = false;
            }

            // 2. Validar Celular (9 dígitos)
            const celularInput = form.querySelector('input[name="celu_empl"]');
            if (celularInput.value && !/^\d{9}$/.test(celularInput.value)) {
                celularInput.classList.add('is-invalid');
                isValid = false;
            }

            // 3. Validar Mayoría de Edad (18+)
            const fnacInput = form.querySelector('input[name="fnac_empl"]');
            if (fnacInput.value) {
                const birthDate = new Date(fnacInput.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const m = today.getMonth() - birthDate.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                if (age < 18) {
                    fnacInput.classList.add('is-invalid');
                    isValid = false;
                }
            }

            if (!isValid) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validación de Datos',
                    text: 'Por favor, corrija los campos resaltados en rojo.',
                    confirmButtonColor: 'var(--medical-primary)'
                });
            }

            return isValid;
        }

        async function guardarTodo() {
            const btn = document.getElementById('btnGuardarMaestro');
            const spinner = btn.querySelector('.spinner-container');
            const icon = btn.querySelector('.bi-cloud-upload-fill');
            const text = btn.querySelector('span');

            // Validaciones antes de guardar
            if (!validarFormulario()) return;

            const form = document.getElementById('cvForm');
            const formData = new FormData(form);

            // Estado de carga
            btn.disabled = true;
            if (spinner) spinner.style.display = 'inline-block';
            if (icon) icon.style.display = 'none';
            if (text) text.innerText = 'Procesando...';

            try {
                const response = await fetch('../models/guardar_hoja_vida.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error("Error del servidor:", errorText);
                    throw new Error('Fallo en la conexión con el servidor o error interno.');
                }
                
                const result = await response.json();
                
                if(result.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Cambios Guardados!',
                        text: 'La hoja de vida se ha actualizado con éxito.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    throw new Error(result.message || 'Error al guardar datos');
                }
            } catch (error) {
                console.error("Error capturado:", error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al Guardar',
                    text: error.message,
                    confirmButtonColor: 'var(--medical-danger)'
                });
            } finally {
                btn.disabled = false;
                if (spinner) spinner.style.display = 'none';
                if (icon) icon.style.display = 'inline-block';
                if (text) text.innerText = 'Guardar Todo';
            }
        }

        // Auto-save debounced
        let saveTimeout;
        let lastFormData = "";

        function autoSave() {
            const currentData = new URLSearchParams(new FormData(document.getElementById('cvForm'))).toString();
            if (currentData === lastFormData) return; // No hay cambios reales

            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                lastFormData = currentData;
                guardarTodo();
            }, 5000); 
        }

        // Inicializar lastFormData
        document.addEventListener('DOMContentLoaded', () => {
            lastFormData = new URLSearchParams(new FormData(document.getElementById('cvForm'))).toString();
        });

        document.getElementById('cvForm').addEventListener('input', autoSave);

        function generarPDF() {
            const id = <?= $id_empleado ?>;
            window.open(`../models/exportar_hoja_vida_pdf.php?id=${id}`, '_blank');
        }
    </script>
</body>
</html>
