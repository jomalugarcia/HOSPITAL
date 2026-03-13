<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: productos.php");
    exit;
}

$producto_id = $_POST['producto_id'];
$codigo = $_POST['codigo'];

try {
    // ===== VALIDACIÓN MÁS ESTRICTA: Verificar si el código ya existe en CUALQUIER producto =====
    $stmt = $pdo->prepare("
        SELECT pc.*, p.nombre as producto_nombre 
        FROM productos_codigos pc
        JOIN productos p ON pc.producto_id = p.id
        WHERE pc.codigo_barras = ?
    ");
    $stmt->execute([$codigo]);
    $existente = $stmt->fetch();
    
    if ($existente) {
        header("Location: editar_producto.php?id=$producto_id&error=" . urlencode(
            "El código '$codigo' ya está registrado en el producto: " . $existente['producto_nombre']
        ));
        exit;
    }
    
    // Insertar código secundario
    $stmt = $pdo->prepare("
        INSERT INTO productos_codigos (producto_id, codigo_barras, es_principal)
        VALUES (?, ?, 0)
    ");
    $stmt->execute([$producto_id, $codigo]);
    
    header("Location: editar_producto.php?id=$producto_id&msg=codigo_agregado");
    
} catch (PDOException $e) {
    header("Location: editar_producto.php?id=$producto_id&error=" . urlencode($e->getMessage()));
}
exit;
?>