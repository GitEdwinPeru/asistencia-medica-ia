const video = document.getElementById('video');
const status = document.getElementById('status');
const btnMarcar = document.getElementById('btn-marcar-asistencia');

// Ruta a los modelos locales (asegúrate de tener los 7 archivos descargados)
const MODEL_URL = '../assets/models/';
let faceMatcher = null;
let idEmpleadoDetectado = null;

const obtenerRutaModel = (archivo) => `../models/${archivo}`;

async function iniciarSistema() {
    try {
        status.innerHTML = "<span class='badge bg-info p-2 animate-pulse'>Cargando Modelos locales...</span>";

        // 1. Cargar redes neuronales desde la carpeta local
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        ]);
        
        console.log("IA Cargada desde servidor local");

        // 2. IMPORTANTE: Iniciamos la cámara antes de cargar la base de datos para evitar bloqueos visuales
        await iniciarCamara();

        // 3. Obtener empleados y sus rostros
        const res = await fetch(obtenerRutaModel('obtener_empleados_fotos.php'));
        const textoBruto = await res.text();
        
        let empleados = [];
        const indiceJson = textoBruto.indexOf('[');
        if (indiceJson !== -1) {
            empleados = JSON.parse(textoBruto.substring(indiceJson));
        }
        
        if (empleados.length > 0) {
            const labeledDescriptors = empleados.map(emp => {
                try {
                    const desc = typeof emp.rostro_embedding === 'string' 
                        ? JSON.parse(emp.rostro_embedding) : emp.rostro_embedding;
                    return new faceapi.LabeledFaceDescriptors(emp.id.toString(), [new Float32Array(desc)]);
                } catch (err) { return null; }
            }).filter(d => d !== null);

            faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
            console.log("Base de datos de rostros lista");
        }

    } catch (error) {
        console.error("Error crítico:", error);
        status.innerHTML = "<span class='badge bg-danger'>Error: Revisa consola o archivos de modelos</span>";
    }
}

async function iniciarCamara() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
        video.srcObject = stream;
        
        // Esperamos a que el video esté listo para empezar el reconocimiento
        video.onloadedmetadata = () => {
            video.play();
            reconocimientoContinuo();
        };
    } catch (err) {
        console.error("Error de cámara:", err);
        status.innerHTML = "<span class='badge bg-danger'>Cámara no encontrada o bloqueada</span>";
    }
}

function reconocimientoContinuo() {
    setInterval(async () => {
        // Si aún no carga la base de datos de empleados, solo mostramos que está buscando
        if (video.paused || video.ended) return;

        const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (detection) {
            // Si ya tenemos el faceMatcher cargado, comparamos
            if (faceMatcher) {
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
                status.innerHTML = "<span class='badge bg-info p-2'>IA lista, cargando personal...</span>";
            }
        } else {
            status.innerHTML = "<span class='badge bg-warning text-dark p-2'>Buscando rostro...</span>";
            btnMarcar.disabled = true;
        }
    }, 500); // Frecuencia de 500ms para mejor respuesta
}

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
            timer: 3000
        });

    } catch (error) {
        Swal.fire('Error', 'Fallo en la comunicación con el servidor', 'error');
    } finally {
        btnMarcar.disabled = false;
    }
});

iniciarSistema();