const video = document.getElementById('video');
const canvas = document.getElementById('overlay');
const statusIA = document.getElementById('status-ia');
const btnGuardar = document.getElementById('btn-guardar');
const descriptorInput = document.getElementById('descriptor_input');

const finalModelUrl = (typeof MODEL_URL !== 'undefined') ? MODEL_URL : '../assets/models/';

async function cargarModelosYCamara() {
    console.log("--- INICIANDO REGISTRO DE PERSONAL ---");

    // Iniciar cámara de inmediato
    try {
        console.log("Solicitando acceso a cámara...");
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;
        video.play();
        console.log("Cámara iniciada.");
    } catch (err) {
        console.error("Error cámara:", err);
        statusIA.innerHTML = "<span class='text-danger'>Cámara no disponible</span>";
    }

    try {
        if (!statusIA) throw new Error("Elemento status-ia no encontrado.");

        statusIA.innerHTML = "<span class='text-primary animate-pulse'>Cargando motor de IA...</span>";
        
        if (typeof faceapi === 'undefined') {
            throw new Error("Librería face-api.js no cargada.");
        }

        console.log("Cargando modelos desde:", finalModelUrl);

        // Carga paralela para máxima velocidad
        const resultados = await Promise.allSettled([
            faceapi.nets.tinyFaceDetector.loadFromUri(finalModelUrl),
            faceapi.nets.faceLandmark68Net.loadFromUri(finalModelUrl),
            faceapi.nets.faceRecognitionNet.loadFromUri(finalModelUrl),
            faceapi.nets.faceExpressionNet.loadFromUri(finalModelUrl)
        ]);

        const fallos = resultados.filter(r => r.status === 'rejected');
        if (fallos.length > 0) {
            console.error("Fallos en carga de modelos:", fallos);
            throw new Error("No se pudieron cargar los modelos de IA.");
        }

        console.log("Todos los modelos listos para el registro.");

        const iniciarDeteccion = () => {
            statusIA.innerHTML = "<span class='text-success'>IA Lista</span>";
            const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
            faceapi.matchDimensions(canvas, displaySize);

            setInterval(async () => {
                if (video.paused || video.ended) return;

                try {
                    // Verificación de salud de modelos antes de detectar
                    if (!faceapi.nets.tinyFaceDetector.params) {
                        console.warn("Re-cargando pesos del detector...");
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
                        statusIA.innerHTML = "<span class='text-success fw-bold'>Rostro Detectado ✓</span>";
                        btnGuardar.disabled = false;
                    } else {
                        statusIA.innerHTML = "<span class='text-danger'>Encuadre su rostro</span>";
                        btnGuardar.disabled = true;
                    }
                } catch (e) {
                    console.error("Error en detección facial:", e);
                    // Si el error es persistente, informar al usuario
                    if (e.message.includes('weights')) {
                        statusIA.innerHTML = "<span class='text-danger'>Error: Modelos corruptos o no cargados</span>";
                    }
                }
            }, 500);
        };

        // Si el video ya está reproduciéndose, iniciar de inmediato
        if (!video.paused) {
            iniciarDeteccion();
        } else {
            video.onplay = iniciarDeteccion;
        }

    } catch (error) {
        statusIA.innerHTML = "<span class='text-danger'>Error: Verifique assets/models/</span>";
        console.error(error);
    }
}

// Lógica de envío del formulario
document.getElementById('formRegistroEmpleado').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch('../models/guardar_empleado.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.status === 'success') {
            Swal.fire('¡Éxito!', 'Empleado registrado correctamente', 'success').then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch {
        Swal.fire('Error', 'Fallo de servidor', 'error');
    }
});

cargarModelosYCamara();