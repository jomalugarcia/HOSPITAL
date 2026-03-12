<?php
require_once '../../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

header('Content-Type: application/json');

$codigo = $_GET['codigo'] ?? '';

if (strlen($codigo) < 1) {
    echo json_encode(['existe' => false]);
    exit;
}

try {
    // Buscar en productos_codigos
    $stmt = $pdo->prepare("
        SELECT pc.*, p.nombre as producto_nombre, p.codigo as producto_codigo
        FROM productos_codigos pc
        JOIN productos p ON pc.producto_id = p.id
        WHERE pc.codigo_barras = ? AND p.departamento = 'almacen'
        LIMIT 1
    ");
    $stmt->execute([$codigo]);
    $resultado = $stmt->fetch();
    
    if ($resultado) {
        echo json_encode([
            'existe' => true,
            'producto_nombre' => $resultado['producto_nombre'],
            'producto_codigo' => $resultado['producto_codigo']
        ]);
    } else {
        echo json_encode(['existe' => false]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>