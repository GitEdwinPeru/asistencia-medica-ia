const video = document.getElementById('video');
const status = document.getElementById('status');
const btnMarcar = document.getElementById('btn-marcar-asistencia');
const btnSalida = document.getElementById('btn-marcar-salida'); // Nuevo botón de salida
const canvas = document.getElementById('overlay'); 

const MODEL_URL = '../assets/models/';
let faceMatcher = null;
let idEmpleadoDetectado = null;

const obtenerRutaModel = (archivo) => `../models/${archivo}`;

async function iniciarSistema() {
    try {
        status.innerHTML = "<span class='badge bg-info p-2 animate-pulse'>Cargando Modelos locales...</span>";

        // 1. Cargar redes neuronales incluyendo expresiones para el efecto visual
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
            faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
        ]);
        
        console.log("IA Cargada con expresiones desde servidor local");

        await iniciarCamara();

        // 2. Obtener empleados para el reconocimiento
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
        status.innerHTML = "<span class='badge bg-danger'>Error al cargar el sistema</span>";
    }
}

async function iniciarCamara() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
        video.srcObject = stream;
        
        video.onloadedmetadata = () => {
            video.play();
            reconocimientoContinuo();
        };
    } catch (err) {
        status.innerHTML = "<span class='badge bg-danger'>Cámara bloqueada</span>";
    }
}

function reconocimientoContinuo() {
    const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
    faceapi.matchDimensions(canvas, displaySize);

    setInterval(async () => {
        if (video.paused || video.ended) return;

        const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptor()
            .withFaceExpressions();

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (detection) {
            const resizedDetections = faceapi.resizeResults(detection, displaySize);
            
            // Dibujar visuales (cuadro, puntos y expresiones)
            faceapi.draw.drawDetections(canvas, resizedDetections);
            faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
            faceapi.draw.drawFaceExpressions(canvas, resizedDetections);

            if (faceMatcher) {
                const match = faceMatcher.findBestMatch(detection.descriptor);
                
                if (match.label !== 'unknown') {
                    idEmpleadoDetectado = match.label;
                    status.innerHTML = `<span class='badge bg-success p-2 shadow-sm'>Identificado ID: ${idEmpleadoDetectado}</span>`;
                    btnMarcar.disabled = false; // Habilita Entrada
                    btnSalida.disabled = false;  // Habilita Salida
                } else {
                    idEmpleadoDetectado = null;
                    status.innerHTML = "<span class='badge bg-secondary p-2'>Rostro no reconocido</span>";
                    btnMarcar.disabled = true;
                    btnSalida.disabled = true;
                }
            }
        } else {
            status.innerHTML = "<span class='badge bg-warning text-dark p-2'>Buscando rostro...</span>";
            btnMarcar.disabled = true;
            btnSalida.disabled = true;
        }
    }, 500);
}

// Función para procesar el registro (Entrada o Salida)
async function procesarAsistencia(tipo) {
    if (!idEmpleadoDetectado) return;
    
    const btnActual = (tipo === 'entrada') ? btnMarcar : btnSalida;
    btnActual.disabled = true;

    const formData = new FormData();
    formData.append('id_empleado', idEmpleadoDetectado);
    formData.append('tipo_registro', tipo); // Envía el tipo de acción

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
        Swal.fire('Error', 'Fallo en la comunicación', 'error');
    } finally {
        btnActual.disabled = false;
    }
}

// Eventos para cada botón
btnMarcar.addEventListener('click', () => procesarAsistencia('entrada'));
btnSalida.addEventListener('click', () => procesarAsistencia('salida'));

iniciarSistema();