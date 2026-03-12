<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: entrada.php");
    exit;
}

// ============================================
// RECIBIR DATOS DEL FORMULARIO
// ============================================
$proveedor_id = $_POST['proveedor_id'] ?? null;
$referencia = $_POST['referencia'] ?? '';
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$observaciones = $_POST['observaciones'] ?? '';
$usuario_id = $_SESSION['user_id'];

$productos = $_POST['producto_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];
$precios = $_POST['precio_unitario'] ?? [];

// ============================================
// VALIDACIONES BÁSICAS
// ============================================
if (empty($productos) || count($productos) == 0) {
    header("Location: entrada.php?error=Debe agregar al menos un producto");
    exit;
}

if (empty($proveedor_id)) {
    header("Location: entrada.php?error=Debe seleccionar un proveedor");
    exit;
}

try {
    // ============================================
    // INICIAR TRANSACCIÓN
    // ============================================
    $pdo->beginTransaction();
    
    $total_compra = 0;
    $items_procesados = 0;
    
    // ============================================
    // PROCESAR CADA PRODUCTO
    // ============================================
    foreach ($productos as $i => $producto_id) {
        if (empty($producto_id)) continue;
        
        $cantidad = intval($cantidades[$i] ?? 0);
        $precio = floatval($precios[$i] ?? 0);
        
        if ($cantidad <= 0) continue;
        
        $subtotal = $cantidad * $precio;
        $total_compra += $subtotal;
        $items_procesados++;
        
        // ============================================
        // REGISTRAR MOVIMIENTO EN INVENTARIO
        // ============================================
        $motivo = "Compra: " . ($referencia ?: "Sin factura");
        
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario (
                departamento,
                producto_id,
                tipo_movimiento,
                cantidad,
                motivo,
                referencia,
                usuario_id,
                fecha_movimiento,
                destino,
                observaciones
            ) VALUES (
                'almacen',
                ?,
                'entrada',
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");
        
        $fecha_movimiento = $fecha . ' ' . date('H:i:s');
        $destino = "Proveedor: " . $proveedor_id;
        
        $stmt->execute([
            $producto_id,
            $cantidad,
            $motivo,
            $referencia,
            $usuario_id,
            $fecha_movimiento,
            $destino,
            $observaciones
        ]);
        
        // ============================================
        // OPCIONAL: ACTUALIZAR PRECIO DEL PRODUCTO
        // (si el precio de compra es diferente al actual)
        // ============================================
        // Primero obtenemos el precio actual
        $stmt_precio = $pdo->prepare("SELECT precio_unitario FROM productos WHERE id = ?");
        $stmt_precio->execute([$producto_id]);
        $precio_actual = $stmt_precio->fetchColumn();
        
        // Si el precio de compra es diferente y no es cero, podríamos actualizar
        // Esta lógica puede ajustarse según necesidades del negocio
        if ($precio > 0 && $precio != $precio_actual) {
            // Opcional: actualizar precio
            // $stmt_update = $pdo->prepare("UPDATE productos SET precio_unitario = ? WHERE id = ?");
            // $stmt_update->execute([$precio, $producto_id]);
            
            // Por ahora solo guardamos en el motivo
            error_log("Precio diferente para producto $producto_id: actual $precio_actual, nuevo $precio");
        }
    }
    
    // ============================================
    // VERIFICAR QUE SE PROCESÓ AL MENOS UN PRODUCTO
    // ============================================
    if ($items_procesados == 0) {
        throw new Exception("No se procesó ningún producto válido");
    }
    
    // ============================================
    // CONFIRMAR TRANSACCIÓN
    // ============================================
    $pdo->commit();
    
    // ============================================
    // REDIRIGIR CON MENSAJE DE ÉXITO
    // ============================================
    header("Location: productos.php?msg=entrada&total=" . number_format($total_compra, 2));
    
} catch (Exception $e) {
    // ============================================
    // ERROR: REVERTIR TRANSACCIÓN
    // ============================================
    $pdo->rollBack();
    error_log("Error en procesar_entrada: " . $e->getMessage());
    header("Location: entrada.php?error=" . urlencode("Error al registrar: " . $e->getMessage()));
}
exit;
?>