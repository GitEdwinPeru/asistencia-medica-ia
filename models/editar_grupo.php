<?php
require_once '../config/auth.php';
restringirSoloAdmin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id_grupo']);
    $nombre = trim($_POST['nomb_grup']);

    if ($id > 0 && !empty($nombre)) {
        $stmt = $pdo->prepare("UPDATE grupo SET nomb_grup = ? WHERE pk_id_grupo = ?");
        $stmt->execute([$nombre, $id]);
    }
    header("Location: ../views/grupos_lista.php?msj=editado");
    exit();
}