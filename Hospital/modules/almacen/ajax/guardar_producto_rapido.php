<?php
require_once '../../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../../includes/auth.php';
require_once '../../../config/db.php';

header('Content-Type: application/json');

// Verificar método primero
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del POST
$codigo = $_POST['codigo'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$categoria_id = $_POST['categoria_id'] ?? '';
$precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
$unidad_medida = $_POST['unidad_medida'] ?? 'pieza';
$stock_minimo = intval($_POST['stock_minimo'] ?? 5);
$stock_maximo = intval($_POST['stock_maximo'] ?? 100);
$departamento = $_POST['departamento'] ?? 'almacen';

try {
    // Validar campos obligatorios
    if (empty($codigo) || empty($nombre) || empty($categoria_id)) {
        echo json_encode(['error' => 'Código, nombre y categoría son obligatorios']);
        exit;
    }
    
    // ===== VALIDACIÓN GLOBAL: Verificar si el código ya existe =====
    $stmt = $pdo->prepare("
        SELECT pc.*, p.nombre as producto_nombre, p.id as producto_id
        FROM productos_codigos pc
        JOIN productos p ON pc.producto_id = p.id
        WHERE pc.codigo_barras = ? AND p.departamento = 'almacen'
    ");
    $stmt->execute([$codigo]);
    $existente = $stmt->fetch();
    
    if ($existente) {
        echo json_encode([
            'error' => 'El código ya está registrado en el producto: ' . $existente['producto_nombre']
        ]);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Insertar producto
    $stmt = $pdo->prepare("
        INSERT INTO productos (
            departamento,
            codigo,
            nombre,
            categoria_almacen_id,
            unidad_medida,
            stock_minimo,
            stock_maximo,
            precio_unitario,
            activo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->execute([
        $departamento,
        $codigo,
        $nombre,
        $categoria_id,
        $unidad_medida,
        $stock_minimo,
        $stock_maximo,
        $precio_unitario
    ]);
    
    $producto_id = $pdo->lastInsertId();
    
    // Registrar código principal
    $stmt = $pdo->prepare("
        INSERT INTO productos_codigos (producto_id, codigo_barras, es_principal)
        VALUES (?, ?, 1)
    ");
    $stmt->execute([$producto_id, $codigo]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'id' => $producto_id,
        'codigo' => $codigo,
        'nombre' => $nombre,
        'precio_unitario' => $precio_unitario
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
}
?>