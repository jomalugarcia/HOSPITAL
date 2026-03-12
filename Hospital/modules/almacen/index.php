<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener estadísticas
$stats = [];

// Total de productos activos
$stmt = $pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1 AND departamento = 'almacen'");
$stats['total_productos'] = $stmt->fetchColumn();

// Productos con stock bajo
$stmt = $pdo->query("
    SELECT COUNT(*) FROM stock_actual sa
    JOIN productos p ON sa.producto_id = p.id
    WHERE sa.departamento = 'almacen' AND sa.cantidad_total <= p.stock_minimo
");
$stats['stock_bajo'] = $stmt->fetchColumn();

// Total proveedores
$stmt = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE activo = 1");
$stats['total_proveedores'] = $stmt->fetchColumn();

// Valor total inventario
$stmt = $pdo->query("
    SELECT COALESCE(SUM(sa.cantidad_total * p.precio_unitario), 0)
    FROM stock_actual sa
    JOIN productos p ON sa.producto_id = p.id
    WHERE sa.departamento = 'almacen'
");
$stats['valor_total_inventario'] = $stmt->fetchColumn();

// Productos con stock bajo (para mostrar)
$productos_bajo_stock = $pdo->prepare("
    SELECT p.id, p.codigo, p.nombre, p.precio_unitario, sa.cantidad_total as stock_actual,
           p.stock_minimo, p.stock_minimo - sa.cantidad_total as faltante,
           (p.stock_minimo - sa.cantidad_total) * p.precio_unitario as costo_reposicion,
           c.nombre as categoria_nombre
    FROM stock_actual sa
    JOIN productos p ON sa.producto_id = p.id
    LEFT JOIN categorias_almacen c ON p.categoria_almacen_id = c.id
    WHERE sa.departamento = 'almacen' AND sa.cantidad_total <= p.stock_minimo
    ORDER BY faltante DESC LIMIT 5
");
$productos_bajo_stock->execute();
$productos_bajo_stock = $productos_bajo_stock->fetchAll();

// Movimientos de hoy
$movimientos_hoy = $pdo->prepare("
    SELECT m.*, p.nombre as producto_nombre, p.codigo, u.nombre as usuario_nombre,
           p.precio_unitario, m.cantidad * p.precio_unitario as valor_movimiento
    FROM movimientos_inventario m
    JOIN productos p ON m.producto_id = p.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE DATE(m.fecha_movimiento) = CURDATE() AND p.departamento = 'almacen'
    ORDER BY m.fecha_movimiento DESC LIMIT 10
");
$movimientos_hoy->execute();
$movimientos_hoy = $movimientos_hoy->fetchAll();
?>

<div class="fade-in">
    <!-- Header -->
    <div class="almacen-header">
        <div>
            <h1>🏢 Módulo de Almacén</h1>
            <p class="almacen-subtitle">Gestión de inventarios, productos y movimientos</p>
        </div>
        <div>
            <span class="almacen-badge primary"><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="almacen-stats">
        <div class="almacen-stat-card primary">
            <div class="almacen-stat-icon">📦</div>
            <div class="almacen-stat-value"><?= $stats['total_productos'] ?></div>
            <div class="almacen-stat-label">Productos Activos</div>
        </div>
        
        <div class="almacen-stat-card <?= $stats['stock_bajo'] > 0 ? 'warning' : 'success' ?>">
            <div class="almacen-stat-icon">⚠️</div>
            <div class="almacen-stat-value">
                <a href="reporte_stock_bajo.php" class="almacen-stat-link"><?= $stats['stock_bajo'] ?></a>
            </div>
            <div class="almacen-stat-label">Productos Stock Bajo</div>
        </div>
        
        <div class="almacen-stat-card info">
            <div class="almacen-stat-icon">🤝</div>
            <div class="almacen-stat-value"><?= $stats['total_proveedores'] ?></div>
            <div class="almacen-stat-label">Proveedores</div>
        </div>
    </div>

    <!-- Valor total del inventario -->
    <div class="almacen-valor-card">
        <div class="almacen-stat-icon">💰</div>
        <div class="almacen-stat-value">$<?= number_format($stats['valor_total_inventario'], 2) ?></div>
        <div class="almacen-stat-label">VALOR TOTAL DEL INVENTARIO</div>
    </div>

    <!-- Accesos rápidos -->
    <h2 class="almacen-section-title">Accesos Rápidos</h2>
    <div class="almacen-grid">
        <a href="productos.php" class="almacen-grid-item">
            <div class="almacen-grid-icon">📦</div>
            <div class="almacen-grid-text">Productos</div>
        </a>
        <a href="categorias.php" class="almacen-grid-item">
            <div class="almacen-grid-icon">📂</div>
            <div class="almacen-grid-text">Categorías</div>
        </a>
        <a href="proveedores.php" class="almacen-grid-item">
            <div class="almacen-grid-icon">🤝</div>
            <div class="almacen-grid-text">Proveedores</div>
        </a>
        <a href="entrada.php" class="almacen-grid-item">
            <div class="almacen-grid-icon">⬆️</div>
            <div class="almacen-grid-text">Entradas</div>
        </a>
        <a href="salidas.php" class="almacen-grid-item">
            <div class="almacen-grid-icon">⬇️</div>
            <div class="almacen-grid-text">Salidas</div>
        </a>
        <a href="movimientos.php" class="almacen-grid-item">
            <div class="almacen-grid-icon">📊</div>
            <div class="almacen-grid-text">Movimientos</div>
        </a>
        <a href="reportes.php" class="almacen-grid-item">
            <div class="almacen-grid-icon">📈</div>
            <div class="almacen-grid-text">Reportes</div>
        </a>
    </div>

    <!-- Sección de stock bajo -->
    <div class="almacen-card">
        <div class="almacen-card-header">
            <h3>⚠️ Productos con Stock Bajo</h3>
            <a href="reporte_stock_bajo.php" class="almacen-btn almacen-btn-sm almacen-btn-outline">Ver todos</a>
        </div>
        
        <?php if (empty($productos_bajo_stock)): ?>
            <div class="almacen-mensaje">
                <p>✅ No hay productos con stock bajo</p>
            </div>
        <?php else: ?>
            <?php foreach ($productos_bajo_stock as $p): ?>
                <div class="almacen-producto-card bajo">
                    <div class="almacen-producto-info">
                        <strong><?= htmlspecialchars($p['codigo']) ?></strong> - <?= htmlspecialchars($p['nombre']) ?>
                        <br><small>Stock: <?= $p['stock_actual'] ?> / Mínimo: <?= $p['stock_minimo'] ?></small>
                        <br><span class="almacen-badge bajo">Faltan <?= $p['faltante'] ?> uds</span>
                    </div>
                    <div class="almacen-producto-acciones">
                        <div><strong>$<?= number_format($p['precio_unitario'], 2) ?></strong> c/u</div>
                        <div class="almacen-text-danger">Total: $<?= number_format($p['costo_reposicion'], 2) ?></div>
                        <a href="entrada.php?producto=<?= $p['id'] ?>&faltante=<?= $p['faltante'] ?>" 
                           class="almacen-btn almacen-btn-sm almacen-btn-success">Registrar entrada</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Movimientos del día -->
    <div class="almacen-card">
        <div class="almacen-card-header">
            <h3>📋 Movimientos de Hoy</h3>
            <a href="movimientos.php?fecha_desde=<?= date('Y-m-d') ?>&fecha_hasta=<?= date('Y-m-d') ?>" 
               class="almacen-btn almacen-btn-sm almacen-btn-outline">Ver todos</a>
        </div>
        
        <?php if (empty($movimientos_hoy)): ?>
            <div class="almacen-mensaje">No hay movimientos registrados hoy</div>
        <?php else: ?>
            <?php 
            $total_valor_dia = 0;
            foreach ($movimientos_hoy as $m) $total_valor_dia += $m['valor_movimiento'];
            ?>
            <div class="almacen-tabla-container">
                <table class="almacen-tabla">
                    <thead>
                        <tr>
                            <th>Hora</th><th>Tipo</th><th>Producto</th><th>Cant</th><th>P.Unit</th><th>Valor</th><th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos_hoy as $m): ?>
                        <tr>
                            <td><?= date('H:i', strtotime($m['fecha_movimiento'])) ?></td>
                            <td><span class="almacen-badge <?= $m['tipo_movimiento'] == 'entrada' ? 'success' : 'warning' ?>">
                                <?= $m['tipo_movimiento'] == 'entrada' ? '⬆️ Entrada' : '⬇️ Salida' ?>
                            </span></td>
                            <td><?= htmlspecialchars($m['codigo']) ?> - <?= htmlspecialchars($m['producto_nombre']) ?></td>
                            <td><?= $m['cantidad'] ?></td>
                            <td>$<?= number_format($m['precio_unitario'], 2) ?></td>
                            <td class="almacen-text-<?= $m['tipo_movimiento'] == 'entrada' ? 'success' : 'warning' ?>">
                                $<?= number_format($m['valor_movimiento'], 2) ?>
                            </td>
                            <td><?= htmlspecialchars($m['usuario_nombre']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="almacen-tabla-foot">
                            <td colspan="5" class="almacen-text-right">Total del día:</td>
                            <td colspan="2"><strong>$<?= number_format($total_valor_dia, 2) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>