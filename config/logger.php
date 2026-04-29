<?php
/**
 * Sistema de Logs Profesional para Asistencia Facial
 */
class Logger {
    private static $logPath = __DIR__ . '/../logs/';

    public static function log($message, $level = 'INFO') {
        if (!file_exists(self::$logPath)) {
            mkdir(self::$logPath, 0777, true);
        }

        $date = date('Y-m-d H:i:s');
        $file = self::$logPath . date('Y-m-d') . '.log';
        $formattedMessage = "[$date] [$level]: $message" . PHP_EOL;

        file_put_contents($file, $formattedMessage, FILE_APPEND);
    }

    public static function error($message) {
        self::log($message, 'ERROR');
    }

    public static function security($message) {
        self::log($message, 'SECURITY');
    }
}
?>
