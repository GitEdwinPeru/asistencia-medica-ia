const video = document.getElementById('video');
const statusDiv = document.getElementById('status');
const btnMarcar = document.getElementById('btn-marcar-asistencia');
const btnSalida = document.getElementById('btn-marcar-salida');
const canvas = document.getElementById('overlay');
const selectSede = document.getElementById('sede-asistencia');
const sedeWarning = document.getElementById('sede-warning');

console.log('Script camara.js cargado correctamente');

const finalModelUrl = (typeof MODEL_URL !== 'undefined') ? MODEL_URL : '../assets/models/';

let faceMatcher = null;
let idEmpleadoDetectado = null;
let recognitionIntervalId = null;
let reconocimientoActivo = false;

const obtenerRutaModel = (archivo) => `../models/${archivo}`;

function normalizarError(error, fallback = 'Ocurrio un error inesperado') {
    if (error instanceof Error) return error;

    if (typeof error === 'string' && error.trim() !== '') {
        return new Error(error);
    }

    if (error && typeof error === 'object') {
        const mensaje = error.message || error.error || error.details || error.name;
        if (typeof mensaje === 'string' && mensaje.trim() !== '') {
            return new Error(mensaje);
        }

        try {
            return new Error(JSON.stringify(error));
        } catch {
            return new Error(fallback);
        }
    }

    return new Error(fallback);
}

async function cargarSedes() {
    try {
        const res = await fetch(obtenerRutaModel('obtener_sedes.php'));
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

        const sedes = await res.json();
        if (!Array.isArray(sedes) || !selectSede) return;

        sedes.forEach((sede) => {
            const option = document.createElement('option');
            option.value = sede.id;
            option.textContent = sede.nombre;
            selectSede.appendChild(option);
        });
    } catch (error) {
        console.error('Error al cargar sedes:', normalizarError(error));
    }
}

async function iniciarSistema() {
    console.log('--- INICIANDO SISTEMA DE ASISTENCIA FACIAL ---');

    if (!video || !canvas) {
        console.error('Faltan elementos criticos: video o canvas no encontrados.');
        return;
    }

    iniciarCamara().catch((err) => {
        console.error('Fallo inicial de camara:', normalizarError(err));
    });

    try {
        if (!statusDiv) {
            console.warn("No se encontro el elemento 'status' para mostrar progreso.");
        } else {
            statusDiv.innerHTML = "<span class='badge bg-info p-2 animate-pulse'><i class='bi bi-cpu me-2'></i>Inicializando IA...</span>";
        }

        if (typeof faceapi === 'undefined') {
            throw new Error('Libreria face-api.js no cargada.');
        }

        console.log('Cargando modelos desde:', finalModelUrl);

        const cargarRecurso = (name, task) => task.catch((error) => {
            throw normalizarError(error, `No se pudo cargar ${name}`);
        });

        const recursos = [
            { name: 'Sedes', task: cargarRecurso('Sedes', cargarSedes()) },
            { name: 'Detector', task: cargarRecurso('Detector', faceapi.nets.tinyFaceDetector.loadFromUri(finalModelUrl)) },
            { name: 'Landmarks', task: cargarRecurso('Landmarks', faceapi.nets.faceLandmark68Net.loadFromUri(finalModelUrl)) },
            { name: 'Recognition', task: cargarRecurso('Recognition', faceapi.nets.faceRecognitionNet.loadFromUri(finalModelUrl)) },
            { name: 'Expressions', task: cargarRecurso('Expressions', faceapi.nets.faceExpressionNet.loadFromUri(finalModelUrl)) }
        ];

        const resultados = await Promise.allSettled(recursos.map((recurso) => recurso.task));

        resultados.forEach((resultado, index) => {
            if (resultado.status === 'rejected') {
                console.error(`Fallo al cargar ${recursos[index].name}:`, normalizarError(resultado.reason));
            } else {
                console.log(`OK ${recursos[index].name} cargado correctamente.`);
            }
        });

        const falloModelos = resultados.some((resultado, index) =>
            resultado.status === 'rejected' && recursos[index].name !== 'Sedes'
        );

        if (falloModelos) {
            throw new Error('Fallo la carga de uno o mas modelos de IA.');
        }

        console.log('Todos los modelos de IA estan listos.');
        console.log('Obteniendo base de datos de rostros...');

        const res = await fetch(obtenerRutaModel('obtener_empleados_fotos.php'));
        if (!res.ok) throw new Error(`Error HTTP al obtener empleados: ${res.status}`);

        const textoBruto = await res.text();
        let empleados = [];
        const indiceJson = textoBruto.indexOf('[');

        if (indiceJson !== -1) {
            try {
                empleados = JSON.parse(textoBruto.substring(indiceJson));
            } catch (error) {
                console.error('Error al procesar JSON de empleados:', normalizarError(error));
            }
        }

        if (empleados.length > 0) {
            const labeledDescriptors = empleados.map((emp) => {
                try {
                    const desc = typeof emp.rostro_embedding === 'string'
                        ? JSON.parse(emp.rostro_embedding)
                        : emp.rostro_embedding;

                    return new faceapi.LabeledFaceDescriptors(emp.id.toString(), [new Float32Array(desc)]);
                } catch {
                    return null;
                }
            }).filter((descriptor) => descriptor !== null);

            if (labeledDescriptors.length === 0) {
                console.warn('No se encontraron descriptores validos en la base de datos.');
            } else {
                faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
                console.log(`Base de datos de rostros lista con ${labeledDescriptors.length} empleados.`);
            }
        }
    } catch (error) {
        const errorNormalizado = normalizarError(error, 'Error critico en el sistema');
        console.error('Fallo critico en el sistema:', errorNormalizado);

        if (statusDiv) {
            statusDiv.innerHTML = `<div class='alert alert-danger py-2 small'>
                <strong><i class='bi bi-exclamation-triangle-fill'></i> Error de Sistema:</strong><br>
                ${errorNormalizado.message}<br>
                <button class='btn btn-sm btn-outline-danger mt-2' onclick='location.reload()'>Reintentar</button>
            </div>`;
        }
    }
}

async function iniciarCamara() {
    console.log('Intentando acceder a la camara...');

    try {
        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            throw new Error('Este navegador no soporta acceso a la camara.');
        }

        const constraints = {
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            }
        };

        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        console.log('Stream de camara obtenido con exito');

        video.srcObject = stream;

        const iniciarVideo = async () => {
            if (reconocimientoActivo) return;

            console.log('Metadatos de video cargados. Iniciando reproduccion...');
            await video.play();
            console.log('Video reproduciendose. Iniciando loop de reconocimiento.');
            reconocimientoContinuo();
            reconocimientoActivo = true;
        };

        video.addEventListener('loadedmetadata', () => {
            iniciarVideo().catch((error) => {
                console.error('Error al iniciar reproduccion de video:', normalizarError(error));
            });
        }, { once: true });

        if (video.readyState >= 1) {
            await iniciarVideo();
        }
    } catch (error) {
        const errorNormalizado = normalizarError(error, 'Error: Camara no disponible');
        console.error('ERROR CRITICO DE CAMARA:', errorNormalizado);

        let msg = 'Error: Camara no disponible';
        if (errorNormalizado.name === 'NotAllowedError') {
            msg = 'Permiso denegado para usar la camara';
        } else if (errorNormalizado.name === 'NotFoundError') {
            msg = 'No se encontro ninguna camara conectada';
        } else if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
            msg = 'La camara requiere una conexion segura (HTTPS)';
        } else if (errorNormalizado.message) {
            msg = errorNormalizado.message;
        }

        if (statusDiv) {
            statusDiv.innerHTML = `<span class='badge bg-danger p-2'><i class='bi bi-camera-video-off'></i> ${msg}</span>`;
        }
    }
}

function actualizarEstadoBotones(identificado) {
    if (!selectSede || !btnMarcar || !btnSalida || !sedeWarning) return;

    const sedeSeleccionada = selectSede.value !== '';

    if (identificado && sedeSeleccionada) {
        btnMarcar.disabled = false;
        btnSalida.disabled = false;
        sedeWarning.classList.add('d-none');
    } else {
        btnMarcar.disabled = true;
        btnSalida.disabled = true;

        if (identificado && !sedeSeleccionada) {
            sedeWarning.classList.remove('d-none');
        } else {
            sedeWarning.classList.add('d-none');
        }
    }
}

function reconocimientoContinuo() {
    if (!video || !canvas || recognitionIntervalId) return;

    const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
    faceapi.matchDimensions(canvas, displaySize);

    recognitionIntervalId = setInterval(async () => {
        if (video.paused || video.ended) return;

        try {
            const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor()
                .withFaceExpressions();

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (detection) {
                const resizedDetections = faceapi.resizeResults(detection, displaySize);
                const landmarks = detection.landmarks;
                const leftEye = landmarks.getLeftEye();
                const eyeDist = (p1, p2) => Math.sqrt(((p1.x - p2.x) ** 2) + ((p1.y - p2.y) ** 2));
                const leftEAR = eyeDist(leftEye[1], leftEye[5]) / eyeDist(leftEye[0], leftEye[3]);
                const isLive = leftEAR > 0.2;

                faceapi.draw.drawDetections(canvas, resizedDetections);

                if (faceMatcher) {
                    const match = faceMatcher.findBestMatch(detection.descriptor);

                    if (match.label !== 'unknown') {
                        idEmpleadoDetectado = match.label;

                        if (isLive) {
                            statusDiv.innerHTML = `<span class='badge bg-success p-2 shadow-sm pulse-green'><i class='bi bi-patch-check-fill'></i> Humano Detectado - ID: ${idEmpleadoDetectado}</span>`;
                            actualizarEstadoBotones(true);
                        } else {
                            statusDiv.innerHTML = "<span class='badge bg-danger p-2'><i class='bi bi-exclamation-triangle'></i> Posible foto detectada</span>";
                            actualizarEstadoBotones(false);
                        }
                    } else {
                        idEmpleadoDetectado = null;
                        statusDiv.innerHTML = "<span class='badge bg-secondary p-2'>Rostro no reconocido</span>";
                        actualizarEstadoBotones(false);
                    }
                }
            } else {
                statusDiv.innerHTML = "<span class='badge bg-warning text-dark p-2'>Buscando rostro...</span>";
                actualizarEstadoBotones(false);
            }
        } catch (error) {
            console.error('Error en loop de reconocimiento:', normalizarError(error));
        }
    }, 500);
}

if (selectSede) {
    selectSede.addEventListener('change', () => {
        actualizarEstadoBotones(idEmpleadoDetectado !== null);
    });
}

async function procesarAsistencia(tipo) {
    if (!idEmpleadoDetectado || !selectSede || selectSede.value === '') return;

    const btnActual = (tipo === 'entrada') ? btnMarcar : btnSalida;
    if (btnActual) btnActual.disabled = true;

    const formData = new FormData();
    formData.append('id_empleado', idEmpleadoDetectado);
    formData.append('tipo_registro', tipo);
    formData.append('id_distrito', selectSede.value);

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
        console.error('Error al registrar asistencia:', normalizarError(error));
        Swal.fire('Error', 'Fallo en la comunicacion', 'error');
    } finally {
        if (btnActual) btnActual.disabled = false;
    }
}

if (btnMarcar) btnMarcar.addEventListener('click', () => procesarAsistencia('entrada'));
if (btnSalida) btnSalida.addEventListener('click', () => procesarAsistencia('salida'));

window.addEventListener('unhandledrejection', (event) => {
    console.error('Promesa no controlada en camara.js:', normalizarError(event.reason, 'Promesa rechazada sin detalle'));
});

iniciarSistema().catch((err) => {
    console.error('Fallo global en camara.js:', normalizarError(err));
});
