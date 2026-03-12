<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener producto si viene por URL
$producto_id = $_GET['producto'] ?? null;
$producto = null;

if ($producto_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, COALESCE(sa.cantidad_total, 0) as stock_actual
        FROM productos p
        LEFT JOIN stock_actual sa ON p.id = sa.producto_id AND sa.departamento = 'almacen'
        WHERE p.id = ? AND p.activo = 1 AND p.departamento = 'almacen'
    ");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();
}

// Obtener productos con stock para el select
// Obtener productos con stock para el select - VERSIÓN CORREGIDA
$productos = $pdo->query("
    SELECT 
        p.id, 
        p.codigo, 
        p.nombre, 
        p.unidad_medida,
        p.precio_unitario,
        COALESCE(sa.cantidad_total, 0) as stock_actual
    FROM productos p
    LEFT JOIN stock_actual sa ON p.id = sa.producto_id AND sa.departamento = 'almacen'
    WHERE p.activo = 1 
        AND p.departamento = 'almacen'
        AND COALESCE(sa.cantidad_total, 0) > 0
    ORDER BY p.nombre
")->fetchAll();

// Estadísticas del día
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total, SUM(cantidad) as total_unidades
    FROM movimientos_inventario 
    WHERE tipo_movimiento = 'salida' 
    AND DATE(fecha_movimiento) = CURDATE()
    AND departamento = 'almacen'
");
$stmt->execute();
$salidas_hoy = $stmt->fetch();
?>

<div class="fade-in modulo-almacen">
    <!-- Header -->
    <div class="almacen-header">
        <div>
            <h1>⬇️ Registrar Salida de Productos</h1>
            <p class="almacen-subtitle">Disminuye el stock por consumo, uso o desperfecto</p>
        </div>
        <div class="almacen-flex almacen-gap-sm">
            <span class="almacen-badge info">Hoy: <?= $salidas_hoy['total'] ?? 0 ?> salidas</span>
            <span class="almacen-badge warning"><?= $salidas_hoy['total_unidades'] ?? 0 ?> unidades</span>
            <a href="productos.php" class="almacen-btn almacen-btn-sm almacen-btn-outline">← Volver</a>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Salida registrada correctamente</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <?php if ($producto): ?>
        <div class="almacen-card" style="background: var(--info-light); border-left: 4px solid var(--info); margin-bottom: var(--spacing-md);">
            <div class="almacen-flex" style="justify-content: space-between; align-items: center;">
                <div>
                    <strong>Producto seleccionado:</strong> <?= htmlspecialchars($producto['nombre']) ?>
                </div>
                <div>
                    Stock disponible: <strong><?= $producto['stock_actual'] ?> <?= $producto['unidad_medida'] ?></strong>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulario principal -->
    <div class="almacen-card">
        <h3>📝 Registrar nueva salida</h3>
        
        <form method="POST" action="procesar_salida.php" id="formSalida">
            <!-- Producto -->
            <div class="almacen-form-group">
                <label class="required">Producto</label>
                <?php if ($producto): ?>
                    <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                    <div class="almacen-form-control" style="background: var(--gray-100); height: auto; padding: 10px;">
                        <strong><?= htmlspecialchars($producto['codigo']) ?></strong> - 
                        <?= htmlspecialchars($producto['nombre']) ?>
                        (Stock disponible: <strong id="stockDisponible"><?= $producto['stock_actual'] ?></strong>)
                    </div>
                <?php else: ?>
                    <select name="producto_id" id="productoSelect" class="almacen-form-control" required>
                        <option value="">-- Seleccionar producto con stock --</option>
                        <?php foreach ($productos as $p): ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-stock="<?= $p['stock_actual'] ?>"
                                    data-unidad="<?= $p['unidad_medida'] ?>"
                                    data-precio="<?= $p['precio_unitario'] ?>">
                                <?= htmlspecialchars($p['codigo']) ?> - <?= htmlspecialchars($p['nombre']) ?> 
                                (Stock: <?= $p['stock_actual'] ?> <?= $p['unidad_medida'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="almacen-form-row">
                <!-- Cantidad -->
                <div class="almacen-form-group" style="flex: 1;">
                    <label class="required">Cantidad a retirar</label>
                    <input type="number" name="cantidad" id="cantidad" class="almacen-form-control" 
                           required min="1" max="<?= $producto['stock_actual'] ?? '' ?>" step="1" value="1">
                    <small id="stockInfo" class="almacen-text-muted">
                        <?php if ($producto): ?>
                            Stock disponible: <?= $producto['stock_actual'] ?> <?= $producto['unidad_medida'] ?>
                        <?php endif; ?>
                    </small>
                </div>

                <!-- Valor estimado de la salida -->
                <div class="almacen-form-group" style="flex: 1;">
                    <label>Valor estimado</label>
                    <div class="almacen-form-control" style="background: var(--gray-100); height: auto; padding: 10px;" id="valorEstimado">
                        $0.00
                    </div>
                </div>
            </div>

            <!-- Motivo -->
            <div class="almacen-form-group">
                <label>Motivo / Observaciones</label>
                <input type="text" name="motivo" class="almacen-form-control" 
                       placeholder="Ej: Uso en consultorio, desperfecto, donación...">
                <small class="almacen-text-muted">Opcional, pero recomendado para mejor control</small>
            </div>

            <!-- Botones -->
            <div class="almacen-flex almacen-gap-sm">
                <button type="submit" class="almacen-btn almacen-btn-warning" style="flex: 1;">
                    <span>💾</span> Registrar Salida
                </button>
                <a href="productos.php" class="almacen-btn almacen-btn-outline">Cancelar</a>
            </div>
        </form>
    </div>

    <!-- Salidas recientes -->
    <div class="almacen-card">
        <div class="almacen-card-header">
            <h3>📋 Últimas salidas registradas</h3>
        </div>
        
        <?php
        $recientes = $pdo->query("
            SELECT m.*, p.nombre as producto, p.codigo, u.nombre as usuario,
                   p.precio_unitario, m.cantidad * p.precio_unitario as valor_total
            FROM movimientos_inventario m
            JOIN productos p ON m.producto_id = p.id
            JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.tipo_movimiento = 'salida' AND m.departamento = 'almacen'
            ORDER BY m.fecha_movimiento DESC
            LIMIT 10
        ")->fetchAll();
        ?>
        
        <?php if (empty($recientes)): ?>
            <div class="almacen-mensaje">No hay salidas registradas aún</div>
        <?php else: ?>
            <div class="almacen-tabla-container">
                <table class="almacen-tabla">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Valor</th>
                            <th>Usuario</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recientes as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($r['fecha_movimiento'])) ?></td>
                            <td><?= htmlspecialchars($r['codigo']) ?> - <?= htmlspecialchars($r['producto']) ?></td>
                            <td><strong>-<?= $r['cantidad'] ?></strong></td>
                            <td>$<?= number_format($r['valor_total'], 2) ?></td>
                            <td><?= htmlspecialchars($r['usuario']) ?></td>
                            <td><?= htmlspecialchars($r['motivo'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ============================================
// VARIABLES GLOBALES
// ============================================
let productosSalida = []; // Para evitar duplicados si es necesario

// ============================================
// ACTUALIZAR INFORMACIÓN CUANDO SE SELECCIONA PRODUCTO
// ============================================
document.getElementById('productoSelect')?.addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const stock = selected.getAttribute('data-stock');
    const unidad = selected.getAttribute('data-unidad');
    const precio = selected.getAttribute('data-precio');
    
    if (stock) {
        document.getElementById('cantidad').max = stock;
        document.getElementById('stockInfo').textContent = 
            'Stock disponible: ' + stock + ' ' + (unidad || 'unidades');
        
        // Calcular valor estimado
        const cantidad = document.getElementById('cantidad').value || 1;
        const valor = cantidad * precio;
        document.getElementById('valorEstimado').innerHTML = '$' + valor.toFixed(2);
    }
});

// ============================================
// CALCULAR VALOR ESTIMADO AL CAMBIAR CANTIDAD
// ============================================
document.getElementById('cantidad')?.addEventListener('input', function() {
    const select = document.getElementById('productoSelect');
    if (!select || !select.value) {
        // Si hay producto preseleccionado
        <?php if ($producto): ?>
        const precio = <?= $producto['precio_unitario'] ?>;
        const cantidad = this.value || 0;
        const valor = cantidad * precio;
        document.getElementById('valorEstimado').innerHTML = '$' + valor.toFixed(2);
        <?php endif; ?>
        return;
    }
    
    const selected = select.options[select.selectedIndex];
    const precio = selected.getAttribute('data-precio') || 0;
    const cantidad = this.value || 0;
    const valor = cantidad * precio;
    
    document.getElementById('valorEstimado').innerHTML = '$' + valor.toFixed(2);
});

// ============================================
// VALIDAR CANTIDAD ANTES DE ENVIAR
// ============================================
document.getElementById('formSalida')?.addEventListener('submit', function(e) {
    const cantidad = document.getElementById('cantidad').value;
    const max = document.getElementById('cantidad').max;
    
    if (cantidad <= 0) {
        e.preventDefault();
        alert('La cantidad debe ser mayor a 0');
        return;
    }
    
    if (max && parseInt(cantidad) > parseInt(max)) {
        e.preventDefault();
        alert('No hay suficiente stock disponible');
        return;
    }
    
    // Deshabilitar botón para evitar doble envío
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '⏳ Registrando...';
});

// ============================================
// INICIALIZAR VALOR ESTIMADO SI HAY PRODUCTO PRESELECCIONADO
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($producto): ?>
    const cantidad = document.getElementById('cantidad');
    const precio = <?= $producto['precio_unitario'] ?>;
    const valor = cantidad.value * precio;
    document.getElementById('valorEstimado').innerHTML = '$' + valor.toFixed(2);
    <?php endif; ?>
});
</script>

<?php require_once '../../includes/footer.php'; ?>