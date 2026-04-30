<?php
require_once '../vendor/autoload.php';
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';
require_once '../config/security.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id_empleado = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_empleado === 0) exit("ID Inválido");

// Obtener todos los datos
$empleado = $pdo->prepare("SELECT e.*, c.nomb_carg, g.nomb_grup, d.nomb_dist FROM empleado e LEFT JOIN cargo c ON e.id_cargo = c.pk_id_cargo LEFT JOIN grupo g ON e.id_grupo = g.pk_id_grupo LEFT JOIN distrito d ON e.id_distrito = d.pk_id_distrito WHERE e.pk_id_empleado = ?");
$empleado->execute([$id_empleado]);
$emp = $empleado->fetch();

$estudios = $pdo->prepare("SELECT * FROM empleado_estudios WHERE id_empleado = ?");
$estudios->execute([$id_empleado]);
$estudios = $estudios->fetchAll();

$bancos = $pdo->prepare("SELECT * FROM empleado_bancos WHERE id_empleado = ?");
$bancos->execute([$id_empleado]);
$bancos = $bancos->fetchAll();

$familia = $pdo->prepare("SELECT * FROM empleado_familia WHERE id_empleado = ?");
$familia->execute([$id_empleado]);
$familia = $familia->fetchAll();

$emergencia = $pdo->prepare("SELECT * FROM empleado_emergencia WHERE id_empleado = ?");
$emergencia->execute([$id_empleado]);
$emergencia = $emergencia->fetchAll();

$experiencia = $pdo->prepare("SELECT * FROM empleado_experiencia WHERE id_empleado = ?");
$experiencia->execute([$id_empleado]);
$experiencia = $experiencia->fetchAll();

$html = '
<html>
<head>
<style>
    body { font-family: Arial, sans-serif; color: #333; line-height: 1.5; }
    .header { text-align: center; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 20px; }
    .section { margin-bottom: 20px; }
    .section-title { background: #f0f4f8; padding: 5px 10px; font-weight: bold; color: #0d6efd; text-transform: uppercase; font-size: 14px; margin-bottom: 10px; }
    .grid { display: table; width: 100%; }
    .row { display: table-row; }
    .col { display: table-cell; padding: 5px; }
    .label { font-weight: bold; color: #555; }
    table { width: 100%; border-collapse: collapse; margin-top: 5px; }
    th, td { border: 1px solid #eee; padding: 8px; text-align: left; font-size: 12px; }
    th { background: #fafafa; }
</style>
</head>
<body>
    <div class="header">
        <h1>HOJA DE VIDA</h1>
        <h2 style="margin: 0;">' . htmlspecialchars($emp['nomb_empl'] . ' ' . $emp['apat_empl']) . '</h2>
        <p style="margin: 5px 0;">' . htmlspecialchars($emp['nomb_carg']) . ' | DNI: ' . $emp['dni_empl'] . '</p>
    </div>

    <div class="section">
        <div class="section-title">Datos Personales</div>
        <table>
            <tr>
                <td><span class="label">F. Nacimiento:</span> ' . ($emp['fnac_empl'] ?: '---') . '</td>
                <td><span class="label">Estado Civil:</span> ' . ($emp['esta_civil'] ?: '---') . '</td>
            </tr>
            <tr>
                <td><span class="label">Nacionalidad:</span> ' . ($emp['nacionalidad'] ?: '---') . '</td>
                <td><span class="label">Celular:</span> ' . ($emp['celu_empl'] ?: '---') . '</td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">Email:</span> ' . ($emp['emai_empl'] ?: '---') . '</td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">Dirección:</span> ' . ($emp['dire_empl'] ?: '---') . '</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Formación Académica</div>
        <table>
            <thead><tr><th>Título</th><th>Institución</th><th>Fecha</th></tr></thead>
            <tbody>';
            foreach($estudios as $e) {
                $html .= '<tr><td>'.htmlspecialchars($e['titulo']).'</td><td>'.htmlspecialchars($e['institucion']).'</td><td>'.$e['fecha_graduacion'].'</td></tr>';
            }
$html .= '</tbody></table></div>

    <div class="section">
        <div class="section-title">Experiencia Laboral</div>';
        foreach($experiencia as $exp) {
            $html .= '<div style="margin-bottom: 10px; padding: 5px; border-left: 3px solid #eee;">
                        <strong>'.htmlspecialchars($exp['cargo']).'</strong> en '.htmlspecialchars($exp['empresa']).'<br>
                        <small>'.$exp['fecha_inicio'].' al '.($exp['fecha_fin'] ?: 'Presente').'</small><br>
                        <p style="font-size: 11px; margin-top: 5px;">'.htmlspecialchars($exp['descripcion']).'</p>
                      </div>';
        }
$html .= '</div>

    <div class="section">
        <div class="section-title">Carga Familiar</div>
        <table>
            <thead><tr><th>Nombre</th><th>Parentesco</th><th>F. Nacimiento</th></tr></thead>
            <tbody>';
            foreach($familia as $f) {
                $html .= '<tr><td>'.htmlspecialchars($f['nombre']).'</td><td>'.htmlspecialchars($f['parentesco']).'</td><td>'.$f['fecha_nacimiento'].'</td></tr>';
            }
$html .= '</tbody></table></div>

    <div class="section">
        <div class="section-title">Contactos de Emergencia</div>
        <table>
            <thead><tr><th>Nombre</th><th>Relación</th><th>Teléfono</th></tr></thead>
            <tbody>';
            foreach($emergencia as $em) {
                $html .= '<tr><td>'.htmlspecialchars($em['nombre']).'</td><td>'.htmlspecialchars($em['relacion']).'</td><td>'.$em['telefono'].'</td></tr>';
            }
$html .= '</tbody></table></div>

    <div class="section">
        <div class="section-title">Datos Bancarios</div>
        <table>
            <thead><tr><th>Banco</th><th>Tipo</th><th>N° Cuenta</th></tr></thead>
            <tbody>';
            foreach($bancos as $b) {
                $html .= '<tr><td>'.htmlspecialchars($b['banco']).'</td><td>'.htmlspecialchars($b['tipo_cuenta']).'</td><td>'.desencriptarDato($b['numero_cuenta']).'</td></tr>';
            }
$html .= '</tbody></table></div>

</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Hoja_Vida_".$emp['dni_empl'].".pdf", ["Attachment" => false]);
?>
