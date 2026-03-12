<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Procesar eliminación (desactivar)
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    // Verificar que no tenga productos asociados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE categoria_almacen_id = ?");
    $stmt->execute([$id]);
    $productos_asociados = $stmt->fetchColumn();
    
    if ($productos_asociados > 0) {
        header("Location: categorias.php?error=No se puede eliminar: tiene productos asociados");
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM categorias_almacen WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: categorias.php?msg=eliminado");
    exit;
}

// Obtener categorías
$categorias = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM productos WHERE categoria_almacen_id = c.id) as total_productos
    FROM categorias_almacen c
    ORDER BY c.tipo, c.nombre
")->fetchAll();

// Estadísticas
$total_categorias = count($categorias);
$tipos = $pdo->query("SELECT tipo, COUNT(*) as total FROM categorias_almacen GROUP BY tipo")->fetchAll();
?>

<div class="fade-in modulo-almacen">
    <!-- Header -->
    <div class="almacen-header">
        <div>
            <h1>📂 Gestión de Categorías</h1>
            <p class="almacen-subtitle">Clasifica los productos del almacén</p>
        </div>
        <div>
            <span class="almacen-badge primary">Total: <?= $total_categorias ?> categorías</span>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            ✅ Categoría <?= $_GET['msg'] == 'guardado' ? 'guardada' : 'eliminada' ?> correctamente
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario nueva categoría -->
    <div class="almacen-card">
        <h3 style="color: var(--primary); margin-bottom: var(--spacing-lg);">➕ Nueva Categoría</h3>
        
        <form method="POST" action="guardar_categoria.php" id="formCategoria">
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label class="required">Nombre de la categoría</label>
                    <input type="text" name="nombre" class="almacen-form-control" required 
                           placeholder="Ej: Material de curación, Papelería, etc."
                           maxlength="100">
                </div>
                
                <div class="almacen-form-group">
                    <label class="required">Tipo</label>
                    <select name="tipo" class="almacen-form-control" required>
                        <option value="">Seleccionar tipo...</option>
                        <option value="material_medico">🩺 Material médico</option>
                        <option value="limpieza">🧹 Limpieza</option>
                        <option value="oficina">📎 Oficina</option>
                        <option value="equipo">🔧 Equipo</option>
                        <option value="otro">📦 Otro</option>
                    </select>
                </div>
            </div>
            
            <div class="almacen-form-group">
                <label>Descripción (opcional)</label>
                <textarea name="descripcion" class="almacen-form-control" rows="2" 
                          placeholder="Descripción de la categoría..."></textarea>
            </div>
            
            <div class="almacen-flex almacen-gap-sm" style="margin-top: var(--spacing-lg);">
                <button type="submit" class="almacen-btn almacen-btn-success">
                    <span>💾</span> Guardar Categoría
                </button>
                <button type="reset" class="almacen-btn almacen-btn-outline">
                    <span>🗑️</span> Limpiar
                </button>
            </div>
        </form>
    </div>

    <!-- Resumen por tipo -->
    <?php if (!empty($tipos)): ?>
    <div class="almacen-card">
        <h3>📊 Resumen por tipo</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--spacing-md); margin-top: var(--spacing-md);">
            <?php foreach ($tipos as $t): ?>
                <div style="background: var(--gray-100); padding: var(--spacing-md); border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 1.5rem; margin-bottom: var(--spacing-xs);">
                        <?php
                        $icono = [
                            'material_medico' => '🩺',
                            'limpieza' => '🧹',
                            'oficina' => '📎',
                            'equipo' => '🔧',
                            'otro' => '📦'
                        ][$t['tipo']] ?? '📁';
                        echo $icono;
                        ?>
                    </div>
                    <div style="font-weight: bold;"><?= ucfirst(str_replace('_', ' ', $t['tipo'])) ?></div>
                    <div class="almacen-badge primary"><?= $t['total'] ?> categorías</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla de categorías -->
    <div class="almacen-card">
        <h3>📋 Listado de categorías</h3>
        
        <?php if (empty($categorias)): ?>
            <div class="almacen-mensaje">
                <p>📭 No hay categorías registradas</p>
                <p>Crea la primera categoría usando el formulario superior</p>
            </div>
        <?php else: ?>
            <div class="almacen-tabla-container">
                <table class="almacen-tabla">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Productos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                            <td>
                                <?php
                                $iconos_tipo = [
                                    'material_medico' => '🩺',
                                    'limpieza' => '🧹',
                                    'oficina' => '📎',
                                    'equipo' => '🔧',
                                    'otro' => '📦'
                                ];
                                $icono = $iconos_tipo[$c['tipo']] ?? '📁';
                                ?>
                                <span class="almacen-badge info"><?= $icono ?> <?= ucfirst(str_replace('_', ' ', $c['tipo'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars($c['descripcion'] ?? '-') ?></td>
                            <td>
                                <span class="almacen-badge <?= $c['total_productos'] > 0 ? 'success' : 'secondary' ?>">
                                    <?= $c['total_productos'] ?> productos
                                </span>
                            </td>
                            <td>
                                <?php if ($c['total_productos'] == 0): ?>
                                    <a href="categorias.php?eliminar=<?= $c['id'] ?>" 
                                       class="almacen-btn almacen-btn-sm almacen-btn-danger"
                                       onclick="return confirm('¿Eliminar esta categoría?')">
                                        🗑️ Eliminar
                                    </a>
                                <?php else: ?>
                                    <span class="almacen-badge secondary" title="No se puede eliminar: tiene productos asociados">
                                        🔒 En uso
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('formCategoria')?.addEventListener('submit', function(e) {
    const nombre = this.querySelector('[name="nombre"]').value.trim();
    if (nombre.length < 3) {
        e.preventDefault();
        alert('El nombre debe tener al menos 3 caracteres');
        return;
    }
    
    const btn = this.querySelector('button[type="submit"]');
    btn.innerHTML = '⏳ Guardando...';
    btn.disabled = true;
});
</script>

<?php require_once '../../includes/footer.php'; ?>g