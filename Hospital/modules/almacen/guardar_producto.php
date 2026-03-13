<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: productos.php");
    exit;
}

try {
    // ===== VALIDACIÓN GLOBAL: Verificar si el código ya existe en CUALQUIER producto =====
    $stmt = $pdo->prepare("
        SELECT pc.*, p.nombre as producto_nombre, p.id as producto_id
        FROM productos_codigos pc
        JOIN productos p ON pc.producto_id = p.id
        WHERE pc.codigo_barras = ? AND p.departamento = 'almacen'
    ");
    $stmt->execute([$_POST['codigo']]);
    $existente = $stmt->fetch();
    
    if ($existente) {
        // Guardar datos para recargar formulario
        $datos = http_build_query([
            'error' => 'El código ya está registrado en el producto: ' . $existente['producto_nombre'],
            'codigo' => $_POST['codigo'],
            'nombre' => $_POST['nombre'],
            'descripcion' => $_POST['descripcion'] ?? '',
            'categoria_almacen_id' => $_POST['categoria_almacen_id'],
            'unidad_medida' => $_POST['unidad_medida'],
            'stock_minimo' => $_POST['stock_minimo'] ?? 5,
            'stock_maximo' => $_POST['stock_maximo'] ?? 100,
            'precio_unitario' => $_POST['precio_unitario'] ?? 0,
            'ubicacion' => $_POST['ubicacion'] ?? '',
            'codigo_secundario' => $_POST['codigo_secundario'] ?? '',
            'mostrar_formulario' => 1
        ]);
        header("Location: productos.php?" . $datos);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Insertar producto
    $stmt = $pdo->prepare("
        INSERT INTO productos (
            departamento,
            codigo, 
            nombre, 
            descripcion, 
            categoria_almacen_id, 
            unidad_medida,
            stock_minimo, 
            stock_maximo, 
            precio_unitario, 
            ubicacion, 
            activo
        ) VALUES (
            'almacen',
            ?, ?, ?, ?, ?, ?, ?, ?, ?, 1
        )
    ");
    
    $stmt->execute([
        $_POST['codigo'],
        $_POST['nombre'],
        $_POST['descripcion'] ?? null,
        $_POST['categoria_almacen_id'],
        $_POST['unidad_medida'],
        $_POST['stock_minimo'] ?? 5,
        $_POST['stock_maximo'] ?? 100,
        $_POST['precio_unitario'] ?? 0,
        $_POST['ubicacion'] ?? null
    ]);
    
    $producto_id = $pdo->lastInsertId();
    
    // Registrar código principal
    $stmt = $pdo->prepare("
        INSERT INTO productos_codigos (producto_id, codigo_barras, es_principal)
        VALUES (?, ?, 1)
    ");
    $stmt->execute([$producto_id, $_POST['codigo']]);
    
    // Registrar código secundario si existe
    if (!empty($_POST['codigo_secundario'])) {
        // Verificar que el código secundario no exista
        $stmt = $pdo->prepare("
            SELECT pc.*, p.nombre as producto_nombre 
            FROM productos_codigos pc
            JOIN productos p ON pc.producto_id = p.id
            WHERE pc.codigo_barras = ?
        ");
        $stmt->execute([$_POST['codigo_secundario']]);
        $secundario_existente = $stmt->fetch();
        
        if ($secundario_existente) {
            $pdo->rollBack();
            $datos = http_build_query([
                'error' => 'El código secundario ya está registrado en el producto: ' . $secundario_existente['producto_nombre'],
                'codigo' => $_POST['codigo'],
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? '',
                'categoria_almacen_id' => $_POST['categoria_almacen_id'],
                'unidad_medida' => $_POST['unidad_medida'],
                'stock_minimo' => $_POST['stock_minimo'] ?? 5,
                'stock_maximo' => $_POST['stock_maximo'] ?? 100,
                'precio_unitario' => $_POST['precio_unitario'] ?? 0,
                'ubicacion' => $_POST['ubicacion'] ?? '',
                'codigo_secundario' => $_POST['codigo_secundario'] ?? '',
                'mostrar_formulario' => 1
            ]);
            header("Location: productos.php?" . $datos);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO productos_codigos (producto_id, codigo_barras, es_principal)
            VALUES (?, ?, 0)
        ");
        $stmt->execute([$producto_id, $_POST['codigo_secundario']]);
    }
    
    $pdo->commit();
    
    header("Location: productos.php?msg=guardado");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->errorInfo[1] == 1062) {
        header("Location: productos.php?error=El código ya existe");
    } else {
        header("Location: productos.php?error=" . urlencode($e->getMessage()));
    }
}
exit;
?>