<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado | AMFURI PERU S.A.C.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #fff5f5; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .error-card { text-align: center; padding: 40px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(220,53,69,0.1); max-width: 500px; border-top: 5px solid #dc3545; }
        .error-code { font-size: 6rem; font-weight: 800; color: #f8d7da; line-height: 1; margin-bottom: 20px; }
        .error-icon { font-size: 4rem; color: #dc3545; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">403</div>
        <div class="error-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <h2 class="fw-bold text-danger">Acceso Denegado</h2>
        <p class="text-muted mb-4">No tienes los permisos necesarios para acceder a este recurso.</p>
        <a href="/asistencia_facial/views/dashboard.php" class="btn btn-outline-danger px-4 py-2 rounded-pill fw-bold">
            <i class="bi bi-arrow-left me-2"></i>Regresar al Panel
        </a>
    </div>
</body>
</html>
