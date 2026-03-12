<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Parámetros de filtro
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$tipo = $_GET['tipo'] ?? '';
$producto_id = $_GET['producto'] ?? '';

// Construir consulta
$sql = "
    SELECT 
        m.id,
        m.producto_id,
        m.tipo_movimiento,
        m.cantidad,
        m.motivo,
        m.fecha_movimiento,
        p.nombre as producto_nombre,
        p.codigo as producto_codigo,
        c.nombre as categoria_nombre,
        u.nombre as usuario_nombre,
        p.precio_unitario,
        (m.cantidad * p.precio_unitario) as valor_movimiento
    FROM movimientos_inventario m
    JOIN productos p ON m.producto_id = p.id
    LEFT JOIN categorias_almacen c ON p.categoria_almacen_id = c.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE DATE(m.fecha_movimiento) BETWEEN :desde AND :hasta
        AND p.departamento = 'almacen'
";

$params = [
    ':desde' => $fecha_desde,
    ':hasta' => $fecha_hasta
];

if (!empty($tipo)) {
    $sql .= " AND m.tipo_movimiento = :tipo";
    $params[':tipo'] = $tipo;
}

if (!empty($producto_id)) {
    $sql .= " AND m.producto_id = :producto";
    $params[':producto'] = $producto_id;
}

$sql .= " ORDER BY m.fecha_movimiento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

// Obtener productos para filtro
$productos = $pdo->query("
    SELECT id, codigo, nombre 
    FROM productos 
    WHERE activo = 1 AND departamento = 'almacen'
    ORDER BY nombre
")->fetchAll();

// Calcular totales
$total_entradas = 0;
$total_salidas = 0;
$total_valor = 0;
foreach ($movimientos as $m) {
    if ($m['tipo_movimiento'] == 'entrada') {
        $total_entradas += $m['cantidad'];
    } elseif ($m['tipo_movimiento'] == 'salida') {
        $total_salidas += $m['cantidad'];
    }
    $total_valor += $m['valor_movimiento'];
}
?>

<div class="fade-in modulo-almacen">
    <!-- Header -->
    <div class="almacen-header">
        <div>
            <h1>📋 Historial de Movimientos</h1>
            <p class="almacen-subtitle">Registro completo de entradas y salidas de almacén</p>
        </div>
        <a href="index.php" class="almacen-btn almacen-btn-outline">← Volver</a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <!-- Resumen de totales -->
    <div class="almacen-stats">
        <div class="almacen-stat-card success">
            <div class="almacen-stat-icon">⬆️</div>
            <div class="almacen-stat-value"><?= $total_entradas ?></div>
            <div class="almacen-stat-label">Total Entradas</div>
        </div>
        
        <div class="almacen-stat-card warning">
            <div class="almacen-stat-icon">⬇️</div>
            <div class="almacen-stat-value"><?= $total_salidas ?></div>
            <div class="almacen-stat-label">Total Salidas</div>
        </div>
        
        <div class="almacen-stat-card info">
            <div class="almacen-stat-icon">💰</div>
            <div class="almacen-stat-value">$<?= number_format($total_valor, 2) ?></div>
            <div class="almacen-stat-label">Valor Total</div>
        </div>
        
        <div class="almacen-stat-card primary">
            <div class="almacen-stat-icon">📊</div>
            <div class="almacen-stat-value"><?= count($movimientos) ?></div>
            <div class="almacen-stat-label">Total Movimientos</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="almacen-card">
        <h3>🔍 Filtrar movimientos</h3>
        <form method="GET" class="almacen-form-row">
            <div class="almacen-form-group">
                <label>Fecha desde</label>
                <input type="date" name="fecha_desde" class="almacen-form-control" value="<?= $fecha_desde ?>">
            </div>
            
            <div class="almacen-form-group">
                <label>Fecha hasta</label>
                <input type="date" name="fecha_hasta" class="almacen-form-control" value="<?= $fecha_hasta ?>">
            </div>
            
            <div class="almacen-form-group">
                <label>Tipo</label>
                <select name="tipo" class="almacen-form-control">
                    <option value="">Todos</option>
                    <option value="entrada" <?= $tipo == 'entrada' ? 'selected' : '' ?>>⬆️ Entradas</option>
                    <option value="salida" <?= $tipo == 'salida' ? 'selected' : '' ?>>⬇️ Salidas</option>
                </select>
            </div>
            
            <div class="almacen-form-group">
                <label>Producto</label>
                <select name="producto_id" class="almacen-form-control">
                    <option value="">Todos</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $producto_id == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['codigo']) ?> - <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="almacen-form-group" style="display: flex; align-items: flex-end;">
                <button type="submit" class="almacen-btn almacen-btn-primary">Filtrar</button>
                <a href="movimientos.php" class="almacen-btn almacen-btn-outline" style="margin-left: 0.5rem;">Limpiar</a>
            </div>
        </form>
    </div>

    <!-- Tabla de movimientos -->
    <div class="almacen-card">
        <div class="almacen-card-header">
            <h3>📋 Listado de movimientos</h3>
            <button onclick="exportarCSV()" class="almacen-btn almacen-btn-sm almacen-btn-outline">
                📥 Exportar a CSV
            </button>
        </div>
        
        <?php if (empty($movimientos)): ?>
            <div class="almacen-mensaje">
                <p>📭 No hay movimientos en el período seleccionado</p>
            </div>
        <?php else: ?>
            <div class="almacen-tabla-container">
                <table class="almacen-tabla" id="tablaMovimientos">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Cantidad</th>
                            <th>P.Unit</th>
                            <th>Valor</th>
                            <th>Usuario</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($m['fecha_movimiento'])) ?></td>
                            <td>
                                <?php if ($m['tipo_movimiento'] == 'entrada'): ?>
                                    <span class="almacen-badge success">⬆️ Entrada</span>
                                <?php else: ?>
                                    <span class="almacen-badge warning">⬇️ Salida</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($m['producto_codigo']) ?></strong>
                                <br>
                                <small><?= htmlspecialchars($m['producto_nombre']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($m['categoria_nombre'] ?? 'Sin categoría') ?></td>
                            <td style="font-weight: bold;"><?= $m['cantidad'] ?></td>
                            <td>$<?= number_format($m['precio_unitario'], 2) ?></td>
                            <td style="font-weight: bold; color: <?= $m['tipo_movimiento'] == 'entrada' ? 'var(--success)' : 'var(--warning)' ?>;">
                                $<?= number_format($m['valor_movimiento'], 2) ?>
                            </td>
                            <td><?= htmlspecialchars($m['usuario_nombre']) ?></td>
                            <td><?= htmlspecialchars($m['motivo'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Totales del período -->
            <div style="margin-top: var(--spacing-lg); padding: var(--spacing-md); background: var(--gray-100); border-radius: var(--radius-md); display: flex; justify-content: flex-end; gap: var(--spacing-xl);">
                <div>
                    <span style="color: var(--gray-600);">Total Entradas:</span>
                    <strong style="color: var(--success);"><?= $total_entradas ?> uds</strong>
                </div>
                <div>
                    <span style="color: var(--gray-600);">Total Salidas:</span>
                    <strong style="color: var(--warning);"><?= $total_salidas ?> uds</strong>
                </div>
                <div>
                    <span style="color: var(--gray-600);">Valor Total:</span>
                    <strong style="color: var(--info);">$<?= number_format($total_valor, 2) ?></strong>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function exportarCSV() {
    const filas = document.querySelectorAll('#tablaMovimientos tbody tr');
    if (filas.length === 0) return;
    
    let csv = [];
    
    // Encabezados
    csv.push('Fecha,Hora,Tipo,Producto,Categoría,Cantidad,Precio Unit.,Valor,Usuario,Motivo');
    
    // Datos
    filas.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length < 9) return;
        
        // Separar fecha y hora
        const fechaHora = celdas[0]?.innerText || '';
        const fecha = fechaHora.split(' ')[0] || '';
        const hora = fechaHora.split(' ')[1] || '';
        
        // Limpiar tipo (quitar iconos)
        let tipo = celdas[1]?.innerText.replace(/[⬆️⬇️]/g, '').trim() || '';
        
        // Producto (quitar saltos de línea)
        let producto = celdas[2]?.innerText.replace(/\n/g, ' ') || '';
        
        const categoria = celdas[3]?.innerText || '';
        const cantidad = celdas[4]?.innerText || '';
        const precio = celdas[5]?.innerText.replace('$', '') || '';
        const valor = celdas[6]?.innerText.replace('$', '') || '';
        const usuario = celdas[7]?.innerText || '';
        const motivo = celdas[8]?.innerText || '';
        
        csv.push(`"${fecha}","${hora}","${tipo}","${producto}","${categoria}","${cantidad}","${precio}","${valor}","${usuario}","${motivo}"`);
    });
    
    // Descargar
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'movimientos_<?= $fecha_desde ?>_<?= $fecha_hasta ?>.csv';
    a.click();
}
</script>

<?php require_once '../../includes/footer.php'; ?>