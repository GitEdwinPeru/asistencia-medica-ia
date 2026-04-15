<?php
require_once '../config/db.php';

// Definir el nombre del archivo con la fecha actual
$filename = "Reporte_Asistencia_" . date('Ymd') . ".xls";

// Cabeceras para forzar la descarga en Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

try {
    // Consulta completa uniendo tablas
    $sql = "SELECT a.fech_ingr, a.fech_sali, a.horas_tard, a.horas_trab, 
                   e.nomb_empl, e.apat_empl, c.nomb_carg 
            FROM asistencia a
            INNER JOIN empleado e ON a.id_empleado = e.pk_id_empleado
            INNER JOIN cargo c ON e.id_cargo = c.pk_id_cargo
            ORDER BY a.fech_ingr DESC";
            
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear la tabla HTML que Excel interpretará
    echo "<table border='1'>";
    echo "<tr style='background-color: #000; color: #fff;'>";
    echo "<th>Empleado</th>";
    echo "<th>Cargo</th>";
    echo "<th>Fecha/Hora Entrada</th>";
    echo "<th>Fecha/Hora Salida</th>";
    echo "<th>Tardanza</th>";
    echo "<th>Horas Trabajadas</th>";
    echo "</tr>";

    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . utf8_decode($row['nomb_empl'] . " " . $row['apat_empl']) . "</td>";
        echo "<td>" . utf8_decode($row['nomb_carg']) . "</td>";
        echo "<td>" . $row['fech_ingr'] . "</td>";
        echo "<td>" . ($row['fech_sali'] ?? 'No marco') . "</td>";
        echo "<td>" . $row['horas_tard'] . "</td>";
        echo "<td>" . ($row['horas_trab'] ?? '--') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "Error al exportar: " . $e->getMessage();
}