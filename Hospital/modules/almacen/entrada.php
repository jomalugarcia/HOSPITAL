<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Obtener proveedores
$proveedores = $pdo->query("
    SELECT id, nombre 
    FROM proveedores 
    WHERE activo = 1 
    ORDER BY nombre
")->fetchAll();

// Obtener categorías para modal de nuevo producto
$categorias = $pdo->query("
    SELECT * FROM categorias_almacen 
    ORDER BY nombre
")->fetchAll();

// Obtener productos para referencia
$productos = $pdo->query("
    SELECT 
        p.id,
        p.codigo,
        p.nombre,
        p.precio_unitario,
        p.unidad_medida,
        COALESCE(sa.cantidad_total, 0) as stock_actual
    FROM productos p
    LEFT JOIN stock_actual sa ON p.id = sa.producto_id AND sa.departamento = 'almacen'
    WHERE p.activo = 1 AND p.departamento = 'almacen'
    ORDER BY p.nombre
")->fetchAll();

// Fecha actual
$fecha_actual = date('Y-m-d');
?>

<div class="fade-in modulo-almacen">
    <!-- Header -->
    <div class="almacen-header">
        <div>
            <h1>📥 Entrada de Productos</h1>
            <p class="almacen-subtitle">Ingrese los datos de la compra y escanee productos</p>
        </div>
        <a href="productos.php" class="almacen-btn almacen-btn-outline">← Volver</a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Entrada registrada correctamente</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <div class="almacen-card">
        <form method="POST" action="procesar_entrada.php" id="formEntrada">
            <!-- Datos de compra (PROVEEDOR, FACTURA, FECHA) -->
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label class="required">PROVEEDOR</label>
                    <select name="proveedor_id" class="almacen-form-control" required>
                        <option value="">Seleccionar proveedor</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="almacen-form-group">
                    <label>FACTURA/REMISIÓN</label>
                    <input type="text" name="referencia" class="almacen-form-control" placeholder="Número de factura">
                </div>
                
                <div class="almacen-form-group">
                    <label>FECHA</label>
                    <input type="date" name="fecha" class="almacen-form-control" value="<?= $fecha_actual ?>">
                </div>
            </div>

            <h4>Productos a recibir <span style="font-size: 0.9rem; font-weight: normal; margin-left: var(--spacing-md); color: var(--gray-600);">
                (Escanee el código en el campo "PRODUCTO")
            </span></h4>
            
            <!-- Contenedor de productos (inicia con una fila) -->
            <div id="items-container">
                <!-- Se llenará con JavaScript -->
            </div>

            <!-- Botón para agregar manualmente -->
            <button type="button" class="almacen-btn almacen-btn-outline" onclick="agregarFilaVacia()" style="margin-bottom: var(--spacing-lg);">
                ➕ Agregar producto manualmente
            </button>

            <!-- Totales -->
            <div class="almacen-totales">
                <div class="almacen-totales-item">
                    <div class="almacen-totales-label">Total productos:</div>
                    <div class="almacen-totales-valor" id="total-items">0</div>
                </div>
                <div class="almacen-totales-item">
                    <div class="almacen-totales-label">Total compra:</div>
                    <div class="almacen-totales-valor success" id="total-compra">$0.00</div>
                </div>
            </div>

            <div class="almacen-form-group">
                <label>OBSERVACIONES</label>
                <textarea name="observaciones" class="almacen-form-control" rows="3" 
                          placeholder="Notas adicionales..."></textarea>
            </div>

            <div class="almacen-flex almacen-gap-sm">
                <button type="submit" class="almacen-btn almacen-btn-success">Registrar Entrada</button>
                <a href="productos.php" class="almacen-btn almacen-btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<!-- MODAL PARA CREAR PRODUCTO RÁPIDO -->
<div class="almacen-modal" id="modalNuevoProducto">
    <div class="almacen-modal-content">
        <h2>➕ Nuevo Producto</h2>
        <form id="formNuevoProducto">
            <input type="hidden" id="nuevo_codigo_temporal">
            
            <div class="almacen-form-group">
                <label class="required">Código de barras</label>
                <input type="text" id="nuevo_codigo" class="almacen-form-control" readonly style="background: var(--gray-100);">
            </div>
            
            <div class="almacen-form-group">
                <label class="required">Nombre del producto</label>
                <input type="text" id="nuevo_nombre" class="almacen-form-control" required placeholder="Ej: Resma de papel A4">
            </div>
            
            <div class="almacen-form-group">
                <label class="required">Categoría</label>
                <select id="nuevo_categoria" class="almacen-form-control" required>
                    <option value="">Seleccionar categoría</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label>Precio unitario</label>
                    <input type="number" id="nuevo_precio" class="almacen-form-control" step="0.01" value="0.00">
                </div>
                
                <div class="almacen-form-group">
                    <label>Unidad medida</label>
                    <select id="nuevo_unidad" class="almacen-form-control">
                        <option value="pieza">Pieza</option>
                        <option value="caja">Caja</option>
                        <option value="paquete">Paquete</option>
                        <option value="litro">Litro</option>
                        <option value="kilogramo">Kilogramo</option>
                    </select>
                </div>
            </div>
            
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label>Stock mínimo</label>
                    <input type="number" id="nuevo_minimo" class="almacen-form-control" value="5">
                </div>
                
                <div class="almacen-form-group">
                    <label>Stock máximo</label>
                    <input type="number" id="nuevo_maximo" class="almacen-form-control" value="100">
                </div>
            </div>
            
            <div class="almacen-flex">
                <button type="button" class="almacen-btn almacen-btn-success" onclick="guardarNuevoProducto()">Guardar</button>
                <button type="button" class="almacen-btn almacen-btn-outline" onclick="cerrarModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
// ============================================
// VARIABLES GLOBALES
// ============================================
let productosData = <?= json_encode($productos) ?>;
let filaActiva = null;
let timeoutId = null;
let productosEscaneados = []; // Array para evitar duplicados

// ============================================
// INICIALIZACIÓN
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    agregarFilaVacia();
});

// ============================================
// FUNCIONES PARA FILAS DE PRODUCTOS
// ============================================
function agregarFilaVacia() {
    const container = document.getElementById('items-container');
    const filaId = 'fila_' + Date.now();
    
    const nuevaFila = document.createElement('div');
    nuevaFila.className = 'almacen-item-row';
    nuevaFila.id = filaId;
    nuevaFila.innerHTML = `
        <div class="almacen-form-row">
            <!-- Campo de PRODUCTO con escáner integrado -->
            <div class="almacen-form-group" style="flex: 2;">
                <label class="required">PRODUCTO</label>
                <div class="almacen-flex" style="gap: 5px;" id="producto-container-${filaId}">
                    <input type="text" class="almacen-form-control producto-scanner" 
                           placeholder="Escanear código..." 
                           style="flex: 1;"
                           data-fila-id="${filaId}"
                           autocomplete="off">
                    <button type="button" class="almacen-btn almacen-btn-sm almacen-btn-success" 
                            onclick="abrirModalParaFila('${filaId}')" title="Crear nuevo producto">➕</button>
                </div>
                <input type="hidden" name="producto_id[]" class="producto-id" value="">
                <div class="producto-info" style="margin-top: 5px; font-size: 0.9rem;"></div>
            </div>
            
            <!-- CANTIDAD -->
            <div class="almacen-form-group" style="flex: 1;">
                <label class="required">CANTIDAD</label>
                <input type="number" name="cantidad[]" class="almacen-form-control cantidad-input" 
                       required min="1" value="1">
            </div>

            <!-- PRECIO UNITARIO -->
            <div class="almacen-form-group" style="flex: 1;">
                <label class="required">PRECIO UNIT.</label>
                <input type="number" name="precio_unitario[]" class="almacen-form-control precio-input" 
                       required min="0" step="0.01" value="0.00">
            </div>

            <!-- SUBTOTAL -->
            <div class="almacen-form-group" style="flex: 1;">
                <label>SUBTOTAL</label>
                <input type="text" class="almacen-form-control subtotal-field" readonly value="$0.00">
            </div>

            <!-- ELIMINAR -->
            <div class="almacen-form-group" style="flex: 0.3;">
                <label style="visibility: hidden;">Eliminar</label>
                <button type="button" class="almacen-btn almacen-btn-sm almacen-btn-danger" 
                        onclick="eliminarFila(this)">✗</button>
            </div>
        </div>
    `;
    
    container.appendChild(nuevaFila);
    
    // Configurar event listeners
    const scannerInput = nuevaFila.querySelector('.producto-scanner');
    const cantidadInput = nuevaFila.querySelector('.cantidad-input');
    const precioInput = nuevaFila.querySelector('.precio-input');
    
    scannerInput.addEventListener('input', function(e) {
        manejarEscaneo(e, this);
    });
    
    cantidadInput.addEventListener('input', actualizarTotales);
    precioInput.addEventListener('input', actualizarTotales);
    
    // Enfocar el campo de escaneo
    setTimeout(() => scannerInput.focus(), 100);
    
    actualizarTotales();
    return nuevaFila;
}

function eliminarFila(btn) {
    const container = document.getElementById('items-container');
    const fila = btn.closest('.almacen-item-row');
    const productoId = fila.querySelector('.producto-id')?.value;
    
    // Si la fila tenía un producto, quitarlo de la lista de escaneados
    if (productoId && productosEscaneados.includes(parseInt(productoId))) {
        const index = productosEscaneados.indexOf(parseInt(productoId));
        if (index > -1) {
            productosEscaneados.splice(index, 1);
        }
    }
    
    if (container.children.length > 1) {
        fila.remove();
        actualizarTotales();
    } else {
        alert('Debe haber al menos un producto');
    }
}

// ============================================
// MANEJO DEL ESCÁNER
// ============================================
function manejarEscaneo(event, inputElement) {
    const codigo = inputElement.value.trim();
    
    // Limpiar timeout anterior
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
    
    if (codigo.length >= 3) {
        timeoutId = setTimeout(() => {
            procesarCodigo(codigo, inputElement);
        }, 100);
    }
}

function procesarCodigo(codigo, inputElement) {
    const fila = inputElement.closest('.almacen-item-row');
    const infoDiv = fila.querySelector('.producto-info');
    const contenedorProducto = fila.querySelector('.almacen-flex'); // El contenedor del input y botón
    
    infoDiv.innerHTML = '<span class="almacen-text-info">🔍 Buscando...</span>';
    
    fetch(`ajax/buscar_producto.php?q=${encodeURIComponent(codigo)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                infoDiv.innerHTML = `<span class="almacen-text-danger">❌ Error: ${data.error}</span>`;
                setTimeout(() => infoDiv.innerHTML = '', 3000);
            } else if (data.length > 0) {
                // Producto encontrado
                const producto = data[0];
                
                // ===== VALIDACIÓN: Evitar duplicados =====
                if (productosEscaneados.includes(producto.id)) {
                    infoDiv.innerHTML = `
                        <span class="almacen-text-warning">⚠️ Este producto ya fue agregado</span>
                    `;
                    setTimeout(() => infoDiv.innerHTML = '', 2000);
                    inputElement.value = '';
                    return;
                }
                
                // Agregar a la lista de escaneados
                productosEscaneados.push(producto.id);
                
                // Establecer el ID del producto
                const idInput = fila.querySelector('.producto-id');
                idInput.value = producto.id;
                
                // Sugerir precio
                const precioInput = fila.querySelector('.precio-input');
                if (precioInput && parseFloat(precioInput.value) === 0 && producto.precio_unitario > 0) {
                    precioInput.value = producto.precio_unitario;
                }
                
                // ===== NUEVA FUNCIONALIDAD: ELIMINAR EL CAMPO Y MOSTRAR SOLO EL BADGE =====
                // Guardar referencia al contenedor del input
                const campoProducto = contenedorProducto;
                
                // Crear el badge grande que reemplazará al campo
                const badgeProducto = document.createElement('div');
                badgeProducto.className = 'almacen-badge success';
                badgeProducto.style.cssText = `
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    width: 100%;
                    padding: 0.75rem 1rem;
                    font-size: 1rem;
                    font-weight: 600;
                    background: #27ae60;
                    color: white;
                    border-radius: 0.5rem;
                    box-sizing: border-box;
                    min-height: 45px;
                `;
                badgeProducto.innerHTML = `
                    <span>✓ ${producto.codigo} - ${producto.nombre}</span>
                    <span style="font-size: 0.9rem; opacity: 0.9;">ID: ${producto.id}</span>
                `;
                
                // Reemplazar el contenido del contenedor (input + botón) por el badge
                campoProducto.innerHTML = '';
                campoProducto.appendChild(badgeProducto);
                
                // La información adicional ya no es necesaria
                infoDiv.innerHTML = '';
                
                // Crear una nueva fila para el siguiente producto
                agregarFilaVacia();
                
                // Enfocar el nuevo campo de escaneo
                setTimeout(() => {
                    const nuevosCampos = document.querySelectorAll('.producto-scanner');
                    const ultimoCampo = nuevosCampos[nuevosCampos.length - 1];
                    if (ultimoCampo && !ultimoCampo.disabled) {
                        ultimoCampo.focus();
                    }
                }, 100);
                
            } else {
                // Producto no encontrado
                infoDiv.innerHTML = `
                    <span class="almacen-text-warning">⚠️ Código "${codigo}" no encontrado</span>
                    <button class="almacen-btn almacen-btn-sm almacen-btn-warning" 
                            onclick="abrirModalNuevo('${codigo}', '${fila.id}')">➕ Crear nuevo</button>
                `;
                inputElement.value = '';
            }
        })
        .catch(error => {
            infoDiv.innerHTML = `<span class="almacen-text-danger">❌ Error en búsqueda</span>`;
            setTimeout(() => infoDiv.innerHTML = '', 3000);
        });
}

// ============================================
// MODAL PARA NUEVO PRODUCTO
// ============================================
function abrirModalNuevo(codigo, filaId) {
    document.getElementById('nuevo_codigo').value = codigo;
    document.getElementById('nuevo_codigo_temporal').value = codigo;
    document.getElementById('modalNuevoProducto').style.display = 'flex';
    
    // Guardar referencia a la fila
    filaActiva = document.getElementById(filaId);
}

function abrirModalParaFila(filaId) {
    const fila = document.getElementById(filaId);
    const scannerInput = fila.querySelector('.producto-scanner');
    const codigo = scannerInput.value.trim() || 'NUEVO' + Date.now();
    
    document.getElementById('nuevo_codigo').value = codigo;
    document.getElementById('nuevo_codigo_temporal').value = codigo;
    document.getElementById('modalNuevoProducto').style.display = 'flex';
    filaActiva = fila;
}

function cerrarModal() {
    document.getElementById('modalNuevoProducto').style.display = 'none';
    document.getElementById('formNuevoProducto').reset();
    filaActiva = null;
}

function guardarNuevoProducto() {
    const codigo = document.getElementById('nuevo_codigo').value;
    const nombre = document.getElementById('nuevo_nombre').value;
    const categoria_id = document.getElementById('nuevo_categoria').value;
    const precio = document.getElementById('nuevo_precio').value;
    const unidad = document.getElementById('nuevo_unidad').value;
    const stock_minimo = document.getElementById('nuevo_minimo').value;
    const stock_maximo = document.getElementById('nuevo_maximo').value;
    
    if (!nombre || !categoria_id) {
        alert('Nombre y categoría son obligatorios');
        return;
    }
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '⏳ Guardando...';
    
    fetch('ajax/guardar_producto_rapido.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            codigo: codigo,
            nombre: nombre,
            categoria_id: categoria_id,
            precio_unitario: precio,
            unidad_medida: unidad,
            stock_minimo: stock_minimo,
            stock_maximo: stock_maximo,
            departamento: 'almacen'
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = 'Guardar';
        
        if (data.error) {
            alert('Error: ' + data.error);
        } else {
            // Agregar el nuevo producto a productosData
            productosData.push({
                id: data.id,
                codigo: data.codigo,
                nombre: data.nombre,
                precio_unitario: parseFloat(precio)
            });
            
            // Simular que se escaneó el código
            if (filaActiva) {
                const scannerInput = filaActiva.querySelector('.producto-scanner');
                scannerInput.value = codigo;
                procesarCodigo(codigo, scannerInput);
            } else {
                // Si no hay fila activa, crear una nueva
                agregarFilaVacia();
                setTimeout(() => {
                    const ultimaFila = document.querySelector('#items-container .almacen-item-row:last-child');
                    const scannerInput = ultimaFila.querySelector('.producto-scanner');
                    scannerInput.value = codigo;
                    procesarCodigo(codigo, scannerInput);
                }, 100);
            }
            
            cerrarModal();
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = 'Guardar';
        alert('Error al guardar: ' + error);
    });
}

// ============================================
// CÁLCULOS DE TOTALES
// ============================================
function actualizarTotales() {
    let totalCompra = 0;
    let totalItems = 0;
    
    document.querySelectorAll('.almacen-item-row').forEach(row => {
        const cantidad = parseFloat(row.querySelector('.cantidad-input')?.value) || 0;
        const precio = parseFloat(row.querySelector('.precio-input')?.value) || 0;
        const subtotal = cantidad * precio;
        
        const subtotalField = row.querySelector('.subtotal-field');
        if (subtotalField) {
            subtotalField.value = `$${subtotal.toFixed(2)}`;
        }
        
        totalCompra += subtotal;
        totalItems += cantidad;
    });
    
    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('total-compra').textContent = `$${totalCompra.toFixed(2)}`;
}

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});

// Reiniciar lista de escaneados al enviar el formulario (opcional)
document.getElementById('formEntrada')?.addEventListener('submit', function() {
    // No reiniciamos aquí porque podría haber errores
});
</script>

<?php require_once '../../includes/footer.php'; ?>