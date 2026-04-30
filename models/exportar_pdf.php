<?php
require_once '../vendor/autoload.php';
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id_empleado = isset($_GET['id']) ? intval($_GET['id']) : 0;
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$preset = $_GET['preset'] ?? '';

if ($preset) {
    $fecha_fin = date('Y-m-d');
    switch ($preset) {
        case 'hoy': $fecha_inicio = date('Y-m-d'); break;
        case 'ayer': $fecha_inicio = date('Y-m-d', strtotime('-1 day')); $fecha_fin = $fecha_inicio; break;
        case 'semana': $fecha_inicio = date('Y-m-d', strtotime('-7 days')); break;
        case 'mes': $fecha_inicio = date('Y-m-d', strtotime('-30 days')); break;
    }
}

$where = [];
$params = [];
if ($id_empleado > 0) {
    $where[] = "a.id_empleado = ?";
    $params[] = $id_empleado;
}
if ($fecha_inicio) {
    $where[] = "DATE(a.fech_ingr) >= ?";
    $params[] = $fecha_inicio;
}
if ($fecha_fin) {
    $where[] = "DATE(a.fech_ingr) <= ?";
    $params[] = $fecha_fin;
}
$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT e.nomb_empl, e.apat_empl, e.dni_empl, c.nomb_carg, 
               a.fech_ingr, a.fech_sali, a.horas_tard, a.horas_trab, d.nomb_dist as sede_marcacion
        FROM asistencia a
        INNER JOIN empleado e ON a.id_empleado = e.pk_id_empleado
        INNER JOIN cargo c ON e.id_cargo = c.pk_id_cargo
        LEFT JOIN distrito d ON a.id_distrito = d.pk_id_distrito
        $where_sql
        ORDER BY a.fech_ingr DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$html = '
<style>
    body { font-family: sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #0d6efd; color: white; }
    .header { text-align: center; margin-bottom: 20px; }
    .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; }
</style>
<div class="header">
    <h2 style="margin-bottom: 5px;">REPORTE DE ASISTENCIA</h2>
    <p style="margin: 0; color: #666;">AMFURI PERU S.A.C.</p>
    ' . ($fecha_inicio ? "<p>Desde: $fecha_inicio Hasta: $fecha_fin</p>" : "") . '
</div>
<table>
    <thead>
        <tr>
            <th>DNI</th>
            <th>Empleado</th>
            <th>Sede del Día</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Tardanza</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>';

foreach ($asistencias as $as) {
    $html .= '<tr>
        <td>' . $as['dni_empl'] . '</td>
        <td>' . htmlspecialchars($as['nomb_empl'] . " " . $as['apat_empl']) . '</td>
        <td>' . htmlspecialchars($as['sede_marcacion'] ?? 'S/D') . '</td>
        <td>' . date('d/m/Y H:i', strtotime($as['fech_ingr'])) . '</td>
        <td>' . ($as['fech_sali'] ? date('d/m/Y H:i', strtotime($as['fech_sali'])) : '---') . '</td>
        <td style="color: ' . ($as['horas_tard'] != '00:00:00' ? 'red' : 'black') . '">' . $as['horas_tard'] . '</td>
        <td>' . ($as['horas_trab'] ?: '00:00:00') . '</td>
    </tr>';
}

$html .= '</tbody></table>
<div class="footer">Generado el ' . date('d/m/Y H:i:s') . '</div>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Reporte_Asistencia_" . date('Ymd') . ".pdf", ["Attachment" => true]);
?>
