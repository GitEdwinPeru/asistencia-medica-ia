<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['admin_id'])) { header("Location: index.php"); exit(); }
require_once '../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Obtener datos actuales del empleado
$stmt = $pdo->prepare("SELECT * FROM empleado WHERE pk_id_empleado = ?");
$stmt->execute([$id]);
$emp = $stmt->fetch();

if (!$emp) { echo "Empleado no encontrado."; exit; }

// 2. Cargar catálogos para los selectores
$cargos = $pdo->query("SELECT * FROM cargo ORDER BY nomb_carg ASC")->fetchAll();
$grupos = $pdo->query("SELECT * FROM grupo ORDER BY nomb_grup ASC")->fetchAll();
$distritos = $pdo->query("SELECT * FROM distrito ORDER BY nomb_dist ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Personal - <?= htmlspecialchars($emp['nomb_empl']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/editar_empleado.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header bg-warning text-dark p-3 fw-bold" style="border-radius: 15px 15px 0 0;">
                        <i class="bi bi-pencil-square me-2"></i> Modificar Datos de Personal
                    </div>
                    <div class="card-body p-4">
                        <form action="../models/actualizar_empleado.php" method="POST">
                            <input type="hidden" name="id_empleado" value="<?= $emp['pk_id_empleado'] ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Nombres</label>
                                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($emp['nomb_empl']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Apellido Paterno</label>
                                    <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($emp['apat_empl']) ?>" required>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-muted">DNI</label>
                                    <input type="text" name="dni" class="form-control bg-light" value="<?= htmlspecialchars($emp['dni_empl']) ?>" readonly>
                                    <div class="form-text text-danger">El DNI no se puede modificar por seguridad de identidad.</div>
                                </div>

                                <hr class="my-3">

                                <div class="col-md-12">
                                    <label class="form-label small fw-bold text-primary">Cargo Especializado</label>
                                    <select name="id_cargo" class="form-select shadow-sm" required>
                                        <?php foreach ($cargos as $c): ?>
                                            <option value="<?= $c['pk_id_cargo'] ?>" <?= ($c['pk_id_cargo'] == $emp['id_cargo']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['nomb_carg']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-success">Grupo / Área de Trabajo</label>
                                    <select name="id_grupo" class="form-select shadow-sm" required>
                                        <?php foreach ($grupos as $g): ?>
                                            <option value="<?= $g['pk_id_grupo'] ?>" <?= ($g['pk_id_grupo'] == $emp['id_grupo']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($g['nomb_grup']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-info">Sede / Distrito</label>
                                    <select name="id_distrito" class="form-select shadow-sm" required>
                                        <?php foreach ($distritos as $d): ?>
                                            <option value="<?= $d['pk_id_distrito'] ?>" <?= ($d['pk_id_distrito'] == $emp['id_distrito']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($d['nomb_dist']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-5 d-flex gap-2">
                                <button type="submit" class="btn btn-warning fw-bold w-100 py-2">
                                    <i class="bi bi-save me-2"></i> Guardar Cambios
                                </button>
                                <a href="empleados_lista.php" class="btn btn-outline-secondary w-100 py-2">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>