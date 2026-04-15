<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id_distrito']);
    $nombre = trim($_POST['nomb_dist']);

    if ($id > 0 && !empty($nombre)) {
        $stmt = $pdo->prepare("UPDATE distrito SET nomb_dist = ? WHERE pk_id_distrito = ?");
        $stmt->execute([$nombre, $id]);
    }
    header("Location: ../views/sedes_lista.php?msj=editado");
    exit();
}