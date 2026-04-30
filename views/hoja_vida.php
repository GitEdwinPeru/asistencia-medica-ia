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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --primary-color: #0d6efd; --bg-light: #f8f9fa; }
        body { background-color: var(--bg-light); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .cv-container { max-width: 1000px; margin: 40px auto; }
        .cv-header { background: white; border-radius: 15px; padding: 30px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .cv-section { background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .section-title { color: var(--primary-color); font-weight: 700; border-bottom: 2px solid #eef2f7; padding-bottom: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-label { font-weight: 600; color: #495057; font-size: 0.9rem; }
        .form-control:focus { box-shadow: none; border-color: var(--primary-color); }
        .btn-add { font-size: 0.8rem; padding: 4px 12px; }
        .dynamic-row { position: relative; padding: 15px; border: 1px solid #f1f1f1; border-radius: 10px; margin-bottom: 10px; background: #fafafa; }
        .btn-remove { position: absolute; top: -10px; right: -10px; background: #ff4757; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 12px; cursor: pointer; }
        .save-status { position: fixed; bottom: 20px; right: 20px; padding: 10px 20px; border-radius: 30px; background: #2f3542; color: white; display: none; z-index: 1000; }
        .account-highlight { background-color: #fff3cd; border: 2px solid #ffecb5; font-weight: bold; color: #856404; font-size: 1.1rem; }
        .selector-card { background: #e9ecef; border-radius: 15px; padding: 20px; margin-bottom: 20px; border: 1px solid #dee2e6; }
        @media (max-width: 768px) { .cv-container { margin: 10px; } }
    </style>
</head>
<body>

    <div class="cv-container">
        <!-- Selector de Usuario -->
        <div class="selector-card shadow-sm no-print">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label class="form-label mb-0 fw-bold"><i class="bi bi-search me-2"></i> Seleccionar Colaborador:</label>
                </div>
                <div class="col-md-9">
                    <div class="input-group">
                        <select id="userSelector" class="form-select border-primary shadow-sm" onchange="cambiarUsuario(this.value)">
                            <?php foreach ($todos_empleados as $emp): ?>
                                <option value="<?= $emp['pk_id_empleado'] ?>" <?= $id_empleado == $emp['pk_id_empleado'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['apat_empl'] . ' ' . $emp['nomb_empl'] . ' (DNI: ' . $emp['dni_empl'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary px-4" type="button" onclick="cambiarUsuario(document.getElementById('userSelector').value)">
                            Cargar Datos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="cv-header d-flex justify-content-between align-items-center flex-wrap gap-3 border-start border-primary border-5">
            <div class="d-flex align-items-center gap-4">
                <div class="position-relative">
                    <?php if ($empleado['foto_empl']): ?>
                        <img src="../uploads/fotos/<?= $empleado['foto_empl'] ?>" class="rounded-circle shadow-sm border border-3 border-white" style="width: 100px; height: 100px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 100px; height: 100px;">
                            <i class="bi bi-person text-muted fs-1"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="fw-bold mb-1"><?= htmlspecialchars($empleado['apat_empl'] . ', ' . $empleado['nomb_empl']) ?></h2>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-primary px-3 rounded-pill"><?= htmlspecialchars($empleado['nomb_carg']) ?></span>
                        <span class="text-muted small fw-bold">DNI: <?= $empleado['dni_empl'] ?></span>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="asistencia_detalle.php?id=<?= $id_empleado ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <button onclick="generarPDF()" class="btn btn-danger">
                    <i class="bi bi-file-earmark-pdf"></i> Exportar
                </button>
                <button onclick="guardarTodo()" class="btn btn-success">
                    <i class="bi bi-save"></i> Guardar Todo
                </button>
            </div>
        </div>

        <form id="cvForm">
            <input type="hidden" name="id_empleado" value="<?= $id_empleado ?>">

            <!-- Ficha Datos Personales -->
            <div class="cv-section">
                <h5 class="section-title"><i class="bi bi-person-badge"></i> Datos Personales</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">DNI</label>
                        <input type="text" class="form-control" value="<?= $empleado['dni_empl'] ?>" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estado Civil</label>
                        <select name="esta_civil" class="form-select auto-save">
                            <option value="">Seleccionar...</option>
                            <option value="Soltero/a" <?= $empleado['esta_civil'] == 'Soltero/a' ? 'selected' : '' ?>>Soltero/a</option>
                            <option value="Casado/a" <?= $empleado['esta_civil'] == 'Casado/a' ? 'selected' : '' ?>>Casado/a</option>
                            <option value="Divorciado/a" <?= $empleado['esta_civil'] == 'Divorciado/a' ? 'selected' : '' ?>>Divorciado/a</option>
                            <option value="Viudo/a" <?= $empleado['esta_civil'] == 'Viudo/a' ? 'selected' : '' ?>>Viudo/a</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nacionalidad</label>
                        <input type="text" name="nacionalidad" class="form-control auto-save" value="<?= htmlspecialchars($empleado['nacionalidad'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Celular</label>
                        <input type="text" name="celu_empl" class="form-control auto-save" value="<?= htmlspecialchars($empleado['celu_empl'] ?? '') ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="emai_empl" class="form-control auto-save" value="<?= htmlspecialchars($empleado['emai_empl'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Dirección Domiciliaria</label>
                        <input type="text" name="dire_empl" class="form-control auto-save" value="<?= htmlspecialchars($empleado['dire_empl'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Estudios Académicos -->
            <div class="cv-section" id="section-estudios">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="section-title mb-0"><i class="bi bi-mortarboard"></i> Estudios Académicos</h5>
                    <button type="button" class="btn btn-primary btn-add" onclick="agregarFila('estudios')">+ Agregar</button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($estudios as $est): ?>
                    <div class="dynamic-row" data-id="<?= $est['id'] ?>">
                        <button type="button" class="btn-remove" onclick="eliminarFila(this, 'estudios')">&times;</button>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input type="text" name="estudio_titulo[]" class="form-control form-control-sm" placeholder="Título obtenido" value="<?= htmlspecialchars($est['titulo']) ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="estudio_inst[]" class="form-control form-control-sm" placeholder="Institución" value="<?= htmlspecialchars($est['institucion']) ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="estudio_fecha[]" class="form-control form-control-sm" value="<?= $est['fecha_graduacion'] ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Datos Bancarios -->
            <div class="cv-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="section-title mb-0"><i class="bi bi-bank"></i> Información Bancaria</h5>
                    <button type="button" class="btn btn-primary btn-add" onclick="agregarFila('bancos')">+ Agregar</button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($bancos as $bnc): ?>
                    <div class="dynamic-row" data-id="<?= $bnc['id'] ?>">
                        <button type="button" class="btn-remove" onclick="eliminarFila(this, 'bancos')">&times;</button>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="banco_nombre[]" class="form-control form-control-sm" placeholder="Banco" value="<?= htmlspecialchars($bnc['banco']) ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="banco_tipo[]" class="form-select form-select-sm">
                                    <option value="Ahorros" <?= $bnc['tipo_cuenta'] == 'Ahorros' ? 'selected' : '' ?>>Ahorros</option>
                                    <option value="Corriente" <?= $bnc['tipo_cuenta'] == 'Corriente' ? 'selected' : '' ?>>Corriente</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="banco_numero[]" class="form-control form-control-sm account-highlight" placeholder="Número de cuenta" value="<?= desencriptarDato($bnc['numero_cuenta']) ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Carga Familiar -->
            <div class="cv-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="section-title mb-0"><i class="bi bi-people"></i> Composición Familiar</h5>
                    <button type="button" class="btn btn-primary btn-add" onclick="agregarFila('familia')">+ Agregar</button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($familia as $fam): ?>
                    <div class="dynamic-row" data-id="<?= $fam['id'] ?>">
                        <button type="button" class="btn-remove" onclick="eliminarFila(this, 'familia')">&times;</button>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="fam_nombre[]" class="form-control form-control-sm" placeholder="Nombre completo" value="<?= htmlspecialchars($fam['nombre']) ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="fam_paren[]" class="form-control form-control-sm" placeholder="Parentesco" value="<?= htmlspecialchars($fam['parentesco']) ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="fam_fecha[]" class="form-control form-control-sm" value="<?= $fam['fecha_nacimiento'] ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="fam_ocup[]" class="form-control form-control-sm" placeholder="Ocupación" value="<?= htmlspecialchars($fam['ocupacion']) ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Contacto de Emergencia -->
            <div class="cv-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="section-title mb-0"><i class="bi bi-telephone-outbound"></i> Contacto de Emergencia</h5>
                    <button type="button" class="btn btn-primary btn-add" onclick="agregarFila('emergencia')">+ Agregar</button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($emergencia as $eme): ?>
                    <div class="dynamic-row" data-id="<?= $eme['id'] ?>">
                        <button type="button" class="btn-remove" onclick="eliminarFila(this, 'emergencia')">&times;</button>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="eme_nombre[]" class="form-control form-control-sm" placeholder="Nombre completo" value="<?= htmlspecialchars($eme['nombre']) ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="eme_rel[]" class="form-control form-control-sm" placeholder="Relación" value="<?= htmlspecialchars($eme['relacion']) ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="eme_tel[]" class="form-control form-control-sm" placeholder="Teléfono" value="<?= htmlspecialchars($eme['telefono']) ?>">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="eme_dir[]" class="form-control form-control-sm" placeholder="Dirección" value="<?= htmlspecialchars($eme['direccion']) ?>">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Experiencia Laboral -->
            <div class="cv-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="section-title mb-0"><i class="bi bi-briefcase"></i> Experiencia Laboral</h5>
                    <button type="button" class="btn btn-primary btn-add" onclick="agregarFila('experiencia')">+ Agregar</button>
                </div>
                <div class="container-dinamico">
                    <?php foreach ($experiencia as $exp): ?>
                    <div class="dynamic-row" data-id="<?= $exp['id'] ?>">
                        <button type="button" class="btn-remove" onclick="eliminarFila(this, 'experiencia')">&times;</button>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="exp_empresa[]" class="form-control form-control-sm" placeholder="Empresa" value="<?= htmlspecialchars($exp['empresa']) ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="exp_cargo[]" class="form-control form-control-sm" placeholder="Cargo desempeñado" value="<?= htmlspecialchars($exp['cargo']) ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="exp_inicio[]" class="form-control form-control-sm" value="<?= $exp['fecha_inicio'] ?>">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="exp_fin[]" class="form-control form-control-sm" value="<?= $exp['fecha_fin'] ?>">
                            </div>
                            <div class="col-12 mt-2">
                                <textarea name="exp_desc[]" class="form-control form-control-sm" rows="2" placeholder="Breve descripción de funciones..."><?= htmlspecialchars($exp['descripcion']) ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </form>
    </div>

    <!-- Status de guardado -->
    <div id="saveStatus" class="save-status">
        <span class="spinner-border spinner-border-sm me-2"></span> Guardando cambios...
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        const TEMPLATES = {
            estudios: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'estudios')">&times;</button>
                    <div class="row g-2">
                        <div class="col-md-5"><input type="text" name="estudio_titulo[]" class="form-control form-control-sm" placeholder="Título obtenido"></div>
                        <div class="col-md-4"><input type="text" name="estudio_inst[]" class="form-control form-control-sm" placeholder="Institución"></div>
                        <div class="col-md-3"><input type="date" name="estudio_fecha[]" class="form-control form-control-sm"></div>
                    </div>
                </div>`,
            bancos: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'bancos')">&times;</button>
                    <div class="row g-2">
                        <div class="col-md-4"><input type="text" name="banco_nombre[]" class="form-control form-control-sm" placeholder="Banco"></div>
                        <div class="col-md-3">
                            <select name="banco_tipo[]" class="form-select form-select-sm">
                                <option value="Ahorros">Ahorros</option>
                                <option value="Corriente">Corriente</option>
                            </select>
                        </div>
                        <div class="col-md-5"><input type="text" name="banco_numero[]" class="form-control form-control-sm account-highlight" placeholder="Número de cuenta"></div>
                    </div>
                </div>`,
            familia: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'familia')">&times;</button>
                    <div class="row g-2">
                        <div class="col-md-4"><input type="text" name="fam_nombre[]" class="form-control form-control-sm" placeholder="Nombre completo"></div>
                        <div class="col-md-2"><input type="text" name="fam_paren[]" class="form-control form-control-sm" placeholder="Parentesco"></div>
                        <div class="col-md-3"><input type="date" name="fam_fecha[]" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><input type="text" name="fam_ocup[]" class="form-control form-control-sm" placeholder="Ocupación"></div>
                    </div>
                </div>`,
            emergencia: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'emergencia')">&times;</button>
                    <div class="row g-2">
                        <div class="col-md-4"><input type="text" name="eme_nombre[]" class="form-control form-control-sm" placeholder="Nombre completo"></div>
                        <div class="col-md-2"><input type="text" name="eme_rel[]" class="form-control form-control-sm" placeholder="Relación"></div>
                        <div class="col-md-3"><input type="text" name="eme_tel[]" class="form-control form-control-sm" placeholder="Teléfono"></div>
                        <div class="col-md-3"><input type="text" name="eme_dir[]" class="form-control form-control-sm" placeholder="Dirección"></div>
                    </div>
                </div>`,
            experiencia: `
                <div class="dynamic-row">
                    <button type="button" class="btn-remove" onclick="eliminarFila(this, 'experiencia')">&times;</button>
                    <div class="row g-2">
                        <div class="col-md-4"><input type="text" name="exp_empresa[]" class="form-control form-control-sm" placeholder="Empresa"></div>
                        <div class="col-md-4"><input type="text" name="exp_cargo[]" class="form-control form-control-sm" placeholder="Cargo desempeñado"></div>
                        <div class="col-md-2"><input type="date" name="exp_inicio[]" class="form-control form-control-sm"></div>
                        <div class="col-md-2"><input type="date" name="exp_fin[]" class="form-control form-control-sm"></div>
                        <div class="col-12 mt-2"><textarea name="exp_desc[]" class="form-control form-control-sm" rows="2" placeholder="Breve descripción..."></textarea></div>
                    </div>
                </div>`
        };

        function agregarFila(tipo) {
            const container = document.querySelector(`#cvForm .cv-section:has(h5 i.bi-${tipo === 'estudios' ? 'mortarboard' : tipo === 'bancos' ? 'bank' : tipo === 'familia' ? 'people' : tipo === 'emergencia' ? 'telephone-outbound' : 'briefcase'}) .container-dinamico`);
            // Nota: Selector simplificado para demostración, en producción usar IDs únicos
            const target = event.target.closest('.cv-section').querySelector('.container-dinamico');
            target.insertAdjacentHTML('beforeend', TEMPLATES[tipo]);
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

        async function guardarTodo() {
            const form = document.getElementById('cvForm');
            const formData = new FormData(form);
            const statusDiv = document.getElementById('saveStatus');

            statusDiv.style.display = 'block';

            try {
                const response = await fetch('../models/guardar_hoja_vida.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) throw new Error('Error en la comunicación con el servidor');
                
                const result = await response.json();
                
                if(result.status === 'success') {
                    // Solo mostrar notificación si el usuario hizo clic explícitamente en el botón
                    // o si queremos una notificación sutil para el auto-save.
                    // Según el requerimiento: "solo se muestren cuando realmente se ejecute una acción de guardado exitosa"
                    // y "causando que el mensaje aparezca repetidamente sin que el usuario haya presionado el botón guardar".
                    
                    // Verificamos si la llamada proviene del botón (manual) o del auto-save
                    const isManual = event && (event.type === 'click' || event.target.tagName === 'BUTTON');

                    if (isManual) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Hoja de Vida Actualizada',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        // Para el auto-save, solo mostramos un indicador visual temporal en el statusDiv
                        statusDiv.innerHTML = '<i class="bi bi-check-circle me-2"></i> Cambios guardados';
                        setTimeout(() => {
                            statusDiv.style.display = 'none';
                            statusDiv.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando cambios...';
                        }, 2000);
                        return; // Evitar el display: none del finally para que se vea el mensaje de éxito
                    }
                } else {
                    throw new Error(result.message || 'Error desconocido al guardar');
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
            } finally {
                // Solo ocultamos si no es un auto-save exitoso (que tiene su propio timer arriba)
                if (statusDiv.innerHTML.indexOf('Guardando') !== -1) {
                    statusDiv.style.display = 'none';
                }
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
