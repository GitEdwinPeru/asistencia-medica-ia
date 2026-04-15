<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['nomb_dist'])) {
    $nombre = trim($_POST['nomb_dist']);
    
    $stmt = $pdo->prepare("INSERT INTO distrito (nomb_dist) VALUES (?)");
    if ($stmt->execute([$nombre])) {
        header("Location: ../views/sedes_lista.php?status=success");
    } else {
        header("Location: ../views/sedes_lista.php?status=error");
    }
}