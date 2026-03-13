<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';
require_once '../../includes/header.php';

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: usuarios.php?error=ID no válido");
    exit;
}

$id_usuario = $_GET['id'];

// Obtener datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: usuarios.php?error=Usuario no encontrado");
    exit;
}

// Obtener módulos del usuario
$stmt = $pdo->prepare("SELECT modulo_id FROM usuario_modulos WHERE usuario_id = ?");
$stmt->execute([$id_usuario]);
$modulos_usuario = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener todos los módulos
$modulos = $pdo->query("SELECT * FROM modulos ORDER BY nombre")->fetchAll();
?>

<div class="fade-in">
    <!-- Header con navegación -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1 style="margin-bottom: var(--spacing-xs);">✏️ Editar Usuario</h1>
            <p style="color: var(--gray-600);">Modificando: <strong><?= htmlspecialchars($usuario['nombre']) ?></strong></p>
        </div>
        <a href="usuarios.php" class="btn btn-outline">
            <span>←</span> Volver a Usuarios
        </a>
    </div>

    <!-- Tarjeta del formulario de edición -->
    <div class="card" style="background: white; max-width: 800px; margin: 0 auto;">
        <form method="POST" action="actualizar_usuario.php" id="formEditarUsuario">
            <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
            
            <div class="form-row">
                <!-- Columna izquierda -->
                <div>
                    <div class="form-group">
                        <label class="required">Nombre Completo</label>
                        <input 
                            type="text" 
                            name="nombre" 
                            class="form-control" 
                            required 
                            value="<?= htmlspecialchars($usuario['nombre']) ?>"
                            autocomplete="off"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Usuario</label>
                        <input 
                            type="text" 
                            name="usuario" 
                            class="form-control" 
                            required 
                            value="<?= htmlspecialchars($usuario['usuario']) ?>"
                            autocomplete="off"
                            readonly
                            style="background: var(--gray-100); cursor: not-allowed;"
                        >
                        <small style="color: var(--gray-500);">El nombre de usuario no se puede cambiar</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Nueva Contraseña</label>
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Dejar en blanco para mantener actual"
                            id="password"
                        >
                        <small style="color: var(--gray-500);">Solo si desea cambiar la contraseña</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirmar Nueva Contraseña</label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            class="form-control" 
                            placeholder="Confirmar nueva contraseña"
                            id="confirm_password"
                        >
                        <small id="passwordMatch" style="color: var(--gray-500);"></small>
                    </div>
                </div>
                
                <!-- Columna derecha -->
                <div>
                    <div class="form-group">
                        <label>Estado del Usuario</label>
                        <select name="activo" class="form-control">
                            <option value="1" <?= $usuario['activo'] ? 'selected' : '' ?>>✅ Activo</option>
                            <option value="0" <?= !$usuario['activo'] ? 'selected' : '' ?>>❌ Inactivo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Rol Principal</label>
                        <select name="rol" class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="admin" <?= $usuario['rol'] == 'admin' ? 'selected' : '' ?>>👑 Administrador</option>
                            <option value="doctor" <?= $usuario['rol'] == 'doctor' ? 'selected' : '' ?>>🩺 Doctor</option>
                            <option value="farmacia" <?= $usuario['rol'] == 'farmacia' ? 'selected' : '' ?>>💊 Farmacia</option>
                            <option value="registro" <?= $usuario['rol'] == 'registro' ? 'selected' : '' ?>>📋 Registro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Registro</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            value="<?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'] ?? 'now')) ?>" 
                            readonly
                            style="background: var(--gray-100);"
                        >
                    </div>
                </div>
            </div>
            
            <!-- Permisos por módulo -->
            <div style="margin-top: var(--spacing-lg); border-top: 2px solid var(--gray-200); padding-top: var(--spacing-lg);">
                <h4 style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-md);">
                    <span>🔐</span> Permisos de Acceso por Módulo
                </h4>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: var(--spacing-md);">
                    <?php foreach ($modulos as $m): ?>
                        <label class="checkbox-card" style="
                            display: flex;
                            align-items: center;
                            gap: var(--spacing-sm);
                            padding: var(--spacing-sm) var(--spacing-md);
                            background: var(--gray-100);
                            border-radius: var(--radius-md);
                            cursor: pointer;
                            transition: all var(--transition-fast);
                            border: 2px solid transparent;
                            <?= in_array($m['id'], $modulos_usuario) ? 'background: var(--primary-soft); border-color: var(--primary);' : '' ?>
                        ">
                            <input 
                                type="checkbox" 
                                name="modulos[]" 
                                value="<?= $m['id'] ?>"
                                style="width: 18px; height: 18px;"
                                <?= in_array($m['id'], $modulos_usuario) ? 'checked' : '' ?>
                            >
                            <span style="font-weight: 500;"><?= htmlspecialchars($m['nombre']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: var(--spacing-md); display: flex; gap: var(--spacing-sm);">
                    <button type="button" class="btn btn-sm btn-outline" onclick="checkAll()">✓ Seleccionar Todos</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="uncheckAll()">✗ Deseleccionar Todos</button>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-primary" style="min-width: 200px;">
                    <span>💾</span> Actualizar Usuario
                </button>
                <a href="usuarios.php" class="btn btn-outline">
                    <span>✗</span> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Validar que las contraseñas coincidan (solo si se está cambiando)
document.getElementById('confirm_password')?.addEventListener('keyup', function() {
    var password = document.getElementById('password').value;
    var confirm = this.value;
    var matchMsg = document.getElementById('passwordMatch');
    
    if (password === '' && confirm === '') {
        matchMsg.innerHTML = '';
        this.style.borderColor = '';
    } else if (password === confirm) {
        matchMsg.innerHTML = '✓ Las contraseñas coinciden';
        matchMsg.style.color = 'var(--success)';
        this.style.borderColor = 'var(--success)';
    } else {
        matchMsg.innerHTML = '✗ Las contraseñas no coinciden';
        matchMsg.style.color = 'var(--danger)';
        this.style.borderColor = 'var(--danger)';
    }
});

// Seleccionar todos los checkboxes
function checkAll() {
    document.querySelectorAll('input[name="modulos[]"]').forEach(cb => cb.checked = true);
    // Actualizar estilo de las cards
    document.querySelectorAll('.checkbox-card').forEach(card => {
        card.style.background = 'var(--primary-soft)';
        card.style.borderColor = 'var(--primary)';
    });
}

// Deseleccionar todos los checkboxes
function uncheckAll() {
    document.querySelectorAll('input[name="modulos[]"]').forEach(cb => cb.checked = false);
    // Actualizar estilo de las cards
    document.querySelectorAll('.checkbox-card').forEach(card => {
        card.style.background = 'var(--gray-100)';
        card.style.borderColor = 'transparent';
    });
}

// Actualizar estilo de card cuando se clickea un checkbox
document.querySelectorAll('.checkbox-card input').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const card = this.closest('.checkbox-card');
        if (this.checked) {
            card.style.background = 'var(--primary-soft)';
            card.style.borderColor = 'var(--primary)';
        } else {
            card.style.background = 'var(--gray-100)';
            card.style.borderColor = 'transparent';
        }
    });
});

// Validar formulario antes de enviar
document.getElementById('formEditarUsuario')?.addEventListener('submit', function(e) {
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    if (password !== '' && password.length < 6) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 6 caracteres');
        return false;
    }
    
    // Deshabilitar botón para evitar doble envío
    const btn = this.querySelector('button[type="submit"]');
    btn.innerHTML = '⏳ Actualizando...';
    btn.disabled = true;
});
</script>

<?php require_once '../../includes/footer.php'; ?>