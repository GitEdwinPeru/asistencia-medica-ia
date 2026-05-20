<?php
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';
require_once '../config/empleados_report.php';
require_once '../config/export_helpers.php';

$filtros = empleadosFiltros($_GET);
$catalogos = empleadosCatalogos($pdo);
$chips = empleadosChips($catalogos, $filtros);
$empleados = empleadosConsulta($pdo, $filtros);
$filename = empleadosNombreArchivo($filtros, 'xls');

exportExcelHeaders($filename);
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr style="background:#0d6efd;color:white;font-weight:bold;">
            <th colspan="10">DIRECTORIO DE COLABORADORES - AMFURI PERU S.A.C.</th>
        </tr>
        <tr><th colspan="10">Generado: <?= date('d/m/Y H:i:s') ?></th></tr>
        <tr><th colspan="10">Filtros: <?= htmlspecialchars($chips ? implode(' | ', $chips) : 'Sin filtros') ?></th></tr>
        <tr style="background:#f8f9fa;font-weight:bold;">
            <th>DNI</th>
            <th>Nombres</th>
            <th>Apellidos</th>
            <th>Cargo</th>
            <th>Grupo</th>
            <th>Sede</th>
            <th>Telefono</th>
            <th>Email</th>
            <th>Direccion</th>
            <th>Descriptor Facial</th>
        </tr>
    </thead>
    <tbody>
        <?= empleadosExportRows($empleados, true) ?>
    </tbody>
</table>
