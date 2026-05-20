const video = document.getElementById('video');
const canvas = document.getElementById('overlay');
const statusIA = document.getElementById('status-ia');
const btnGuardar = document.getElementById('btn-guardar');
const descriptorInput = document.getElementById('descriptor_input');
const telefonoInput = document.querySelector('input[name="telefono"]');

const finalModelUrl = (typeof MODEL_URL !== 'undefined') ? MODEL_URL : '../assets/models/';

if (telefonoInput) {
    telefonoInput.inputMode = 'numeric';
    telefonoInput.maxLength = 9;
    telefonoInput.minLength = 9;
    telefonoInput.pattern = '[0-9]{9}';
    telefonoInput.title = 'Ingrese exactamente 9 dígitos numéricos';

    telefonoInput.addEventListener('input', () => {
        telefonoInput.value = telefonoInput.value.replace(/\D/g, '').slice(0, 9);
    });
}

function mostrarEstadoCamaraRegistro(tipo, titulo, detalle = '') {
    const clase = tipo === 'danger' ? 'alert-danger' : tipo === 'success' ? 'alert-success' : 'alert-info';
    statusIA.innerHTML = `<div class="alert ${clase} text-start small py-2 mb-0">
        <strong>${titulo}</strong>
        ${detalle ? `<div class="mt-1">${detalle}</div>` : ''}
        ${tipo === 'danger' ? '<button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="location.reload()">Reintentar</button>' : ''}
    </div>`;
}

async function cargarModelosYCamara() {
    console.log('--- INICIANDO REGISTRO DE PERSONAL ---');

    try {
        console.log('Solicitando acceso a cámara...');
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;
        await video.play();
        console.log('Cámara iniciada.');
    } catch (err) {
        console.error('Error de cámara:', err);
        mostrarEstadoCamaraRegistro(
            'danger',
            'No se pudo usar la cámara.',
            'Active el permiso de cámara del navegador, conecte una cámara disponible y vuelva a intentar.'
        );
    }

    try {
        if (!statusIA) throw new Error('Elemento status-ia no encontrado.');

        mostrarEstadoCamaraRegistro('info', 'Cargando motor de IA...', 'Espere mientras se preparan los modelos faciales.');

        if (typeof faceapi === 'undefined') {
            throw new Error('Librería face-api.js no cargada.');
        }

        console.log('Cargando modelos desde:', finalModelUrl);

        const resultados = await Promise.allSettled([
            faceapi.nets.tinyFaceDetector.loadFromUri(finalModelUrl),
            faceapi.nets.faceLandmark68Net.loadFromUri(finalModelUrl),
            faceapi.nets.faceRecognitionNet.loadFromUri(finalModelUrl),
            faceapi.nets.faceExpressionNet.loadFromUri(finalModelUrl)
        ]);

        const fallos = resultados.filter((resultado) => resultado.status === 'rejected');
        if (fallos.length > 0) {
            console.error('Fallos en carga de modelos:', fallos);
            throw new Error('No se pudieron cargar los modelos de IA.');
        }

        console.log('Todos los modelos listos para el registro.');

        const iniciarDeteccion = () => {
            mostrarEstadoCamaraRegistro('success', 'IA lista', 'Coloque el rostro frente a la cámara.');
            const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
            faceapi.matchDimensions(canvas, displaySize);

            setInterval(async () => {
                if (video.paused || video.ended) return;

                try {
                    if (!faceapi.nets.tinyFaceDetector.params) {
                        console.warn('Recargando pesos del detector...');
                        await faceapi.nets.tinyFaceDetector.loadFromUri(finalModelUrl);
                    }

                    const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceDescriptor()
                        .withFaceExpressions();

                    const ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    if (detection) {
                        const resizedDetections = faceapi.resizeResults(detection, displaySize);

                        faceapi.draw.drawDetections(canvas, resizedDetections);
                        faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
                        faceapi.draw.drawFaceExpressions(canvas, resizedDetections);

                        descriptorInput.value = JSON.stringify(Array.from(detection.descriptor));
                        statusIA.innerHTML = "<span class='text-success fw-bold'>Rostro detectado ✓</span>";
                        btnGuardar.disabled = false;
                    } else {
                        statusIA.innerHTML = "<span class='text-danger'>Encuadre su rostro</span>";
                        btnGuardar.disabled = true;
                    }
                } catch (error) {
                    console.error('Error en detección facial:', error);
                    if (error.message && error.message.includes('weights')) {
                        mostrarEstadoCamaraRegistro('danger', 'Modelos no disponibles', 'Revise la carpeta assets/models.');
                    }
                }
            }, 500);
        };

        if (!video.paused) {
            iniciarDeteccion();
        } else {
            video.onplay = iniciarDeteccion;
        }
    } catch (error) {
        mostrarEstadoCamaraRegistro('danger', 'No se pudo iniciar la IA facial', 'Verifique que los modelos existan en assets/models.');
        console.error(error);
    }
}

document.getElementById('formRegistroEmpleado').addEventListener('submit', async (event) => {
    event.preventDefault();

    const dniInput = event.target.querySelector('input[name="dni"]');
    if (dniInput && !/^\d{8}$/.test(dniInput.value)) {
        UIFeedback.warning('DNI inválido', 'El DNI debe tener exactamente 8 dígitos numéricos.');
        dniInput.focus();
        return;
    }

    if (dniInput && dniInput.classList.contains('is-invalid')) {
        UIFeedback.warning('DNI duplicado', 'Revise el DNI antes de guardar el registro.');
        dniInput.focus();
        return;
    }

    if (telefonoInput && telefonoInput.value !== '' && !/^\d{9}$/.test(telefonoInput.value)) {
        UIFeedback.warning('Teléfono inválido', 'El teléfono debe tener exactamente 9 dígitos numéricos.');
        telefonoInput.focus();
        return;
    }

    const formData = new FormData(event.target);
    try {
        const response = await fetch('../models/guardar_empleado.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.status === 'success') {
            UIFeedback.success('Empleado registrado correctamente').then(() => {
                window.location.reload();
            });
        } else {
            UIFeedback.error('Error', result.message);
        }
    } catch {
        UIFeedback.error('Error', 'Fallo de servidor');
    }
});

cargarModelosYCamara();
