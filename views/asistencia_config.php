<?php
require_once '../config/auth.php';
requerirPermiso('configuracion');
require_once '../config/db.php';

$stmt = $pdo->query("SELECT * FROM asistencia_config WHERE activo = 1 ORDER BY id DESC LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['hora_entrada' => '08:15:00', 'tolerancia_minutos' => 0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuracion de Asistencia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="mb-3">
            <a href="dashboard.php" class="btn btn-link text-decoration-none text-muted fw-bold">
                <i class="bi bi-arrow-left-circle me-2"></i> Volver al Dashboard
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary fw-bold"><i class="bi bi-clock-history me-2"></i> Configuracion de Horario</h5>
            </div>
            <div class="card-body p-4">
                <form action="../models/guardar_asistencia_config.php" method="POST" class="row g-4">
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Hora oficial de entrada</label>
                        <input type="time" name="hora_entrada" class="form-control form-control-lg" value="<?= htmlspecialchars(substr($config['hora_entrada'], 0, 5)) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tolerancia en minutos</label>
                        <input type="number" name="tolerancia_minutos" class="form-control form-control-lg" min="0" max="240" value="<?= (int) $config['tolerancia_minutos'] ?>" required>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            Esta configuracion se usa para calcular tardanzas en las nuevas marcaciones.
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-2"></i> Guardar Configuracion
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/ui_feedback.js"></script>
    <script>
        UIFeedback.fromQuery({
            guardado: { icon: 'success', title: 'Horario actualizado' },
            error: { icon: 'error', title: 'No se pudo guardar la configuracion' }
        });
    </script>
</body>
</html>
