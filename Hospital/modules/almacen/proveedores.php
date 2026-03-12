<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Procesar acciones
if (isset($_GET['desactivar'])) {
    $id = $_GET['desactivar'];
    $stmt = $pdo->prepare("UPDATE proveedores SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: proveedores.php?msg=desactivado");
    exit;
}

if (isset($_GET['activar'])) {
    $id = $_GET['activar'];
    $stmt = $pdo->prepare("UPDATE proveedores SET activo = 1 WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: proveedores.php?msg=activado");
    exit;
}

// Obtener proveedores
$proveedores = $pdo->query("
    SELECT * FROM proveedores
    ORDER BY activo DESC, nombre
")->fetchAll();

$activos = array_filter($proveedores, fn($p) => $p['activo'] == 1);
$inactivos = array_filter($proveedores, fn($p) => $p['activo'] == 0);
?>

<div class="fade-in modulo-almacen">
    <!-- Header -->
    <div class="almacen-header">
        <div>
            <h1>🤝 Gestión de Proveedores</h1>
            <p class="almacen-subtitle">Administra las empresas que suministran productos</p>
        </div>
        <div>
            <span class="almacen-badge success">Activos: <?= count($activos) ?></span>
            <span class="almacen-badge secondary">Inactivos: <?= count($inactivos) ?></span>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            ✅ <?php 
            if ($_GET['msg'] == 'guardado') echo 'Proveedor guardado correctamente';
            if ($_GET['msg'] == 'actualizado') echo 'Proveedor actualizado correctamente';
            if ($_GET['msg'] == 'desactivado') echo 'Proveedor desactivado correctamente';
            if ($_GET['msg'] == 'activado') echo 'Proveedor activado correctamente';
            ?>
        </div>
    <?php endif; ?>

    <!-- Botón nuevo proveedor -->
    <div style="margin-bottom: var(--spacing-lg);">
        <a href="#" onclick="mostrarFormulario()" class="almacen-btn almacen-btn-success">
            <span>➕</span> Nuevo Proveedor
        </a>
    </div>

    <!-- Formulario nuevo proveedor (oculto inicialmente) -->
    <div id="formNuevo" style="display: none; margin-bottom: var(--spacing-xl);">
        <div class="almacen-card">
            <h3 style="color: var(--primary);">➕ Registrar Nuevo Proveedor</h3>
            
            <form method="POST" action="guardar_proveedor.php" id="formProveedor">
                <div class="almacen-form-row">
                    <div class="almacen-form-group">
                        <label class="required">Nombre o Razón Social</label>
                        <input type="text" name="nombre" class="almacen-form-control" required 
                               placeholder="Ej: Distribuidora Médica SA de CV">
                    </div>
                    
                    <div class="almacen-form-group">
                        <label>RFC</label>
                        <input type="text" name="rfc" class="almacen-form-control" 
                               placeholder="Ej: ABC123456XYZ">
                    </div>
                </div>
                
                <div class="almacen-form-row">
                    <div class="almacen-form-group">
                        <label>Persona de contacto</label>
                        <input type="text" name="contacto" class="almacen-form-control" 
                               placeholder="Ej: Juan Pérez">
                    </div>
                    
                    <div class="almacen-form-group">
                        <label>Teléfono</label>
                        <input type="tel" name="telefono" class="almacen-form-control" 
                               placeholder="Ej: 55-1234-5678">
                    </div>
                </div>
                
                <div class="almacen-form-row">
                    <div class="almacen-form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="almacen-form-control" 
                               placeholder="Ej: contacto@proveedor.com">
                    </div>
                    
                    <div class="almacen-form-group">
                        <label>Dirección</label>
                        <input type="text" name="direccion" class="almacen-form-control" 
                               placeholder="Ej: Calle, número, colonia, ciudad">
                    </div>
                </div>
                
                <div class="almacen-flex almacen-gap-sm" style="margin-top: var(--spacing-lg);">
                    <button type="submit" class="almacen-btn almacen-btn-success">
                        <span>💾</span> Guardar Proveedor
                    </button>
                    <button type="button" class="almacen-btn almacen-btn-outline" onclick="ocultarFormulario()">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de proveedores activos -->
    <div class="almacen-card">
        <h3>📋 Proveedores Activos</h3>
        
        <?php if (empty($activos)): ?>
            <div class="almacen-mensaje">
                No hay proveedores activos registrados
            </div>
        <?php else: ?>
            <div class="almacen-tabla-container">
                <table class="almacen-tabla">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Contacto</th>
                            <th>Teléfono/Email</th>
                            <th>RFC</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activos as $p): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                <?php if ($p['direccion']): ?>
                                    <br><small><?= htmlspecialchars($p['direccion']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['contacto'] ?? '-') ?></td>
                            <td>
                                <?= htmlspecialchars($p['telefono'] ?? '-') ?>
                                <?php if ($p['email']): ?>
                                    <br><small><?= htmlspecialchars($p['email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['rfc'] ?? '-') ?></td>
                            <td>
                                <div class="almacen-flex almacen-gap-sm">
                                    <a href="editar_proveedor.php?id=<?= $p['id'] ?>" 
                                       class="almacen-btn almacen-btn-sm almacen-btn-outline" 
                                       title="Editar">
                                        ✏️
                                    </a>
                                    <a href="proveedores.php?desactivar=<?= $p['id'] ?>" 
                                       class="almacen-btn almacen-btn-sm almacen-btn-outline" 
                                       style="border-color: var(--warning); color: var(--warning);"
                                       onclick="return confirm('¿Desactivar este proveedor?')"
                                       title="Desactivar">
                                        ⭕
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabla de proveedores inactivos -->
    <?php if (!empty($inactivos)): ?>
    <div class="almacen-card" style="margin-top: var(--spacing-lg);">
        <h3>📋 Proveedores Inactivos</h3>
        <div class="almacen-tabla-container">
            <table class="almacen-tabla">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Teléfono/Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inactivos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= htmlspecialchars($p['contacto'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['telefono'] ?? '-') ?></td>
                        <td>
                            <a href="proveedores.php?activar=<?= $p['id'] ?>" 
                               class="almacen-btn almacen-btn-sm almacen-btn-success"
                               onclick="return confirm('¿Activar este proveedor?')">
                                ✅ Activar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function mostrarFormulario() {
    document.getElementById('formNuevo').style.display = 'block';
    window.scrollTo(0, document.getElementById('formNuevo').offsetTop - 100);
}

function ocultarFormulario() {
    document.getElementById('formNuevo').style.display = 'none';
    document.getElementById('formProveedor').reset();
}
</script>

<?php require_once '../../includes/footer.php'; ?>