<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

$producto_id = $_GET['id'] ?? 0;

// Obtener producto
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ? AND departamento = 'almacen'");
$stmt->execute([$producto_id]);
$producto = $stmt->fetch();

if (!$producto) {
    header("Location: productos.php");
    exit;
}

// Obtener códigos del producto
$stmt = $pdo->prepare("
    SELECT * FROM productos_codigos 
    WHERE producto_id = ? 
    ORDER BY es_principal DESC, id
");
$stmt->execute([$producto_id]);
$codigos = $stmt->fetchAll();
?>

<div class="fade-in modulo-almacen">
    <!-- Header -->
    <div class="almacen-header">
        <div>
            <h1>🏷️ Códigos de Barras</h1>
            <p class="almacen-subtitle">Producto: <strong><?= htmlspecialchars($producto['nombre']) ?></strong></p>
        </div>
        <a href="editar_producto.php?id=<?= $producto_id ?>" class="almacen-btn almacen-btn-outline">← Volver</a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            ✅ <?php 
            if ($_GET['msg'] == 'codigo_agregado') echo 'Código agregado correctamente';
            if ($_GET['msg'] == 'codigo_eliminado') echo 'Código eliminado correctamente';
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario para agregar código -->
    <div class="almacen-card">
        <h3 style="color: var(--primary);">➕ Agregar nuevo código</h3>
        <form method="POST" action="guardar_codigo.php">
            <input type="hidden" name="producto_id" value="<?= $producto_id ?>">
            <div class="almacen-form-row">
                <div class="almacen-form-group" style="flex: 1;">
                    <label>Código de barras</label>
                    <input type="text" name="codigo" class="almacen-form-control" required>
                </div>
                <div class="almacen-form-group" style="flex: 0.3;">
                    <label>&nbsp;</label>
                    <button type="submit" class="almacen-btn almacen-btn-success">Agregar</button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Lista de códigos -->
    <div class="almacen-card">
        <h3>📋 Códigos registrados</h3>
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
                        <td><strong><?= htmlspecialchars($c['codigo_barras']) ?></strong></td>
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
                                <span class="almacen-text-muted">No se puede eliminar</span>
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