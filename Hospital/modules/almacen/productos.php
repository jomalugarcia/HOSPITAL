<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// ============================================
// PROCESAR HABILITAR/DESHABILITAR - ANTES DE CUALQUIER HTML
// ============================================
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $estado = $_GET['estado'] ?? 1;
    
    try {
        $stmt = $pdo->prepare("UPDATE productos SET activo = ? WHERE id = ? AND departamento = 'almacen'");
        $stmt->execute([$estado, $id]);
        
        if ($estado == 1) {
            header("Location: productos.php?msg=habilitado");
        } else {
            header("Location: productos.php?msg=deshabilitado");
        }
        exit;
    } catch (PDOException $e) {
        header("Location: productos.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// ============================================
// RECUPERAR DATOS SI HAY ERROR (NUEVO)
// ============================================
$mostrar_formulario = isset($_GET['mostrar_formulario']) || isset($_GET['error']);
$codigo_error = $_GET['codigo'] ?? '';
$nombre_error = $_GET['nombre'] ?? '';
$descripcion_error = $_GET['descripcion'] ?? '';
$categoria_error = $_GET['categoria_almacen_id'] ?? '';
$unidad_error = $_GET['unidad_medida'] ?? '';
$stock_minimo_error = $_GET['stock_minimo'] ?? 5;
$stock_maximo_error = $_GET['stock_maximo'] ?? 100;
$precio_error = $_GET['precio_unitario'] ?? 0;
$ubicacion_error = $_GET['ubicacion'] ?? '';
$codigo_secundario_error = $_GET['codigo_secundario'] ?? '';

// ============================================
// AHORA SÍ, INCLUIMOS EL HEADER
// ============================================
require_once '../../includes/header.php';

// ============================================
// OBTENER CATEGORÍAS DE ALMACÉN
// ============================================
$categorias = $pdo->query("
    SELECT * FROM categorias_almacen 
    ORDER BY nombre
")->fetchAll();

// ============================================
// OBTENER PRODUCTOS CON STOCK Y VALORES
// ============================================
$productos = $pdo->query("
    SELECT 
        p.id,
        p.codigo,
        p.nombre,
        p.descripcion,
        p.categoria_almacen_id,
        c.nombre as categoria_nombre,
        c.tipo as categoria_tipo,
        p.unidad_medida,
        p.stock_minimo,
        p.stock_maximo,
        p.precio_unitario,
        p.ubicacion,
        p.activo,
        COALESCE(sa.cantidad_total, 0) as stock_actual,
        COALESCE(sa.cantidad_total, 0) * p.precio_unitario as valor_total,
        CASE 
            WHEN COALESCE(sa.cantidad_total, 0) <= p.stock_minimo THEN 'bajo'
            WHEN COALESCE(sa.cantidad_total, 0) >= p.stock_maximo THEN 'exceso'
            ELSE 'normal'
        END as estado_stock,
        CASE
            WHEN COALESCE(sa.cantidad_total, 0) < p.stock_minimo 
            THEN p.stock_minimo - COALESCE(sa.cantidad_total, 0)
            ELSE 0
        END as cantidad_faltante
    FROM productos p
    LEFT JOIN categorias_almacen c ON p.categoria_almacen_id = c.id
    LEFT JOIN stock_actual sa ON p.id = sa.producto_id AND sa.departamento = 'almacen'
    WHERE p.departamento = 'almacen'
    ORDER BY p.activo DESC, 
        CASE 
            WHEN COALESCE(sa.cantidad_total, 0) <= p.stock_minimo THEN 1
            WHEN COALESCE(sa.cantidad_total, 0) >= p.stock_maximo THEN 3
            ELSE 2
        END,
        p.nombre
")->fetchAll();

// ============================================
// ESTADÍSTICAS
// ============================================
$stats = [];
$stats['total_productos'] = count($productos);
$stats['activos'] = 0;
$stats['inactivos'] = 0;
$stats['stock_bajo'] = 0;
$stats['valor_total'] = 0;

foreach ($productos as $p) {
    if ($p['activo'] == 1) {
        $stats['activos']++;
    } else {
        $stats['inactivos']++;
    }
    
    if ($p['estado_stock'] == 'bajo' && $p['activo'] == 1) $stats['stock_bajo']++;
    $stats['valor_total'] += $p['valor_total'];
}

$valor_total_formateado = '$' . number_format($stats['valor_total'], 2);
?>

<div class="fade-in modulo-almacen">
    <!-- Header -->
    <div class="almacen-header">
        <div>
            <h1>📦 Gestión de Productos</h1>
            <p class="almacen-subtitle">Catálogo general de productos de almacén</p>
        </div>
        <a href="index.php" class="almacen-btn almacen-btn-outline">← Volver</a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            ✅ <?php 
            if ($_GET['msg'] == 'guardado') echo 'Producto guardado correctamente';
            if ($_GET['msg'] == 'actualizado') echo 'Producto actualizado correctamente';
            if ($_GET['msg'] == 'entrada') echo 'Entrada registrada correctamente';
            if ($_GET['msg'] == 'salida') echo 'Salida registrada correctamente';
            if ($_GET['msg'] == 'codigo_agregado') echo 'Código agregado correctamente';
            if ($_GET['msg'] == 'habilitado') echo 'Producto habilitado correctamente';
            if ($_GET['msg'] == 'deshabilitado') echo 'Producto deshabilitado correctamente';
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            ❌ <?= htmlspecialchars($_GET['error']) ?>
            <?php if ($mostrar_formulario): ?>
                <br><small>Por favor, corrige el código e intenta nuevamente</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <div class="almacen-stats">
        <div class="almacen-stat-card primary">
            <div class="almacen-stat-icon">📦</div>
            <div class="almacen-stat-value"><?= $stats['total_productos'] ?></div>
            <div class="almacen-stat-label">Total Productos</div>
        </div>
        
        <div class="almacen-stat-card success">
            <div class="almacen-stat-icon">✅</div>
            <div class="almacen-stat-value"><?= $stats['activos'] ?></div>
            <div class="almacen-stat-label">Activos</div>
        </div>
        
        <div class="almacen-stat-card warning">
            <div class="almacen-stat-icon">⚠️</div>
            <div class="almacen-stat-value"><?= $stats['stock_bajo'] ?></div>
            <div class="almacen-stat-label">Stock Bajo</div>
        </div>
        
        <div class="almacen-stat-card info">
            <div class="almacen-stat-icon">💰</div>
            <div class="almacen-stat-value"><?= $valor_total_formateado ?></div>
            <div class="almacen-stat-label">Valor Total</div>
        </div>
    </div>

    <!-- Botones de acción superiores -->
    <div class="almacen-flex almacen-gap-sm" style="margin-bottom: var(--spacing-lg); flex-wrap: wrap;">
        <a href="#" onclick="mostrarFormularioProducto()" class="almacen-btn almacen-btn-success">
            <span>➕</span> Nuevo Producto
        </a>
        <a href="entrada.php" class="almacen-btn almacen-btn-primary">
            <span>⬆️</span> Registrar Entrada
        </a>
        <a href="salidas.php" class="almacen-btn almacen-btn-warning">
            <span>⬇️</span> Registrar Salida
        </a>
        <a href="movimientos.php" class="almacen-btn almacen-btn-outline">
            <span>📋</span> Ver Movimientos
        </a>
    </div>

    <!-- Formulario nuevo producto (oculto inicialmente, se muestra si hay error) -->
    <div id="formProducto" style="display: <?= $mostrar_formulario ? 'block' : 'none' ?>; margin-bottom: var(--spacing-xl);">
        <div class="almacen-card">
            <h3 style="color: var(--primary); margin-bottom: var(--spacing-lg);">➕ Nuevo Producto</h3>
            
            <form method="POST" action="guardar_producto.php" id="formNuevoProducto">
                <div class="almacen-form-row">
                    <div class="almacen-form-group">
                        <label class="required">Código</label>
                        <input type="text" name="codigo" class="almacen-form-control" required 
                               placeholder="Ej: PAP-A4-001" maxlength="50"
                               value="<?= htmlspecialchars($codigo_error) ?>">
                        <div id="validacion-codigo"></div>
                    </div>
                    
                    <div class="almacen-form-group">
                        <label class="required">Nombre</label>
                        <input type="text" name="nombre" class="almacen-form-control" required 
                               placeholder="Ej: Resma de papel A4"
                               value="<?= htmlspecialchars($nombre_error) ?>">
                    </div>
                </div>
                
                <div class="almacen-form-group">
                    <label>Descripción</label>
                    <textarea name="descripcion" class="almacen-form-control" rows="2" 
                              placeholder="Descripción detallada..."><?= htmlspecialchars($descripcion_error) ?></textarea>
                </div>
                
                <div class="almacen-form-row">
                    <div class="almacen-form-group">
                        <label class="required">Categoría</label>
                        <select name="categoria_almacen_id" class="almacen-form-control" required>
                            <option value="">Seleccionar categoría...</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $categoria_error == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="almacen-form-group">
                        <label class="required">Unidad de medida</label>
                        <select name="unidad_medida" class="almacen-form-control" required>
                            <option value="pieza" <?= $unidad_error == 'pieza' ? 'selected' : '' ?>>Pieza</option>
                            <option value="caja" <?= $unidad_error == 'caja' ? 'selected' : '' ?>>Caja</option>
                            <option value="paquete" <?= $unidad_error == 'paquete' ? 'selected' : '' ?>>Paquete</option>
                            <option value="litro" <?= $unidad_error == 'litro' ? 'selected' : '' ?>>Litro</option>
                            <option value="kilogramo" <?= $unidad_error == 'kilogramo' ? 'selected' : '' ?>>Kilogramo</option>
                            <option value="metro" <?= $unidad_error == 'metro' ? 'selected' : '' ?>>Metro</option>
                        </select>
                    </div>
                </div>
                
                <div class="almacen-form-row">
                    <div class="almacen-form-group">
                        <label>Precio unitario</label>
                        <input type="number" step="0.01" name="precio_unitario" class="almacen-form-control" 
                               value="<?= htmlspecialchars($precio_error) ?>">
                    </div>
                    
                    <div class="almacen-form-group">
                        <label>Ubicación</label>
                        <input type="text" name="ubicacion" class="almacen-form-control" 
                               placeholder="Ej: Estante A, Nivel 3"
                               value="<?= htmlspecialchars($ubicacion_error) ?>">
                    </div>
                </div>
                
                <div class="almacen-form-row">
                    <div class="almacen-form-group">
                        <label>Stock mínimo</label>
                        <input type="number" name="stock_minimo" class="almacen-form-control" 
                               value="<?= htmlspecialchars($stock_minimo_error) ?>" min="0">
                    </div>
                    
                    <div class="almacen-form-group">
                        <label>Stock máximo</label>
                        <input type="number" name="stock_maximo" class="almacen-form-control" 
                               value="<?= htmlspecialchars($stock_maximo_error) ?>" min="0">
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <label style="display: flex; align-items: center; gap: var(--spacing-sm);">
                        <input type="checkbox" name="codigo_secundario_check" id="codigo_secundario_check"
                               <?= !empty($codigo_secundario_error) ? 'checked' : '' ?>> 
                        Agregar código secundario
                    </label>
                </div>
                
                <div id="campo_codigo_secundario" style="display: <?= !empty($codigo_secundario_error) ? 'block' : 'none' ?>; margin-top: var(--spacing-md);">
                    <div class="almacen-form-group">
                        <label>Código secundario</label>
                        <input type="text" name="codigo_secundario" class="almacen-form-control" 
                               placeholder="Otro código de barras"
                               value="<?= htmlspecialchars($codigo_secundario_error) ?>">
                    </div>
                </div>
                
                <div class="almacen-flex almacen-gap-sm" style="margin-top: var(--spacing-lg);">
                    <button type="submit" class="almacen-btn almacen-btn-success">Guardar Producto</button>
                    <button type="button" class="almacen-btn almacen-btn-outline" onclick="ocultarFormularioProducto()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de productos con escáner integrado -->
    <div class="almacen-card">
        <div class="almacen-card-header">
            <h3>📋 Inventario Actual</h3>
            <div style="display: flex; gap: var(--spacing-sm);">
                <input type="text" id="buscarProducto" class="almacen-form-control" placeholder="🔍 Buscar producto..." style="width: 250px;">
                <input type="text" id="escaneo-rapido" class="almacen-form-control" placeholder="📷 Escanear código..." style="width: 200px;">
            </div>
        </div>
        
        <?php if (empty($productos)): ?>
            <div class="almacen-mensaje">
                <p>📭 No hay productos registrados</p>
                <p>Comienza agregando tu primer producto</p>
            </div>
        <?php else: ?>
            <div class="almacen-tabla-container">
                <table class="almacen-tabla" id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): 
                            // Obtener códigos secundarios (solo los que NO son principales)
                            $stmt = $pdo->prepare("
                                SELECT codigo_barras FROM productos_codigos 
                                WHERE producto_id = ? AND es_principal = 0 AND activo = 1
                            ");
                            $stmt->execute([$p['id']]);
                            $secundarios = $stmt->fetchAll();
                            
                            $clase_fila = $p['activo'] == 0 ? 'inactivo' : '';
                            $estado_texto = '';
                            $estado_clase = '';
                            
                            if ($p['activo'] == 0) {
                                $estado_texto = 'Inactivo';
                                $estado_clase = 'secondary';
                            } elseif ($p['estado_stock'] == 'bajo') {
                                $estado_texto = 'Stock bajo';
                                $estado_clase = 'warning';
                            } elseif ($p['estado_stock'] == 'exceso') {
                                $estado_texto = 'Stock alto';
                                $estado_clase = 'info';
                            } else {
                                $estado_texto = 'Normal';
                                $estado_clase = 'success';
                            }
                        ?>
                            <tr class="<?= $clase_fila ?>" data-id="<?= $p['id'] ?>" data-codigo="<?= strtolower(htmlspecialchars($p['codigo'])) ?>" data-nombre="<?= strtolower(htmlspecialchars($p['nombre'])) ?>">
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <span><strong>🔵 <?= htmlspecialchars($p['codigo']) ?></strong></span>
                                        <?php if ($secundarios): ?>
                                            <div style="margin-top: 4px;">
                                            <?php foreach ($secundarios as $s): ?>
                                                <span style="font-size: 0.8rem; color: var(--gray-600); display: block;">
                                                    🟡 <?= htmlspecialchars($s['codigo_barras']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                    <?php if ($p['descripcion']): ?>
                                        <br><small style="color: var(--gray-500);"><?= htmlspecialchars(substr($p['descripcion'], 0, 50)) ?><?= strlen($p['descripcion']) > 50 ? '...' : '' ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $icono_categoria = '';
                                    switch($p['categoria_tipo'] ?? '') {
                                        case 'material_medico': $icono_categoria = '🩺'; break;
                                        case 'limpieza': $icono_categoria = '🧹'; break;
                                        case 'oficina': $icono_categoria = '📎'; break;
                                        case 'equipo': $icono_categoria = '🔧'; break;
                                        default: $icono_categoria = '📦';
                                    }
                                    ?>
                                    <span class="almacen-badge" style="background: var(--info-light); color: var(--info-dark); padding: 0.25rem 0.75rem; border-radius: 20px;">
                                        <?= $icono_categoria ?> <?= htmlspecialchars($p['categoria_nombre'] ?? 'Sin categoría') ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <span style="font-weight: bold; <?= ($p['estado_stock'] == 'bajo' && $p['activo'] == 1) ? 'color: var(--warning);' : '' ?>">
                                            <?= $p['stock_actual'] ?> <?= $p['unidad_medida'] ?>
                                        </span>
                                        <?php if ($p['estado_stock'] == 'bajo' && $p['activo'] == 1 && $p['cantidad_faltante'] > 0): ?>
                                            <br><small style="color: var(--warning);">Faltan <?= $p['cantidad_faltante'] ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><strong>$<?= number_format($p['precio_unitario'], 2) ?></strong></td>
                                <td>
                                    <span class="almacen-badge <?= $estado_clase ?>" style="padding: 0.25rem 0.75rem; border-radius: 20px;">
                                        <?= $estado_texto ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="almacen-flex almacen-gap-sm" style="justify-content: center;">
                                        <!-- Editar -->
                                        <a href="editar_producto.php?id=<?= $p['id'] ?>" 
                                           class="almacen-btn almacen-btn-sm almacen-btn-outline" 
                                           title="Editar"
                                           style="padding: 0.5rem; min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                                            ✏️
                                        </a>
                                        
                                        <!-- Gestionar códigos -->
                                        <a href="codigos_producto.php?id=<?= $p['id'] ?>" 
                                           class="almacen-btn almacen-btn-sm almacen-btn-outline" 
                                           title="Gestionar códigos"
                                           style="padding: 0.5rem; min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                                            🏷️
                                        </a>
                                        
                                        <!-- Habilitar/Deshabilitar -->
                                        <?php if ($p['activo'] == 1): ?>
                                            <a href="productos.php?toggle=1&id=<?= $p['id'] ?>&estado=0" 
                                               class="almacen-btn almacen-btn-sm almacen-btn-outline" 
                                               style="border-color: var(--warning); color: var(--warning); padding: 0.5rem; min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;"
                                               onclick="return confirm('¿Deshabilitar este producto? No se podrá usar en entradas/salidas')"
                                               title="Deshabilitar">
                                                ⭕
                                            </a>
                                        <?php else: ?>
                                            <a href="productos.php?toggle=1&id=<?= $p['id'] ?>&estado=1" 
                                               class="almacen-btn almacen-btn-sm almacen-btn-outline" 
                                               style="border-color: var(--success); color: var(--success); padding: 0.5rem; min-width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;"
                                               title="Habilitar">
                                                ✅
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Leyenda -->
            <div class="almacen-leyenda">
                <div><span class="almacen-badge warning">⚠️</span> Stock bajo</div>
                <div><span class="almacen-badge info">📈</span> Stock alto</div>
                <div><span class="almacen-badge success">✅</span> Stock normal</div>
                <div><span class="almacen-badge primary">🔵</span> Código principal</div>
                <div><span class="almacen-badge secondary">🟡</span> Código secundario</div>
                <div><span class="almacen-badge secondary">⭕</span> Producto inactivo</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ============================================
// VARIABLES GLOBALES
// ============================================
let productosEncontrados = [];
let timeoutId = null;
let timeoutValidacion = null;

// ============================================
// ESCÁNER DE CÓDIGOS
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const escaner = document.getElementById('escaneo-rapido');
    if (escaner) escaner.focus();
});

document.getElementById('escaneo-rapido').addEventListener('input', function(e) {
    const codigo = e.target.value.trim();
    
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
    
    if (codigo.length >= 3) {
        timeoutId = setTimeout(() => {
            procesarCodigoEscaner(codigo);
        }, 100);
    }
});

function procesarCodigoEscaner(codigo) {
    fetch(`ajax/buscar_producto.php?q=${encodeURIComponent(codigo)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
            } else if (data.length > 0) {
                const producto = data[0];
                
                if (productosEncontrados.includes(producto.id)) {
                    alert('⚠️ Este producto ya fue agregado a la lista');
                    document.getElementById('escaneo-rapido').value = '';
                    return;
                }
                
                productosEncontrados.push(producto.id);
                
                const filas = document.querySelectorAll('tbody tr');
                let encontrado = false;
                
                filas.forEach(fila => {
                    const filaId = fila.getAttribute('data-id');
                    if (filaId == producto.id) {
                        encontrado = true;
                        fila.style.backgroundColor = '#d4edda';
                        fila.style.transition = 'background-color 0.5s';
                        setTimeout(() => {
                            fila.style.backgroundColor = '';
                        }, 2000);
                        
                        const escaner = document.getElementById('escaneo-rapido');
                        escaner.style.borderColor = 'var(--success)';
                        escaner.value = `✅ ${producto.codigo} - ${producto.nombre}`;
                        
                        escaner.disabled = true;
                        setTimeout(() => {
                            escaner.disabled = false;
                            escaner.value = '';
                            escaner.style.borderColor = '';
                            escaner.focus();
                        }, 2000);
                    }
                });
                
                if (!encontrado) {
                    alert('Producto encontrado pero no está en la lista actual');
                    document.getElementById('escaneo-rapido').value = '';
                }
            } else {
                alert('❌ Producto no encontrado');
                document.getElementById('escaneo-rapido').value = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al buscar el producto');
        });
}

// ============================================
// VALIDACIÓN EN TIEMPO REAL DEL CÓDIGO DE BARRAS (NUEVO)
// ============================================
document.querySelector('input[name="codigo"]')?.addEventListener('input', function(e) {
    const codigo = e.target.value.trim();
    const mensajeDiv = document.getElementById('validacion-codigo');
    
    if (timeoutValidacion) {
        clearTimeout(timeoutValidacion);
    }
    
    if (mensajeDiv) mensajeDiv.innerHTML = '';
    
    if (codigo.length >= 3) {
        timeoutValidacion = setTimeout(() => {
            verificarCodigoExistente(codigo);
        }, 500);
    }
});

function verificarCodigoExistente(codigo) {
    let mensajeDiv = document.getElementById('validacion-codigo');
    if (!mensajeDiv) {
        mensajeDiv = document.createElement('div');
        mensajeDiv.id = 'validacion-codigo';
        mensajeDiv.style.marginTop = '5px';
        const campoCodigo = document.querySelector('input[name="codigo"]').closest('.almacen-form-group');
        campoCodigo.appendChild(mensajeDiv);
    }
    
    mensajeDiv.innerHTML = '<span class="almacen-text-info">🔍 Verificando...</span>';
    
    fetch(`ajax/verificar_codigo.php?codigo=${encodeURIComponent(codigo)}`)
        .then(response => response.json())
        .then(data => {
            if (data.existe) {
                mensajeDiv.innerHTML = `
                    <span class="almacen-text-danger" style="display: flex; align-items: center; gap: 5px;">
                        ⚠️ El código "${codigo}" ya está registrado en el producto:
                        <strong>${data.producto_nombre}</strong> (${data.producto_codigo})
                    </span>
                `;
                const btnGuardar = document.querySelector('button[type="submit"]');
                btnGuardar.disabled = true;
                btnGuardar.style.opacity = '0.5';
                btnGuardar.style.cursor = 'not-allowed';
            } else {
                mensajeDiv.innerHTML = '<span class="almacen-text-success">✅ Código disponible</span>';
                const btnGuardar = document.querySelector('button[type="submit"]');
                btnGuardar.disabled = false;
                btnGuardar.style.opacity = '1';
                btnGuardar.style.cursor = 'pointer';
                
                setTimeout(() => {
                    mensajeDiv.innerHTML = '';
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mensajeDiv.innerHTML = '<span class="almacen-text-danger">❌ Error al verificar</span>';
        });
}

// ============================================
// FUNCIONES DEL FORMULARIO
// ============================================
function mostrarFormularioProducto() {
    document.getElementById('formProducto').style.display = 'block';
    window.scrollTo(0, document.getElementById('formProducto').offsetTop - 100);
}

function ocultarFormularioProducto() {
    document.getElementById('formProducto').style.display = 'none';
    document.getElementById('formNuevoProducto').reset();
    document.getElementById('campo_codigo_secundario').style.display = 'none';
    document.getElementById('codigo_secundario_check').checked = false;
    
    // Limpiar parámetros de la URL (recargar sin datos)
    window.location.href = 'productos.php';
}

document.getElementById('codigo_secundario_check')?.addEventListener('change', function() {
    document.getElementById('campo_codigo_secundario').style.display = this.checked ? 'block' : 'none';
});

document.getElementById('buscarProducto')?.addEventListener('keyup', function() {
    const texto = this.value.toLowerCase();
    const filas = document.querySelectorAll('tbody tr');
    
    filas.forEach(fila => {
        const nombre = fila.getAttribute('data-nombre') || '';
        const codigo = fila.getAttribute('data-codigo') || '';
        
        if (nombre.includes(texto) || codigo.includes(texto)) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
});

// ============================================
// VALIDACIÓN DEL FORMULARIO ANTES DE ENVIAR
// ============================================
document.getElementById('formNuevoProducto')?.addEventListener('submit', function(e) {
    const btnGuardar = this.querySelector('button[type="submit"]');
    if (btnGuardar.disabled) {
        e.preventDefault();
        alert('Por favor, corrige el código antes de guardar');
        return false;
    }
    
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '⏳ Guardando...';
});

window.addEventListener('load', function() {
    productosEncontrados = [];
});
</script>

<?php require_once '../../includes/footer.php'; ?>