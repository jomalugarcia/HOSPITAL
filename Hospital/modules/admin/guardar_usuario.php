<?php

require_once '../../config/config.php';
require_once '../../includes/auth.php';
$modulo_requerido = 'admin';

require_once '../../config/db.php';

$nombre  = $_POST['nombre'];
$usuario = $_POST['usuario'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$modulos = $_POST['modulos'] ?? [];

$pdo->beginTransaction();

$stmt = $pdo->prepare("
    INSERT INTO usuarios (nombre, usuario, password, rol)
    VALUES (?, ?, ?, 'usuario')
");
$stmt->execute([$nombre, $usuario, $password]);

$usuario_id = $pdo->lastInsertId();

$stmtPerm = $pdo->prepare("
    INSERT INTO usuario_modulos (usuario_id, modulo_id)
    VALUES (?, ?)
");

foreach ($modulos as $modulo_id) {
    $stmtPerm->execute([$usuario_id, $modulo_id]);
}

$pdo->commit();

header("Location: usuarios.php");
exit;
