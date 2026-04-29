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
                } catch { return null; }
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
    } catch {
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
            
            // Liveness Detection Básica: Verificar parpadeo o movimiento ocular leve
            // Usamos la distancia entre párpados (índices 37-41 y 44-47 de landmarks)
            const landmarks = detection.landmarks;
            const leftEye = landmarks.getLeftEye();
            const rightEye = landmarks.getRightEye();
            
            // Calculamos ratio de apertura (EAR simplificado)
            const eyeDist = (p1, p2) => Math.sqrt(Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2));
            const leftEAR = eyeDist(leftEye[1], leftEye[5]) / eyeDist(leftEye[0], leftEye[3]);
            
            let livenessStatus = "<span class='badge bg-info'>Analizando Vida...</span>";
            let isLive = leftEAR > 0.2; // Umbral simple para ojos abiertos

            // Dibujar visuales (cuadro, puntos y expresiones)
            faceapi.draw.drawDetections(canvas, resizedDetections);
            
            if (faceMatcher) {
                const match = faceMatcher.findBestMatch(detection.descriptor);
                
                if (match.label !== 'unknown') {
                    idEmpleadoDetectado = match.label;
                    
                    if (isLive) {
                        status.innerHTML = `<span class='badge bg-success p-2 shadow-sm pulse-green'><i class='bi bi-patch-check-fill'></i> Humano Detectado - ID: ${idEmpleadoDetectado}</span>`;
                        btnMarcar.disabled = false;
                        btnSalida.disabled = false;
                    } else {
                        status.innerHTML = `<span class='badge bg-danger p-2'><i class='bi bi-exclamation-triangle'></i> Posible Foto Detectada</span>`;
                        btnMarcar.disabled = true;
                        btnSalida.disabled = true;
                    }
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

    } catch {
        Swal.fire('Error', 'Fallo en la comunicación', 'error');
    } finally {
        btnActual.disabled = false;
    }
}

// Eventos para cada botón
btnMarcar.addEventListener('click', () => procesarAsistencia('entrada'));
btnSalida.addEventListener('click', () => procesarAsistencia('salida'));

iniciarSistema();