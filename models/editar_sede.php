<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id_distrito']);
    $nombre = trim($_POST['nomb_dist']);
    $obsv = trim($_POST['obsv_dist'] ?? '');

    if ($id > 0 && !empty($nombre)) {
        $stmt = $pdo->prepare("UPDATE distrito SET nomb_dist = ?, obsv_dist = ? WHERE pk_id_distrito = ?");
        $stmt->execute([$nombre, $obsv, $id]);
    }
    header("Location: ../views/sedes_lista.php?msj=editado");
    exit();
}