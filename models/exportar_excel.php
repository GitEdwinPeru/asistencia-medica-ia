<?php
require_once '../config/db.php';

// Configuración de cabeceras para descargar el archivo Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_Asistencia_Hospital_Huacho_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Consulta completa con JOINs para un reporte detallado
$sql = "SELECT e.nomb_empl, e.apat_empl, e.dni_empl, 
               c.nomb_carg, g.nomb_grup, d.nomb_dist,
               a.fech_ingr, a.fech_sali, a.horas_tard, a.horas_trab
        FROM asistencia a
        INNER JOIN empleado e ON a.id_empleado = e.pk_id_empleado
        INNER JOIN cargo c ON e.id_cargo = c.pk_id_cargo
        INNER JOIN grupo g ON e.id_grupo = g.pk_id_grupo
        INNER JOIN distrito d ON e.id_distrito = d.pk_id_distrito
        ORDER BY a.fech_ingr DESC";

$stmt = $pdo->query($sql);
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr style="background-color: #0d6efd; color: white; font-weight: bold;">
            <th colspan="10" style="font-size: 18px; padding: 10px;">REPORTE DE ASISTENCIA - SEDE HUACHO</th>
        </tr>
        <tr style="background-color: #f8f9fa; font-weight: bold;">
            <th>DNI</th>
            <th>NOMBRES</th>
            <th>APELLIDOS</th>
            <th>CARGO / PROFESIÓN</th>
            <th>GRUPO TÉCNICO</th>
            <th>DISTRITO (SEDE)</th>
            <th>FECHA / HORA ENTRADA</th>
            <th>FECHA / HORA SALIDA</th>
            <th>MINUTOS TARDANZA</th>
            <th>TOTAL HORAS TRABAJADAS</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($asistencias as $asist): ?>
            <tr>
                <td><?php echo $asist['dni_empl']; ?></td>
                <td><?php echo htmlspecialchars($asist['nomb_empl']); ?></td>
                <td><?php echo htmlspecialchars($asist['apat_empl']); ?></td>
                <td><?php echo htmlspecialchars($asist['nomb_carg']); ?></td>
                <td><?php echo htmlspecialchars($asist['nomb_grup']); ?></td>
                <td><?php echo htmlspecialchars($asist['nomb_dist']); ?></td>
                <td><?php echo $asist['fech_ingr']; ?></td>
                <td><?php echo $asist['fech_sali'] ? $asist['fech_sali'] : 'Sin salida'; ?></td>
                <td style="<?php echo ($asist['horas_tard'] != '00:00:00') ? 'color: red;' : ''; ?>">
                    <?php echo $asist['horas_tard']; ?>
                </td>
                <td><?php echo $asist['horas_trab'] ? $asist['horas_trab'] : '00:00:00'; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>