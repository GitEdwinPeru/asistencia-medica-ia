<?php
require_once '../vendor/autoload.php';
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';
require_once '../config/asistencia_report.php';
require_once '../config/export_helpers.php';

$filtros = asistenciaFiltros($_GET);
$asistencias = asistenciaConsulta($pdo, $filtros);
$resumen = asistenciaResumen($pdo, $filtros);
$chips = asistenciaFiltrosActivos($pdo, $filtros);
$filename = asistenciaNombreArchivo($pdo, $filtros, 'pdf');

$html = '
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th, td { border: 1px solid #d1d5db; padding: 7px; text-align: left; }
    th { background-color: #0d6efd; color: white; }
    .header { text-align: center; margin-bottom: 16px; }
    .meta { background: #f3f4f6; padding: 10px; border-radius: 6px; margin-bottom: 8px; }
    .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #6b7280; }
</style>
<div class="header">
    <h2 style="margin-bottom: 4px;">REPORTE DE ASISTENCIA</h2>
    <p style="margin: 0; color: #666;">AMFURI PERU S.A.C.</p>
</div>
<div class="meta"><strong>Filtros aplicados:</strong> ' . htmlspecialchars($chips ? implode(' | ', $chips) : 'Sin filtros') . '</div>
<div class="meta"><strong>Resumen:</strong> ' . $resumen['total'] . ' registros | ' . $resumen['tardanzas'] . ' tardanzas | ' . $resumen['sin_salida'] . ' sin salida | ' . htmlspecialchars($resumen['horas_total']) . ' horas acumuladas</div>
<table>
    <thead>
        <tr>
            <th>DNI</th>
            <th>Empleado</th>
            <th>Sede</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Tardanza</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>';

$html .= asistenciaExportRows($asistencias) . '</tbody></table>
<div class="footer">Generado el ' . date('d/m/Y H:i:s') . '</div>';

exportPdf($html, $filename);
?>
