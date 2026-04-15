<?php
// Configuración de la base de datos
$host    = "localhost";
$db      = "control_asistencia";
$user    = "root";
$pass    = ""; 
$charset = "utf8mb4";

// Configuración del Data Source Name
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Añadimos esta opción para asegurar que las fechas se manejen correctamente
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // En producción, es mejor registrar el error en un log y no mostrarlo al usuario
     error_log($e->getMessage());
     die("Error crítico: No se pudo conectar a la base de datos.");
}
?>