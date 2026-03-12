<?php
require_once '../../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

header('Content-Type: application/json');

$termino = $_GET['q'] ?? '';

if (strlen($termino) < 1) {
    echo json_encode([]);
    exit;
}

try {
    // Buscar en códigos principales y secundarios
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            p.id,
            p.codigo,
            p.nombre,
            p.precio_unitario,
            p.unidad_medida,
            COALESCE(sa.cantidad_total, 0) as stock_actual
        FROM productos p
        LEFT JOIN productos_codigos pc ON p.id = pc.producto_id
        LEFT JOIN stock_actual sa ON p.id = sa.producto_id AND sa.departamento = 'almacen'
        WHERE p.activo = 1 
        AND p.departamento = 'almacen'
        AND (
            p.codigo LIKE ? 
            OR p.nombre LIKE ? 
            OR pc.codigo_barras LIKE ?
        )
        ORDER BY 
            CASE 
                WHEN p.codigo = ? THEN 1
                WHEN p.codigo LIKE ? THEN 2
                ELSE 3
            END,
            p.nombre
        LIMIT 10
    ");
    
    $busqueda = "%$termino%";
    $stmt->execute([$busqueda, $busqueda, $busqueda, $termino, "$termino%"]);
    $resultados = $stmt->fetchAll();
    
    echo json_encode($resultados);
    
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>