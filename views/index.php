<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia Facial - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/index_estilos.css">
</head>
<body>
    <div class="container p-3">
        <div class="card login-card mx-auto shadow-lg" style="max-width: 500px;">
            
            <div class="card-header bg-white border-0 pt-4">
                <ul class="nav nav-tabs" id="tabSistema" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="asistencia-tab" data-bs-toggle="tab" data-bs-target="#asistencia" type="button">
                            <i class="bi bi-person-bounding-box me-1"></i>Marcación
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin" type="button">
                            <i class="bi bi-lock me-1"></i>Administración
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4 text-center tab-content">
                
                <div class="tab-pane fade show active" id="asistencia">
                    <h3 class="fw-bold mb-4 text-dark">Asistencia Facial</h3>

                    <div id="contenedor-camara">
                        <video id="video" autoplay muted playsinline></video>
                        <canvas id="overlay"></canvas>
                    </div>

                    <div id="status" class="alert alert-info py-2 small my-3">
                        <span class="spinner-border spinner-border-sm"></span> Inicializando IA...
                    </div>

                    <div class="d-flex gap-2 mb-2">
                        <button id="btn-marcar-asistencia" class="btn btn-success btn-lg w-100 shadow-sm fw-bold" disabled>
                            <i class="bi bi-check2-circle me-2"></i>Entrada
                        </button>
                        <button id="btn-marcar-salida" class="btn btn-danger btn-lg w-100 shadow-sm fw-bold" disabled>
                            <i class="bi bi-box-arrow-right me-2"></i>Salida
                        </button>
                    </div>
                    
                    <p class="text-muted small">Póngase frente a la cámara y elija una opción al ser reconocido.</p>
                </div>

                <div class="tab-pane fade" id="admin">
                    <h3 class="fw-bold mb-4 text-dark">Acceso al Sistema</h3>
                    <form action="../models/login_process.php" method="POST">
                        <div class="mb-3 text-start">
                            <label class="form-label small fw-bold">Usuario</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" name="usuario" class="form-control" placeholder="Usuario" required>
                            </div>
                        </div>
                        <div class="mb-4 text-start">
                            <label class="form-label small fw-bold">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="clave" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Ingresar
                        </button>
                    </form>
                </div>
            </div>

            <div class="card-footer bg-white border-0 text-center pb-4">
                <small class="text-muted">© 2026 - Sistema de Gestión Facial - AMFURI PERU S.A.C.</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/lib/face-api.js"></script>
    <script src="../assets/js/camara.js"></script>
</body>
</html>