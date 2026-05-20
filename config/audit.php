<?php
function auditEvent(PDO $pdo, string $accion, string $entidad, ?string $entidadId = null, ?string $detalle = null): void {
    try {
        $stmt = $pdo->prepare("INSERT INTO auditoria_eventos (actor_id, accion, entidad, entidad_id, detalle, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['admin_id'] ?? null,
            $accion,
            $entidad,
            $entidadId,
            $detalle,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Throwable $e) {
        if (class_exists('Logger')) {
            Logger::error("No se pudo registrar auditoria: " . $e->getMessage());
        }
    }
}
?>
