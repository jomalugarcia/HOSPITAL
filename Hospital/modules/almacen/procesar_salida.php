<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: salidas.php");
    exit;
}

// Validar datos obligatorios
if (!isset($_POST['producto_id']) || !isset($_POST['cantidad'])) {
    header("Location: salidas.php?error=Faltan datos obligatorios");
    exit;
}

$producto_id = $_POST['producto_id'];
$cantidad = intval($_POST['cantidad']);
$motivo = $_POST['motivo'] ?? null;
$usuario_id = $_SESSION['user_id'];

if ($cantidad <= 0) {
    header("Location: salidas.php?error=La cantidad debe ser mayor a 0");
    exit;
}

try {
    // ============================================
    // VERIFICAR STOCK SUFICIENTE (desde stock_actual)
    // ============================================
    $stmt = $pdo->prepare("
        SELECT COALESCE(cantidad_total, 0) as stock_actual 
        FROM stock_actual 
        WHERE producto_id = ? AND departamento = 'almacen'
    ");
    $stmt->execute([$producto_id]);
    $stock_actual = $stmt->fetchColumn();
    
    if ($stock_actual < $cantidad) {
        header("Location: salidas.php?error=" . urlencode("Stock insuficiente. Disponible: $stock_actual"));
        exit;
    }
    
    // ============================================
    // INICIAR TRANSACCIÓN
    // ============================================
    $pdo->beginTransaction();
    
    // ============================================
    // REGISTRAR MOVIMIENTO (el trigger actualizará stock_actual)
    // ============================================
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_inventario (
            departamento,
            producto_id, 
            tipo_movimiento, 
            cantidad, 
            motivo, 
            usuario_id,
            fecha_movimiento,
            destino
        ) VALUES (
            'almacen',
            ?, 
            'salida', 
            ?, 
            ?, 
            ?, 
            NOW(), 
            'Salida de almacén'
        )
    ");
    
    $stmt->execute([
        $producto_id,
        $cantidad,
        $motivo,
        $usuario_id
    ]);
    
    // ============================================
    // CONFIRMAR TRANSACCIÓN
    // ============================================
    $pdo->commit();
    
    header("Location: productos.php?msg=salida");
    
} catch (PDOException $e) {
    // ============================================
    // ERROR: REVERTIR TRANSACCIÓN
    // ============================================
    $pdo->rollBack();
    error_log("Error en procesar_salida: " . $e->getMessage());
    header("Location: salidas.php?error=" . urlencode("Error al registrar: " . $e->getMessage()));
}
exit;
?>