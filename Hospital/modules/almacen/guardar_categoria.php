<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: categorias.php");
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO categorias_almacen (nombre, tipo, descripcion)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $_POST['nombre'],
        $_POST['tipo'],
        $_POST['descripcion'] ?? null
    ]);
    
    header("Location: categorias.php?msg=guardado");
    
} catch (PDOException $e) {
    header("Location: categorias.php?error=" . urlencode($e->getMessage()));
}
exit;
?>