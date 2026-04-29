<?php
require_once '../config/auth.php';
verificarSesion();
require_once '../config/db.php';

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

$sql = "SELECT e.nomb_empl, e.apat_empl, e.dni_empl, 
               c.nomb_carg, g.nomb_grup, d.nomb_dist,
               a.fech_ingr, a.fech_sali, a.horas_tard, a.horas_trab
        FROM asistencia a
        INNER JOIN empleado e ON a.id_empleado = e.pk_id_empleado
        INNER JOIN cargo c ON e.id_cargo = c.pk_id_cargo
        INNER JOIN grupo g ON e.id_grupo = g.pk_id_grupo
        INNER JOIN distrito d ON e.id_distrito = d.pk_id_distrito
        $where_sql
        ORDER BY a.fech_ingr DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_Asistencia_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr style="background-color: #0d6efd; color: white; font-weight: bold;">
            <th colspan="10" style="font-size: 18px; padding: 10px;">REPORTE DE ASISTENCIA - AMFURI PERU S.A.C.</th>
        </tr>
        <?php if ($fecha_inicio || $fecha_fin): ?>
        <tr>
            <th colspan="10">Rango: <?= $fecha_inicio ?: 'Inicio' ?> hasta <?= $fecha_fin ?: 'Fin' ?></th>
        </tr>
        <?php endif; ?>
        <tr style="background-color: #f8f9fa; font-weight: bold;">
            <th>DNI</th>
            <th>NOMBRES</th>
            <th>APELLIDOS</th>
            <th>CARGO / PROFESIÓN</th>
            <th>GRUPO TÉCNICO</th>
            <th>SEDE / DISTRITO</th>
            <th>ENTRADA</th>
            <th>SALIDA</th>
            <th>TARDANZA</th>
            <th>TOTAL HORAS</th>
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
