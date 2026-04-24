<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id_cargo'] ?? 0);
    $nombre = trim($_POST['nomb_carg'] ?? '');

    if ($id > 0 && !empty($nombre)) {
        try {
            $stmt = $pdo->prepare("UPDATE cargo SET nomb_carg = ? WHERE pk_id_cargo = ?");
            $stmt->execute([$nombre, $id]);
            header("Location: ../views/cargos_lista.php?msj=editado");
        } catch (PDOException $e) {
            header("Location: ../views/cargos_lista.php?msj=error");
        }
    }
    exit();
}