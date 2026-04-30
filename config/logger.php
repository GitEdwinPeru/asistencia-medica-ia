<?php
/**
 * Sistema de Logs Profesional para Asistencia Facial
 */
class Logger {
    private static string $logPath = __DIR__ . '/../logs/';

    public static function log(string $message, string $level = 'INFO'): void {
        if (!file_exists(self::$logPath)) {
            mkdir(self::$logPath, 0777, true);
        }

        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $admin_id = $_SESSION['admin_id'] ?? 'GUEST';
        
        $file = self::$logPath . date('Y-m-d') . '.log';
        $formattedMessage = "[$date] [$level] [IP: $ip] [AdminID: $admin_id]: $message" . PHP_EOL;

        file_put_contents($file, $formattedMessage, FILE_APPEND);
    }

    public static function error(string $message): void {
        self::log($message, 'ERROR');
    }

    public static function security(string $message): void {
        self::log($message, 'SECURITY');
    }
}
?>
