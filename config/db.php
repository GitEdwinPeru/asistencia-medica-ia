<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/response.php';

// Configuracion de la base de datos
$host    = envValue('DB_HOST', 'localhost');
$port    = envValue('DB_PORT', '3307');
$db      = envValue('DB_NAME', 'control_asistencia');
$user    = envValue('DB_USER', 'root');
$pass    = envValue('DB_PASS', '');
$charset = "utf8mb4";

// Configuracion del Data Source Name
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Asegura que las fechas y textos se manejen correctamente.
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     error_log($e->getMessage());

     http_response_code(500);

     $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
     $script = $_SERVER['SCRIPT_NAME'] ?? '';
     $contentType = '';
     $incluidoDesdeModel = false;

     foreach (headers_list() as $header) {
          if (stripos($header, 'Content-Type:') === 0) {
               $contentType = $header;
               break;
          }
     }

     foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $trace) {
          $archivo = str_replace('\\', '/', $trace['file'] ?? '');
          if (str_contains($archivo, '/models/')) {
               $incluidoDesdeModel = true;
               break;
          }
     }

     $esperaJson = stripos($contentType, 'application/json') !== false
          || stripos($accept, 'application/json') !== false
          || str_contains($script, '/models/')
          || $incluidoDesdeModel;

     if ($esperaJson) {
          jsonResponse([
               'status' => 'error',
               'message' => 'No se pudo conectar a la base de datos. Verifica las credenciales de entorno DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS.'
          ], 500);
     }

     die("Error critico: No se pudo conectar a la base de datos.");
}
?>
