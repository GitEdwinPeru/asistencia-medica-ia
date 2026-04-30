<?php
/**
 * Cabeceras de Seguridad para protección contra ataques comunes
 */

// Evita que la página sea enmarcada (Previene Clickjacking)
header("X-Frame-Options: DENY");

// Bloquea el sniffing de tipos MIME
header("X-Content-Type-Options: nosniff");

// Activa el filtro XSS del navegador
header("X-XSS-Protection: 1; mode=block");

// Referrer Policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// Content Security Policy (CSP) - Modo permisivo para diagnóstico
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com data: blob: mediastream:; img-src 'self' data: blob:; connect-src 'self' *; media-src 'self' blob: mediastream:;");

// Strict Transport Security (HSTS) - Solo si se usa HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}
?>
