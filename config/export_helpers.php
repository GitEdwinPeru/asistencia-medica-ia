<?php
function exportH($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function exportDateTime(?string $value): string {
    if (!$value) return 'Sin salida';
    $timestamp = strtotime($value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : $value;
}

function exportExcelHeaders(string $filename): void {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Expires: 0");
}

function exportPdf(string $html, string $filename, string $paper = 'A4', string $orientation = 'landscape'): void {
    $options = new Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);

    $dompdf = new Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper($paper, $orientation);
    $dompdf->render();
    $dompdf->stream($filename, ["Attachment" => true]);
}

function asistenciaExportRows(array $asistencias, bool $excel = false): string {
    if (!$asistencias) {
        return '<tr><td colspan="' . ($excel ? '10' : '7') . '">No hay registros para exportar con los filtros aplicados.</td></tr>';
    }

    $html = '';
    foreach ($asistencias as $as) {
        $empleado = trim(($as['nomb_empl'] ?? '') . ' ' . ($as['apat_empl'] ?? '') . ($excel ? ' ' . ($as['amat_empl'] ?? '') : ''));
        if ($excel) {
            $html .= '<tr>
                <td>' . exportH($as['dni_empl'] ?? '') . '</td>
                <td>' . exportH($as['nomb_empl'] ?? '') . '</td>
                <td>' . exportH(trim(($as['apat_empl'] ?? '') . ' ' . ($as['amat_empl'] ?? ''))) . '</td>
                <td>' . exportH($as['nomb_carg'] ?? '') . '</td>
                <td>' . exportH($as['nomb_grup'] ?? '') . '</td>
                <td>' . exportH($as['sede_marcacion'] ?? 'No especificada') . '</td>
                <td>' . exportH($as['fech_ingr'] ?? '') . '</td>
                <td>' . exportH(!empty($as['fech_sali']) ? $as['fech_sali'] : 'Sin salida') . '</td>
                <td style="' . (($as['horas_tard'] ?? '00:00:00') !== '00:00:00' ? 'color: red;' : '') . '">' . exportH($as['horas_tard'] ?? '00:00:00') . '</td>
                <td>' . exportH($as['horas_trab'] ?: '00:00:00') . '</td>
            </tr>';
            continue;
        }

        $html .= '<tr>
            <td>' . exportH($as['dni_empl'] ?? '') . '</td>
            <td>' . exportH($empleado) . '</td>
            <td>' . exportH($as['sede_marcacion'] ?? 'S/D') . '</td>
            <td>' . exportH(exportDateTime($as['fech_ingr'] ?? null)) . '</td>
            <td>' . exportH(exportDateTime($as['fech_sali'] ?? null)) . '</td>
            <td style="color: ' . (($as['horas_tard'] ?? '00:00:00') !== '00:00:00' ? 'red' : 'black') . '">' . exportH($as['horas_tard'] ?? '00:00:00') . '</td>
            <td>' . exportH($as['horas_trab'] ?: '00:00:00') . '</td>
        </tr>';
    }

    return $html;
}

function empleadosExportRows(array $empleados, bool $excel = false): string {
    if (!$empleados) {
        return '<tr><td colspan="' . ($excel ? '10' : '8') . '">No hay colaboradores para los filtros aplicados.</td></tr>';
    }

    $html = '';
    foreach ($empleados as $emp) {
        $nombreCompleto = trim(($emp['apat_empl'] ?? '') . ' ' . ($emp['amat_empl'] ?? '') . ', ' . ($emp['nomb_empl'] ?? ''));
        if ($excel) {
            $html .= '<tr>
                <td>' . exportH($emp['dni_empl'] ?? '') . '</td>
                <td>' . exportH($emp['nomb_empl'] ?? '') . '</td>
                <td>' . exportH(trim(($emp['apat_empl'] ?? '') . ' ' . ($emp['amat_empl'] ?? ''))) . '</td>
                <td>' . exportH($emp['nomb_carg'] ?? 'Sin cargo') . '</td>
                <td>' . exportH($emp['nomb_grup'] ?? 'Sin grupo') . '</td>
                <td>' . exportH($emp['nomb_dist'] ?? 'Sin sede') . '</td>
                <td>' . exportH($emp['celu_empl'] ?: '---') . '</td>
                <td>' . exportH($emp['emai_empl'] ?: '---') . '</td>
                <td>' . exportH($emp['dire_empl'] ?: '---') . '</td>
                <td>' . (!empty($emp['rostro_embedding']) ? 'Registrado' : 'Pendiente') . '</td>
            </tr>';
            continue;
        }

        $html .= '<tr>
            <td>' . exportH($emp['dni_empl'] ?? '') . '</td>
            <td>' . exportH($nombreCompleto) . '</td>
            <td>' . exportH($emp['nomb_carg'] ?? 'Sin cargo') . '</td>
            <td>' . exportH($emp['nomb_grup'] ?? 'Sin grupo') . '</td>
            <td>' . exportH($emp['nomb_dist'] ?? 'Sin sede') . '</td>
            <td>' . exportH($emp['celu_empl'] ?: '---') . '</td>
            <td>' . exportH($emp['emai_empl'] ?: '---') . '</td>
            <td>' . (!empty($emp['rostro_embedding']) ? 'Registrado' : 'Pendiente') . '</td>
        </tr>';
    }

    return $html;
}
?>
