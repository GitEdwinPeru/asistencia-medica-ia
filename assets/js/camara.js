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
let sistemaListo = false;
let descriptorDetectado = null;
let registroEnProceso = false;
let ultimoRegistroTs = 0;

const obtenerRutaModel = (archivo) => `/asistencia_facial/models/${archivo}`;
const attendanceHeaders = () => ({
    'X-Attendance-Token': (typeof ATTENDANCE_TOKEN !== 'undefined') ? ATTENDANCE_TOKEN : ''
});

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

function estadoHtml(tipo, titulo, detalle = '', acciones = '') {
    const iconos = {
        info: 'bi-info-circle-fill',
        danger: 'bi-camera-video-off-fill',
        warning: 'bi-exclamation-triangle-fill',
        success: 'bi-patch-check-fill'
    };
    const clase = tipo === 'danger' ? 'alert-danger' : tipo === 'warning' ? 'alert-warning' : tipo === 'success' ? 'alert-success' : 'alert-info';
    return `<div class='alert ${clase} py-2 small text-start mb-0'>
        <div class='fw-bold'><i class='bi ${iconos[tipo] || iconos.info} me-1'></i>${titulo}</div>
        ${detalle ? `<div class='mt-1'>${detalle}</div>` : ''}
        ${acciones ? `<div class='mt-2 d-flex flex-wrap gap-2'>${acciones}</div>` : ''}
    </div>`;
}

function mostrarErrorCamara(errorNormalizado) {
    let titulo = 'No se pudo iniciar la cámara';
    let detalle = 'Verifique que el equipo tenga cámara conectada y que ningún otro programa la esté usando.';

    if (errorNormalizado.name === 'NotAllowedError') {
        titulo = 'Permiso de cámara denegado';
        detalle = 'Active el permiso de cámara del navegador y vuelva a intentar. En Chrome puede hacerlo desde el icono de candado junto a la dirección.';
    } else if (errorNormalizado.name === 'NotFoundError') {
        titulo = 'No se encontró una cámara';
        detalle = 'Conecte una cámara o revise que Windows la reconozca antes de marcar asistencia.';
    } else if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
        titulo = 'La cámara requiere conexión segura';
        detalle = 'Abra el sistema en localhost o configure HTTPS para permitir el acceso a cámara.';
    } else if (errorNormalizado.message) {
        detalle = errorNormalizado.message;
    }

    if (statusDiv) {
        statusDiv.innerHTML = estadoHtml('danger', titulo, detalle, "<button class='btn btn-sm btn-outline-danger' onclick='location.reload()'>Reintentar</button>");
    }
}

async function leerJsonSeguro(response, contexto = 'respuesta del servidor') {
    const texto = await response.text();

    if (!response.ok) {
        let mensaje = `Error HTTP ${response.status} en ${contexto}`;

        try {
            const errorJson = JSON.parse(texto);
            mensaje = errorJson.message || errorJson.error || mensaje;
        } catch {
            if (texto.trim() !== '') mensaje = texto.trim();
        }

        throw new Error(mensaje);
    }

    try {
        return JSON.parse(texto);
    } catch {
        const muestra = texto.trim().slice(0, 120) || 'respuesta vacia';
        throw new Error(`El servidor no devolvio JSON valido en ${contexto}: ${muestra}`);
    }
}

async function cargarSedes() {
    try {
        const res = await fetch(obtenerRutaModel('obtener_sedes.php'), { headers: attendanceHeaders() });
        const sedes = await leerJsonSeguro(res, 'sedes');

        if (sedes && sedes.status === 'error') {
            throw new Error(sedes.message || 'No se pudieron cargar las sedes');
        }

        if (!Array.isArray(sedes) || !selectSede) return;

        sedes.forEach((sede) => {
            const option = document.createElement('option');
            option.value = sede.id;
            option.textContent = sede.nombre;
            selectSede.appendChild(option);
        });
    } catch (error) {
        const errorNormalizado = normalizarError(error);
        console.error('Error al cargar sedes:', errorNormalizado);
        throw errorNormalizado;
    }
}

async function cargarBaseFacial() {
    const res = await fetch(obtenerRutaModel('obtener_empleados_fotos.php'), { headers: attendanceHeaders() });
    const respuestaEmpleados = await leerJsonSeguro(res, 'empleados con rostros');

    if (respuestaEmpleados && respuestaEmpleados.error) {
        throw new Error(respuestaEmpleados.error);
    }

    const empleados = Array.isArray(respuestaEmpleados) ? respuestaEmpleados : [];
    const labeledDescriptors = empleados.map((emp) => {
        try {
            const desc = typeof emp.rostro_embedding === 'string'
                ? JSON.parse(emp.rostro_embedding)
                : emp.rostro_embedding;

            if (!Array.isArray(desc) || desc.length !== 128) return null;
            return new faceapi.LabeledFaceDescriptors(emp.id.toString(), [new Float32Array(desc)]);
        } catch {
            return null;
        }
    }).filter((descriptor) => descriptor !== null);

    if (labeledDescriptors.length === 0) {
        faceMatcher = null;
        throw new Error('No hay rostros válidos cargados. Registre empleados con descriptor facial primero.');
    }

    faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
    console.log(`Base de datos de rostros lista con ${labeledDescriptors.length} empleados.`);
    return labeledDescriptors.length;
}

window.refrescarBaseFacial = async () => {
    if (statusDiv) {
        statusDiv.innerHTML = estadoHtml('info', 'Actualizando base facial...', 'Un momento mientras se cargan los rostros registrados.');
    }

    try {
        const total = await cargarBaseFacial();
        sistemaListo = true;
        if (statusDiv) {
            statusDiv.innerHTML = estadoHtml('success', 'Base facial actualizada', `${total} rostros disponibles para reconocimiento.`);
        }
    } catch (error) {
        const errorNormalizado = normalizarError(error);
        if (statusDiv) {
            statusDiv.innerHTML = estadoHtml('warning', 'No se pudo actualizar la base facial', errorNormalizado.message, "<a class='btn btn-sm btn-outline-primary' href='views/empleados_sin_rostro.php'>Ver empleados sin rostro</a>");
        }
    }
};

async function iniciarSistema() {
    console.log('--- INICIANDO SISTEMA DE ASISTENCIA FACIAL ---');

    if (!video || !canvas) {
        console.error('Faltan elementos criticos: video o canvas no encontrados.');
        return;
    }

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

        await cargarBaseFacial();

        sistemaListo = true;
        await iniciarCamara();
    } catch (error) {
        const errorNormalizado = normalizarError(error, 'Error critico en el sistema');
        console.error('Fallo critico en el sistema:', errorNormalizado);

        if (statusDiv) {
            statusDiv.innerHTML = `<div class='alert alert-danger py-2 small'>
                <strong><i class='bi bi-exclamation-triangle-fill'></i> Error de Sistema:</strong><br>
                ${errorNormalizado.message}<br>
                <button class='btn btn-sm btn-outline-danger mt-2' onclick='location.reload()'>Reintentar</button>
                <a class='btn btn-sm btn-outline-primary mt-2 ms-1' href='views/empleados_sin_rostro.php'>Ver pendientes</a>
            </div>`;
        }
        detenerReconocimiento();
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
            if (!sistemaListo) {
                console.warn('La camara esta lista, pero el reconocimiento espera a que la IA y la base de datos carguen.');
                return;
            }

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

        mostrarErrorCamara(errorNormalizado);
    }
}

function detenerReconocimiento() {
    sistemaListo = false;
    reconocimientoActivo = false;

    if (recognitionIntervalId) {
        clearInterval(recognitionIntervalId);
        recognitionIntervalId = null;
    }

    idEmpleadoDetectado = null;
    descriptorDetectado = null;
    actualizarEstadoBotones(false);
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
                        descriptorDetectado = Array.from(detection.descriptor);
                        const distancia = Number(match.distance || 0);
                        const confianza = Math.max(0, Math.min(100, Math.round((1 - (distancia / 0.6)) * 100)));

                        if (isLive) {
                            statusDiv.innerHTML = estadoHtml(
                                'success',
                                `Rostro reconocido - ID ${idEmpleadoDetectado}`,
                                `Confianza aproximada: ${confianza}% | Distancia facial: ${distancia.toFixed(3)}`
                            );
                            actualizarEstadoBotones(true);
                        } else {
                            statusDiv.innerHTML = estadoHtml('warning', 'Posible foto detectada', 'Parpadee o mueva ligeramente el rostro frente a la cámara.');
                            actualizarEstadoBotones(false);
                        }
                    } else {
                        idEmpleadoDetectado = null;
                        descriptorDetectado = null;
                        statusDiv.innerHTML = estadoHtml('warning', 'Rostro no reconocido', 'Si el colaborador ya fue registrado, pulse “Actualizar rostros”.');
                        actualizarEstadoBotones(false);
                    }
                }
            } else {
                descriptorDetectado = null;
                statusDiv.innerHTML = estadoHtml('info', 'Buscando rostro...', 'Ubíquese de frente, con buena luz y sin cubrir el rostro.');
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
    if (!idEmpleadoDetectado || !descriptorDetectado || !selectSede || selectSede.value === '') return;
    if (registroEnProceso) return;

    const ahora = Date.now();
    if (ahora - ultimoRegistroTs < 5000) {
        UIFeedback.warning('Espere un momento', 'Ya se está procesando una marcación reciente.');
        return;
    }

    const btnActual = (tipo === 'entrada') ? btnMarcar : btnSalida;
    registroEnProceso = true;
    ultimoRegistroTs = ahora;
    if (btnActual) {
        btnActual.disabled = true;
        btnActual.dataset.originalText = btnActual.innerHTML;
        btnActual.innerHTML = "<span class='spinner-border spinner-border-sm me-1'></span> Procesando...";
    }
    if (btnMarcar) btnMarcar.disabled = true;
    if (btnSalida) btnSalida.disabled = true;

    const formData = new FormData();
    formData.append('id_empleado', idEmpleadoDetectado);
    formData.append('tipo_registro', tipo);
    formData.append('id_distrito', selectSede.value);
    formData.append('descriptor', JSON.stringify(descriptorDetectado));
    formData.append('attendance_token', (typeof ATTENDANCE_TOKEN !== 'undefined') ? ATTENDANCE_TOKEN : '');

    try {
        const response = await fetch(obtenerRutaModel('registrar_asistencia.php'), {
            method: 'POST',
            body: formData,
            headers: attendanceHeaders()
        });

        const data = await leerJsonSeguro(response, 'registro de asistencia');

        if (data.status === 'success') {
            UIFeedback.success(data.message, { timer: 3000 });
        } else {
            UIFeedback.error('No se pudo registrar', data.message);
        }
    } catch (error) {
        console.error('Error al registrar asistencia:', normalizarError(error));
        UIFeedback.error('Error', 'Fallo en la comunicacion');
    } finally {
        registroEnProceso = false;
        if (btnActual && btnActual.dataset.originalText) {
            btnActual.innerHTML = btnActual.dataset.originalText;
        }
        actualizarEstadoBotones(idEmpleadoDetectado !== null);
    }
}

if (btnMarcar) btnMarcar.addEventListener('click', () => procesarAsistencia('entrada'));
if (btnSalida) btnSalida.addEventListener('click', () => procesarAsistencia('salida'));

window.addEventListener('unhandledrejection', (event) => {
    console.error('Promesa no controlada en camara.js:', normalizarError(event.reason, 'Promesa rechazada sin detalle'));
    event.preventDefault();
});

iniciarSistema().catch((err) => {
    console.error('Fallo global en camara.js:', normalizarError(err));
});
