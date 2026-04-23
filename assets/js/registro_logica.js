const video = document.getElementById('video');
const canvas = document.getElementById('overlay');
const statusIA = document.getElementById('status-ia');
const btnGuardar = document.getElementById('btn-guardar');
const descriptorInput = document.getElementById('descriptor_input');

const MODEL_URL = '../assets/models/';

async function cargarModelosYCamara() {
    try {
        statusIA.innerHTML = "<span class='text-primary'>Cargando motor de IA...</span>";
        
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);

        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;

        video.onplay = () => {
            statusIA.innerHTML = "<span class='text-success'>IA Lista</span>";
            const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
            faceapi.matchDimensions(canvas, displaySize);

            setInterval(async () => {
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

                if (detection) {
                    const resizedDetections = faceapi.resizeResults(detection, displaySize);
                    faceapi.draw.drawDetections(canvas, resizedDetections);

                    descriptorInput.value = JSON.stringify(Array.from(detection.descriptor));
                    statusIA.innerHTML = "<span class='text-success fw-bold'>Rostro Detectado ✓</span>";
                    btnGuardar.disabled = false;
                } else {
                    statusIA.innerHTML = "<span class='text-danger'>Encuadre su rostro</span>";
                    btnGuardar.disabled = true;
                }
            }, 500);
        };

    } catch (error) {
        statusIA.innerHTML = "<span class='text-danger'>Error: Verifique assets/models/</span>";
    }
}

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
    } catch (error) {
        Swal.fire('Error', 'Fallo de servidor', 'error');
    }
});

cargarModelosYCamara();