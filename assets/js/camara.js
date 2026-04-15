const video = document.getElementById('video');
const status = document.getElementById('status');
const btnMarcar = document.getElementById('btn-marcar-asistencia');

// Pesos de la IA desde GitHub
const MODEL_URL = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/';

let faceMatcher = null;
let idEmpleadoDetectado = null;

// Función para asegurar que la ruta al modelo sea correcta desde /views/
const obtenerRutaModel = (archivo) => `../models/${archivo}`;

async function iniciarSistema() {
    try {
        status.innerHTML = "<span class='badge bg-info p-2 animate-pulse'>Cargando Modelos locales...</span>";

        // 1. Cargar redes neuronales
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        
        console.log("IA Cargada desde servidor local");

        // 2. Obtener empleados y sus rostros
        const res = await fetch(obtenerRutaModel('obtener_empleados_fotos.php'));
        const textoBruto = await res.text();
        
        let empleados = [];
        try {
            // Buscamos donde empieza el JSON real por si PHP mandó un error de texto antes
            const indiceJson = textoBruto.indexOf('[');
            if (indiceJson !== -1) {
                empleados = JSON.parse(textoBruto.substring(indiceJson));
            } else {
                throw new Error("Respuesta del servidor no es JSON válido");
            }
        } catch (e) {
            console.error("Error en formato de datos:", textoBruto);
        }
        
        if (empleados.length > 0) {
            const labeledDescriptors = empleados.map(emp => {
                try {
                    const desc = typeof emp.rostro_embedding === 'string' 
                        ? JSON.parse(emp.rostro_embedding) : emp.rostro_embedding;
                    return new faceapi.LabeledFaceDescriptors(emp.id.toString(), [new Float32Array(desc)]);
                } catch (err) { return null; }
            }).filter(d => d !== null);

            faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6); // 0.6 de precisión
        }

        iniciarCamara();
    } catch (error) {
        console.error(error);
        status.innerHTML = "<span class='badge bg-danger'>Error al iniciar sistema</span>";
    }
}

function iniciarCamara() {
    navigator.mediaDevices.getUserMedia({ video: {} })
        .then(stream => { 
            video.srcObject = stream; 
            reconocimientoContinuo(); 
        })
        .catch(err => { 
            console.error(err);
            status.innerHTML = "<span class='badge bg-danger'>Cámara no encontrada</span>"; 
        });
}

function reconocimientoContinuo() {
    setInterval(async () => {
        if (!faceMatcher) return;

        const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (detection) {
            const match = faceMatcher.findBestMatch(detection.descriptor);
            
            if (match.label !== 'unknown') {
                idEmpleadoDetectado = match.label;
                status.innerHTML = `<span class='badge bg-success p-2 shadow-sm'>Identificado ID: ${idEmpleadoDetectado}</span>`;
                btnMarcar.disabled = false;
            } else {
                idEmpleadoDetectado = null;
                status.innerHTML = "<span class='badge bg-secondary p-2'>Rostro no reconocido</span>";
                btnMarcar.disabled = true;
            }
        } else {
            status.innerHTML = "<span class='badge bg-warning text-dark p-2'>Buscando rostro...</span>";
            btnMarcar.disabled = true;
        }
    }, 600);
}

// Evento de clic para registrar
btnMarcar.addEventListener('click', async () => {
    if (!idEmpleadoDetectado) return;
    
    btnMarcar.disabled = true;
    const formData = new FormData();
    formData.append('id_empleado', idEmpleadoDetectado);

    try {
        const response = await fetch(obtenerRutaModel('registrar_asistencia.php'), {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        Swal.fire({
            icon: data.status === 'success' ? 'success' : 'error',
            title: data.message,
            text: data.detalle || '',
            timer: 4000
        });

    } catch (error) {
        console.error("Error en registro:", error);
        Swal.fire('Error', 'Fallo en la comunicación con el servidor', 'error');
    } finally {
        btnMarcar.disabled = false;
    }
});

// Arrancar
iniciarSistema();