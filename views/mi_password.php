<?php
require_once '../config/auth.php';
verificarSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contrasena</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width:640px">
        <a href="dashboard.php" class="btn btn-link text-decoration-none text-muted fw-bold mb-3"><i class="bi bi-arrow-left-circle me-2"></i> Volver</a>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0 text-primary fw-bold"><i class="bi bi-key me-2"></i> Cambiar Contrasena</h5></div>
            <div class="card-body p-4">
                <form id="passwordForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generarTokenCSRF() ?>">
                    <div class="mb-3"><label class="form-label">Contrasena actual</label><input type="password" name="clave_actual" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Nueva contrasena</label><input type="password" name="clave_nueva" class="form-control" minlength="8" required></div>
                    <div class="mb-4"><label class="form-label">Confirmar contrasena</label><input type="password" name="clave_confirmar" class="form-control" minlength="8" required></div>
                    <button class="btn btn-primary w-100"><i class="bi bi-save me-1"></i> Actualizar</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/ui_feedback.js"></script>
    <script>
        document.getElementById('passwordForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const data = new FormData(form);
            if (data.get('clave_nueva') !== data.get('clave_confirmar')) {
                UIFeedback.warning('Contrasenas distintas', 'La confirmacion no coincide.');
                return;
            }
            const response = await fetch('../models/cambiar_password.php', { method: 'POST', body: data });
            const result = await response.json();
            if (result.status === 'success') {
                UIFeedback.success(result.message).then(() => { window.location.href = 'dashboard.php?msg=password_changed'; });
            } else {
                UIFeedback.error('No se pudo cambiar', result.message);
            }
        });
    </script>
</body>
</html>
