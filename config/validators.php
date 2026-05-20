<?php
function soloDigitos(string $value, int $length): bool {
    return preg_match('/^\d{' . $length . '}$/', $value) === 1;
}

function validarFechaOpcional(?string $value): bool {
    if ($value === null || $value === '') return true;

    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

function validarEmailOpcional(string $value): bool {
    return $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function textoLimpio(string $value, int $maxLength = 255): string {
    $value = trim($value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return mb_substr($value, 0, $maxLength, 'UTF-8');
}
?>
