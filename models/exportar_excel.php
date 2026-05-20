<?php
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';
require_once '../config/asistencia_report.php';
require_once '../config/export_helpers.php';

$filtros = asistenciaFiltros($_GET);
$asistencias = asistenciaConsulta($pdo, $filtros);
$resumen = asistenciaResumen($pdo, $filtros);
$chips = asistenciaFiltrosActivos($pdo, $filtros);
$filename = asistenciaNombreArchivo($pdo, $filtros, 'xls');

exportExcelHeaders($filename);
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr style="background-color: #0d6efd; color: white; font-weight: bold;">
            <th colspan="10" style="font-size: 18px; padding: 10px;">REPORTE DE ASISTENCIA - AMFURI PERU S.A.C.</th>
        </tr>
        <tr>
            <th colspan="10">Generado: <?= date('d/m/Y H:i:s') ?></th>
        </tr>
        <tr>
            <th colspan="10">Filtros aplicados: <?= htmlspecialchars($chips ? implode(' | ', $chips) : 'Sin filtros') ?></th>
        </tr>
        <tr>
            <th colspan="10">Resumen: <?= $resumen['total'] ?> registros | <?= $resumen['tardanzas'] ?> tardanzas | <?= $resumen['sin_salida'] ?> sin salida | <?= htmlspecialchars($resumen['horas_total']) ?> horas acumuladas</th>
        </tr>
        <tr style="background-color: #f8f9fa; font-weight: bold;">
            <th>DNI</th>
            <th>NOMBRES</th>
            <th>APELLIDOS</th>
            <th>CARGO / PROFESION</th>
            <th>GRUPO TECNICO</th>
            <th>SEDE DE MARCACION</th>
            <th>ENTRADA</th>
            <th>SALIDA</th>
            <th>TARDANZA</th>
            <th>TOTAL HORAS</th>
        </tr>
    </thead>
    <tbody>
        <?= asistenciaExportRows($asistencias, true) ?>
    </tbody>
</table>
