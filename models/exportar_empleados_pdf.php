<?php
require_once '../vendor/autoload.php';
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';
require_once '../config/empleados_report.php';
require_once '../config/export_helpers.php';

$filtros = empleadosFiltros($_GET);
$catalogos = empleadosCatalogos($pdo);
$chips = empleadosChips($catalogos, $filtros);
$empleados = empleadosConsulta($pdo, $filtros);
$filename = empleadosNombreArchivo($filtros, 'pdf');

$html = '<style>
body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#1f2937}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border:1px solid #d1d5db;padding:6px;text-align:left}
th{background:#0d6efd;color:white}
.meta{background:#f3f4f6;padding:8px;margin:6px 0}
.header{text-align:center}
</style>
<div class="header"><h2>DIRECTORIO DE COLABORADORES</h2><p>AMFURI PERU S.A.C.</p></div>
<div class="meta"><strong>Filtros:</strong> ' . htmlspecialchars($chips ? implode(' | ', $chips) : 'Sin filtros') . '</div>
<div class="meta"><strong>Total:</strong> ' . count($empleados) . ' colaboradores</div>
<table><thead><tr>
<th>DNI</th><th>Empleado</th><th>Cargo</th><th>Grupo</th><th>Sede</th><th>Telefono</th><th>Email</th><th>Rostro</th>
</tr></thead><tbody>';

$html .= empleadosExportRows($empleados) . '</tbody></table><p style="font-size:9px;color:#6b7280;text-align:center;margin-top:16px">Generado el ' . date('d/m/Y H:i:s') . '</p>';

exportPdf($html, $filename);
?>
