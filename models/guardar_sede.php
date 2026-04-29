<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['nomb_dist'])) {
    $nombre = trim($_POST['nomb_dist']);
    $obsv   = trim($_POST['obsv_dist'] ?? '');
    
    $stmt = $pdo->prepare("INSERT INTO distrito (nomb_dist, obsv_dist) VALUES (?, ?)");
    if ($stmt->execute([$nombre, $obsv])) {
        header("Location: ../views/sedes_lista.php?msj=registrado");
    } else {
        header("Location: ../views/sedes_lista.php?status=error");
    }
}