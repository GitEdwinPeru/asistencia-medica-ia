<?php
$base = getenv('APP_TEST_BASE_URL') ?: 'http://localhost/asistencia_facial';
$cookie = tempnam(sys_get_temp_dir(), 'af_cookie_');
require_once __DIR__ . '/../config/db.php';

$testUser = 'codex_export_test';
$testPass = 'CodexTest123!';
$pdo->prepare("DELETE FROM login WHERE usuario = ?")->execute([$testUser]);
$pdo->prepare("INSERT INTO login (usuario, clave, perfil) VALUES (?, ?, ?)")
    ->execute([$testUser, password_hash($testPass, PASSWORD_BCRYPT), 'Administrador']);

register_shutdown_function(function () use ($pdo, $testUser, $cookie) {
    $pdo->prepare("DELETE FROM login WHERE usuario = ?")->execute([$testUser]);
    @unlink($cookie);
});

function httpRequest(string $url, string $method = 'GET', array $headers = [], ?string $body = null): array {
    global $cookie;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $content = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return ['status' => $status, 'body' => $content ?: '', 'error' => $error];
}

function ok(string $label, bool $condition, string $detail = ''): void {
    echo ($condition ? '[OK] ' : '[FAIL] ') . $label . ($detail ? " - $detail" : '') . PHP_EOL;
    if (!$condition) exit(1);
}

function extractCsrf(string $html): string {
    preg_match('/name="csrf_token" value="([^"]+)"/', $html, $matches);
    return $matches[1] ?? '';
}

function assertExport(string $label, string $url, string $needle = ''): void {
    $response = httpRequest($url);
    $validBody = strlen($response['body']) > 200;
    if ($needle !== '') {
        $validBody = $validBody && str_contains($response['body'], $needle);
    }
    ok($label, $response['status'] === 200 && $validBody, 'HTTP ' . $response['status']);
}

echo "=== PRUEBAS HTTP DE ENDPOINTS ===" . PHP_EOL;

$index = httpRequest("$base/index.php");
ok('Carga index', $index['status'] === 200);
preg_match("/ATTENDANCE_TOKEN = '([a-f0-9]{64})'/", $index['body'], $matches);
$token = $matches[1] ?? '';
ok('Token de asistencia presente', strlen($token) === 64);
$csrf = extractCsrf($index['body']);
ok('Token CSRF de login presente', $csrf !== '');

$sedesSinToken = httpRequest("$base/models/obtener_sedes.php");
ok('Sedes sin token bloqueado', $sedesSinToken['status'] === 403);

$headers = ["X-Attendance-Token: $token"];
$sedes = httpRequest("$base/models/obtener_sedes.php", 'GET', $headers);
ok('Sedes con token', $sedes['status'] === 200 && json_decode($sedes['body'], true) !== null);

$empleados = httpRequest("$base/models/obtener_empleados_fotos.php", 'GET', $headers);
ok('Empleados con rostros con token', $empleados['status'] === 200 && is_array(json_decode($empleados['body'], true)));

$registroIncompleto = httpRequest("$base/models/registrar_asistencia.php", 'POST', $headers, 'attendance_token=' . urlencode($token));
ok('Registro incompleto valida datos', in_array($registroIncompleto['status'], [422, 429], true));

$dni = httpRequest("$base/models/verificar_dni.php?dni=00000000");
ok('Endpoint verificar DNI protegido o responde', in_array($dni['status'], [200, 302, 403], true));

$login = httpRequest(
    "$base/models/login_process.php",
    'POST',
    ['Content-Type: application/x-www-form-urlencoded'],
    http_build_query(['csrf_token' => $csrf, 'usuario' => $testUser, 'clave' => $testPass])
);
ok('Login de prueba para exportaciones', $login['status'] === 302);

assertExport('Exportacion asistencia Excel', "$base/models/exportar_excel.php?dni=00000000", 'REPORTE DE ASISTENCIA');
assertExport('Exportacion empleados Excel', "$base/models/exportar_empleados_excel.php?dni=00000000", 'DIRECTORIO DE COLABORADORES');
$pdfAsistencia = httpRequest("$base/models/exportar_pdf.php?dni=00000000");
ok('Exportacion asistencia PDF', $pdfAsistencia['status'] === 200 && str_starts_with($pdfAsistencia['body'], '%PDF'), 'HTTP ' . $pdfAsistencia['status']);
$pdfEmpleados = httpRequest("$base/models/exportar_empleados_pdf.php?dni=00000000");
ok('Exportacion empleados PDF', $pdfEmpleados['status'] === 200 && str_starts_with($pdfEmpleados['body'], '%PDF'), 'HTTP ' . $pdfEmpleados['status']);

echo "Resultado: HTTP ENDPOINTS OK" . PHP_EOL;
?>
