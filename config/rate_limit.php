<?php
function rateLimitKey(string $scope): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    return 'rate_limit_' . hash('sha256', $scope . '|' . $ip);
}

function requerirRateLimit(string $scope, int $maxIntentos, int $ventanaSegundos): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = rateLimitKey($scope);
    $now = time();
    $bucket = $_SESSION[$key] ?? ['start' => $now, 'count' => 0];

    if (($now - (int) $bucket['start']) > $ventanaSegundos) {
        $bucket = ['start' => $now, 'count' => 0];
    }

    $bucket['count']++;
    $_SESSION[$key] = $bucket;

    if ($bucket['count'] > $maxIntentos) {
        $espera = max(1, $ventanaSegundos - ($now - (int) $bucket['start']));
        jsonResponse([
            'status' => 'error',
            'message' => "Demasiados intentos. Espere $espera segundos antes de volver a marcar."
        ], 429);
    }
}
?>
