<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: productos.php");
    exit;
}

$id = $_GET['id'];

// Obtener producto
$stmt = $pdo->prepare("
    SELECT p.*,
           COALESCE(sa.cantidad_total, 0) as stock_actual
    FROM productos p
    LEFT JOIN stock_actual sa ON p.id = sa.producto_id AND sa.departamento = 'almacen'
    WHERE p.id = ? AND p.departamento = 'almacen'
");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
    header("Location: productos.php?error=Producto no encontrado");
    exit;
}

// Obtener categorías de almacén
$categorias = $pdo->query("SELECT * FROM categorias_almacen ORDER BY nombre")->fetchAll();

// Obtener códigos del producto
$stmt = $pdo->prepare("
    SELECT * FROM productos_codigos 
    WHERE producto_id = ? 
    ORDER BY es_principal DESC, id
");
$stmt->execute([$id]);
$codigos = $stmt->fetchAll();
?>

<div class="fade-in modulo-almacen">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1>✏️ Editar Producto</h1>
            <p style="color: var(--gray-600);">Modificando: <strong><?= htmlspecialchars($producto['nombre']) ?></strong></p>
        </div>
        <a href="productos.php" class="almacen-btn almacen-btn-outline">← Volver</a>
    </div>

    <div class="almacen-card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="actualizar_producto.php">
            <input type="hidden" name="id" value="<?= $producto['id'] ?>">
            
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label class="required">Código principal</label>
                    <input type="text" name="codigo" class="almacen-form-control" required 
                           value="<?= htmlspecialchars($producto['codigo']) ?>">
                </div>
                
                <div class="almacen-form-group">
                    <label class="required">Nombre</label>
                    <input type="text" name="nombre" class="almacen-form-control" required 
                           value="<?= htmlspecialchars($producto['nombre']) ?>">
                </div>
            </div>
            
            <div class="almacen-form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="almacen-form-control" rows="2"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
            </div>
            
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label class="required">Categoría</label>
                    <select name="categoria_almacen_id" class="almacen-form-control" required>
                        <option value="">Seleccionar categoría...</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $producto['categoria_almacen_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="almacen-form-group">
                    <label class="required">Unidad de medida</label>
                    <select name="unidad_medida" class="almacen-form-control" required>
                        <option value="pieza" <?= $producto['unidad_medida'] == 'pieza' ? 'selected' : '' ?>>Pieza</option>
                        <option value="caja" <?= $producto['unidad_medida'] == 'caja' ? 'selected' : '' ?>>Caja</option>
                        <option value="paquete" <?= $producto['unidad_medida'] == 'paquete' ? 'selected' : '' ?>>Paquete</option>
                        <option value="litro" <?= $producto['unidad_medida'] == 'litro' ? 'selected' : '' ?>>Litro</option>
                        <option value="kilogramo" <?= $producto['unidad_medida'] == 'kilogramo' ? 'selected' : '' ?>>Kilogramo</option>
                        <option value="metro" <?= $producto['unidad_medida'] == 'metro' ? 'selected' : '' ?>>Metro</option>
                    </select>
                </div>
            </div>
            
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label>Stock actual</label>
                    <input type="number" class="almacen-form-control" value="<?= $producto['stock_actual'] ?>" readonly disabled>
                    <small class="almacen-text-muted">El stock se modifica con entradas/salidas</small>
                </div>
                
                <div class="almacen-form-group">
                    <label>Precio unitario</label>
                    <input type="number" step="0.01" name="precio_unitario" class="almacen-form-control" 
                           value="<?= $producto['precio_unitario'] ?>">
                </div>
            </div>
            
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label>Stock mínimo</label>
                    <input type="number" name="stock_minimo" class="almacen-form-control" value="<?= $producto['stock_minimo'] ?>">
                </div>
                
                <div class="almacen-form-group">
                    <label>Stock máximo</label>
                    <input type="number" name="stock_maximo" class="almacen-form-control" value="<?= $producto['stock_maximo'] ?>">
                </div>
            </div>
            
            <div class="almacen-form-group">
                <label>Ubicación</label>
                <input type="text" name="ubicacion" class="almacen-form-control" value="<?= htmlspecialchars($producto['ubicacion'] ?? '') ?>">
            </div>
            
            <div class="almacen-flex almacen-gap-sm">
                <button type="submit" class="almacen-btn almacen-btn-primary">Actualizar Producto</button>
                <a href="productos.php" class="almacen-btn almacen-btn-outline">Cancelar</a>
            </div>
        </form>
    </div>

    <!-- SECCIÓN DE CÓDIGOS SECUNDARIOS -->
    <div class="almacen-card" style="margin-top: var(--spacing-xl);">
        <h3>🏷️ Códigos de barras secundarios</h3>
        
        <!-- Formulario para agregar nuevo código -->
        <form method="POST" action="guardar_codigo.php" style="margin-bottom: var(--spacing-lg);">
            <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
            <div class="almacen-form-row">
                <div class="almacen-form-group" style="flex: 1;">
                    <label>Nuevo código secundario</label>
                    <input type="text" name="codigo" class="almacen-form-control" placeholder="Código de barras" required>
                </div>
                <div class="almacen-form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="almacen-btn almacen-btn-success">➕ Agregar código</button>
                </div>
            </div>
        </form>
        
        <!-- Lista de códigos existentes -->
        <h4>Códigos registrados</h4>
        <div class="almacen-tabla-container">
            <table class="almacen-tabla">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($codigos as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['codigo_barras']) ?></td>
                        <td>
                            <?php if ($c['es_principal']): ?>
                                <span class="almacen-badge primary">Principal</span>
                            <?php else: ?>
                                <span class="almacen-badge secondary">Secundario</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$c['es_principal']): ?>
                                <a href="eliminar_codigo.php?id=<?= $c['id'] ?>" 
                                   class="almacen-btn almacen-btn-sm almacen-btn-danger"
                                   onclick="return confirm('¿Eliminar este código?')">
                                    🗑️ Eliminar
                                </a>
                            <?php else: ?>
                                <span class="almacen-text-muted">No se puede eliminar el código principal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>