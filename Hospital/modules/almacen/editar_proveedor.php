<?php
require_once '../../config/config.php';
$modulo_requerido = 'almacen';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

if (!isset($_GET['id'])) {
    header("Location: proveedores.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ?");
$stmt->execute([$id]);
$proveedor = $stmt->fetch();

if (!$proveedor) {
    header("Location: proveedores.php?error=Proveedor no encontrado");
    exit;
}
?>

<div class="fade-in modulo-almacen">
    <!-- Header -->
    <div class="almacen-header">
        <div>
            <h1>✏️ Editar Proveedor</h1>
            <p class="almacen-subtitle">Modificando: <strong><?= htmlspecialchars($proveedor['nombre']) ?></strong></p>
        </div>
        <a href="proveedores.php" class="almacen-btn almacen-btn-outline">← Volver</a>
    </div>

    <div class="almacen-card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="actualizar_proveedor.php">
            <input type="hidden" name="id" value="<?= $proveedor['id'] ?>">
            
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label class="required">Nombre</label>
                    <input type="text" name="nombre" class="almacen-form-control" required 
                           value="<?= htmlspecialchars($proveedor['nombre']) ?>">
                </div>
                <div class="almacen-form-group">
                    <label>RFC</label>
                    <input type="text" name="rfc" class="almacen-form-control" 
                           value="<?= htmlspecialchars($proveedor['rfc'] ?? '') ?>">
                </div>
            </div>
            
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label>Contacto</label>
                    <input type="text" name="contacto" class="almacen-form-control" 
                           value="<?= htmlspecialchars($proveedor['contacto'] ?? '') ?>">
                </div>
                <div class="almacen-form-group">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono" class="almacen-form-control" 
                           value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>">
                </div>
            </div>
            
            <div class="almacen-form-row">
                <div class="almacen-form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="almacen-form-control" 
                           value="<?= htmlspecialchars($proveedor['email'] ?? '') ?>">
                </div>
                <div class="almacen-form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="almacen-form-control" 
                           value="<?= htmlspecialchars($proveedor['direccion'] ?? '') ?>">
                </div>
            </div>
            
            <div class="almacen-flex almacen-gap-sm" style="margin-top: var(--spacing-lg);">
                <button type="submit" class="almacen-btn almacen-btn-primary">Actualizar Proveedor</button>
                <a href="proveedores.php" class="almacen-btn almacen-btn-outline">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>