<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requerirCSRF($_POST['csrf_token'] ?? '');
    $nombre = trim($_POST['nomb_carg'] ?? '');

    if (!empty($nombre)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cargo (nomb_carg) VALUES (?)");
            $stmt->execute([$nombre]);
            header("Location: ../views/cargos_lista.php?msj=guardado");
        } catch (PDOException $e) {
            header("Location: ../views/cargos_lista.php?msj=error");
        }
    } else {
        header("Location: ../views/cargos_lista.php?msj=vacio");
    }
    exit();
}
