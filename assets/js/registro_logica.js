const video = document.getElementById('video');
const canvas = document.getElementById('overlay');
const statusIA = document.getElementById('status-ia');
const btnGuardar = document.getElementById('btn-guardar');
const descriptorInput = document.getElementById('descriptor_input');

const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/';

async function cargarModelosYCamara() {
    try {
        statusIA.innerHTML = "<span class='text-primary'>Iniciando IA Local...</span>";
        
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);

        statusIA.innerHTML = "<span class='text-warning'>Encendiendo cámara...</span>";
        
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;

        // Una vez que el video cargue, empezamos a detectar
        video.onplay = () => {
            const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
            faceapi.matchDimensions(canvas, displaySize);

            setInterval(async () => {
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (detection) {
                    // Dibujar en el canvas para feedback visual
                    const resizedDetections = faceapi.resizeResults(detection, displaySize);
                    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                    faceapi.draw.drawDetections(canvas, resizedDetections);

                    // Guardar el descriptor en el input oculto
                    descriptorInput.value = JSON.stringify(Array.from(detection.descriptor));
                    
                    statusIA.innerHTML = "<span class='text-success fw-bold'>Rostro Detectado ✓</span>";
                    btnGuardar.disabled = false; // Habilitar el botón
                } else {
                    statusIA.innerHTML = "<span class='text-danger'>Posicione su rostro frente a la cámara</span>";
                    btnGuardar.disabled = true;
                }
            }, 500);
        };

    } catch (error) {
        console.error(error);
        statusIA.innerHTML = "<span class='text-danger'>Error: No se pudo acceder a la cámara</span>";
    }
}

// Lógica para enviar el formulario por AJAX
document.getElementById('formRegistroEmpleado').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);

    try {
        const response = await fetch('../models/guardar_empleado.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status === 'success') {
            Swal.fire('¡Éxito!', result.message, 'success').then(() => {
                window.location.reload(); // Recargar para nuevo registro
            });
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Fallo en la conexión con el servidor', 'error');
    }
});

cargarModelosYCamara();