<?php
function renderPagination(int $pagina, int $totalPaginas, callable $urlBuilder, int $radio = 2): string {
    if ($totalPaginas <= 1) return '';

    $inicio = max(1, $pagina - $radio);
    $fin = min($totalPaginas, $pagina + $radio);
    $html = '<nav class="mt-4"><ul class="pagination pagination-sm justify-content-center flex-wrap gap-1">';
    $html .= '<li class="page-item ' . ($pagina <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . htmlspecialchars($urlBuilder(max(1, $pagina - 1))) . '">Anterior</a></li>';

    if ($inicio > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($urlBuilder(1)) . '">1</a></li>';
        if ($inicio > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }

    for ($i = $inicio; $i <= $fin; $i++) {
        $html .= '<li class="page-item ' . ($i === $pagina ? 'active' : '') . '"><a class="page-link" href="' . htmlspecialchars($urlBuilder($i)) . '">' . $i . '</a></li>';
    }

    if ($fin < $totalPaginas) {
        if ($fin < $totalPaginas - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($urlBuilder($totalPaginas)) . '">' . $totalPaginas . '</a></li>';
    }

    $html .= '<li class="page-item ' . ($pagina >= $totalPaginas ? 'disabled' : '') . '"><a class="page-link" href="' . htmlspecialchars($urlBuilder(min($totalPaginas, $pagina + 1))) . '">Siguiente</a></li>';
    $html .= '</ul></nav>';
    return $html;
}

function renderOptions(array $rows, string $valueKey, string $labelKey, int $selected): string {
    $html = '';
    foreach ($rows as $row) {
        $value = (int) $row[$valueKey];
        $html .= '<option value="' . $value . '" ' . ($selected === $value ? 'selected' : '') . '>' . htmlspecialchars($row[$labelKey]) . '</option>';
    }
    return $html;
}
?>
