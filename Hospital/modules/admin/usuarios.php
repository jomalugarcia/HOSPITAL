<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// Obtener módulos para permisos
$modulos = $pdo->query("SELECT * FROM modulos ORDER BY nombre")->fetchAll();

// Obtener lista de usuarios existentes
$usuarios = $pdo->query("
    SELECT u.*, GROUP_CONCAT(m.nombre SEPARATOR ', ') as modulos_asignados
    FROM usuarios u
    LEFT JOIN usuario_modulos um ON u.id = um.usuario_id
    LEFT JOIN modulos m ON um.modulo_id = m.id
    GROUP BY u.id
    ORDER BY u.nombre
")->fetchAll();

// Procesar mensajes de éxito/error
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}

require_once '../../includes/header.php';
?>

<div class="fade-in">
   
<!-- Header con título y acciones -->

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl);">
        <div>
            <h1 style="margin-bottom: var(--spacing-xs);">👥 Administración de Usuarios</h1>
            <p style="color: var(--gray-600);">Gestiona los usuarios y sus permisos de acceso al sistema</p>
        </div>
        <div>
            <span class="badge badge-primary">Total: <?= count($usuarios) ?> usuarios</span>
        </div>
    </div>

    <?php if ($success_message): ?>
    <div class="alert alert-success">
        ✅ <?= htmlspecialchars($success_message) ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        ❌ <?= htmlspecialchars($error_message) ?>
    </div>
<?php endif; ?>

    <!-- Tarjeta del formulario -->
    <div class="card" style="background: white;">
        <h3 style="display: flex; align-items: center; gap: var(--spacing-sm); color: var(--primary);">
            <span style="font-size: 1.5rem;">➕</span> 
            Registrar Nuevo Usuario
        </h3>
        
        <form method="POST" action="guardar_usuario.php" id="formUsuario">
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
                            placeholder="Ej: Juan Pérez González"
                            autocomplete="off"
                        >
                        <small style="color: var(--gray-500);">Nombre real del usuario</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Usuario</label>
                        <input 
                            type="text" 
                            name="usuario" 
                            class="form-control" 
                            required 
                            placeholder="Ej: jperez"
                            autocomplete="off"
                            pattern="[a-zA-Z0-9_]+"
                            title="Solo letras, números y guión bajo"
                        >
                        <small style="color: var(--gray-500);">Nombre de usuario para iniciar sesión</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Contraseña</label>
                        <input 
                            type="password" 
                            name="password" 
                            class="form-control" 
                            required 
                            placeholder="••••••••"
                            id="password"
                        >
                        <small style="color: var(--gray-500);">Mínimo 6 caracteres</small>
                    </div>
                </div>
                
                <!-- Columna derecha -->
                <div>
                    <div class="form-group">
                        <label>Confirmar Contraseña</label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            class="form-control" 
                            placeholder="••••••••"
                            id="confirm_password"
                        >
                        <small id="passwordMatch" style="color: var(--gray-500);"></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Estado del Usuario</label>
                        <select name="activo" class="form-control">
                            <option value="1">✅ Activo</option>
                            <option value="0">❌ Inactivo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Rol Principal</label>
                        <select name="rol" class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="admin">👑 Administrador</option>
                            <option value="doctor">🩺 Doctor</option>
                            <option value="farmacia">💊 Farmacia</option>
                            <option value="registro">📋 Registro</option>
                        </select>
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
                        ">
                            <input 
                                type="checkbox" 
                                name="modulos[]" 
                                value="<?= $m['id'] ?>"
                                style="width: 18px; height: 18px;"
                            >
                            <span style="font-weight: 500;"><?= htmlspecialchars($m['nombre']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: var(--spacing-md);">
                    <button type="button" class="btn btn-sm btn-outline" onclick="checkAll()">✓ Seleccionar Todos</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="uncheckAll()">✗ Deseleccionar Todos</button>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div style="display: flex; gap: var(--spacing-md); margin-top: var(--spacing-xl);">
                <button type="submit" class="btn btn-primary" style="min-width: 200px;">
                    <span>💾</span> Guardar Usuario
                </button>
                <button type="reset" class="btn btn-outline">
                    <span>🗑️</span> Limpiar Formulario
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de usuarios existentes -->
    <div class="card" style="margin-top: var(--spacing-xl);">
        <h3 style="display: flex; align-items: center; gap: var(--spacing-sm);">
            <span>📋</span> Usuarios Registrados
        </h3>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Módulos Asignados</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($u['usuario']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td>
                            <?php 
                            $rolClass = '';
                            $rolIcon = '';
                            switch($u['rol']) {
                                case 'admin':
                                    $rolClass = 'badge-primary';
                                    $rolIcon = '👑';
                                    break;
                                case 'doctor':
                                    $rolClass = 'badge-success';
                                    $rolIcon = '🩺';
                                    break;
                                case 'farmacia':
                                    $rolClass = 'badge-warning';
                                    $rolIcon = '💊';
                                    break;
                                case 'registro':
                                    $rolClass = 'badge-info';
                                    $rolIcon = '📋';
                                    break;
                                default:
                                    $rolClass = 'badge-secondary';
                                    $rolIcon = '👤';
                            }
                            ?>
                            <span class="badge <?= $rolClass ?>">
                                <?= $rolIcon ?> <?= $u['rol'] ? ucfirst($u['rol']) : 'Usuario' ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                <?php 
                                $modulos_asignados = explode(', ', $u['modulos_asignados'] ?? '');
                                foreach ($modulos_asignados as $mod):
                                    if (empty($mod)) continue;
                                ?>
                                    <span class="badge badge-primary" style="background: var(--primary-soft); color: var(--primary-dark);">
                                        <?= htmlspecialchars($mod) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($u['activo']): ?>
                                <span class="badge badge-success">✓ Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ Inactivo</span>
                            <?php endif; ?>
                        </td>
  <td>
    <div style="display: flex; gap: var(--spacing-xs);">
<!-- Botón editar (ahora funcional) -->
<a href="editar_usuario.php?id=<?= $u['id'] ?>" 
   class="btn btn-sm btn-outline"
   style="border-color: var(--primary); color: var(--primary);"
   title="Editar usuario">
    ✏️ Editar
</a>
        
        <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <?php if ($u['activo']): ?>
                <a href="desactivar_usuario.php?id=<?= $u['id'] ?>" 
                   class="btn btn-sm btn-outline" 
                   style="border-color: var(--warning); color: var(--warning);"
                   onclick="return confirm('¿Desactivar este usuario? Podrás activarlo después')">
                    ⭕ Desactivar
                </a>
            <?php else: ?>
                <a href="activar_usuario.php?id=<?= $u['id'] ?>" 
                   class="btn btn-sm btn-outline" 
                   style="border-color: var(--success); color: var(--success);">
                    ✅ Activar
                </a>
            <?php endif; ?>
            
            <!-- Botón de eliminar con modal -->
            <a href="#" 
               class="btn btn-sm btn-outline" 
               style="border-color: var(--danger); color: var(--danger);"
               onclick="return confirmarEliminacion('eliminar_usuario.php?id=<?= $u['id'] ?>', '<?= htmlspecialchars($u['nombre'], ENT_QUOTES) ?>')">
                🗑️ Eliminar
            </a>
        <?php endif; ?>
    </div>
</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Validar que las contraseñas coincidan
document.getElementById('confirm_password')?.addEventListener('keyup', function() {
    var password = document.getElementById('password').value;
    var confirm = this.value;
    var matchMsg = document.getElementById('passwordMatch');
    
    if (password === confirm) {
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
}

// Deseleccionar todos los checkboxes
function uncheckAll() {
    document.querySelectorAll('input[name="modulos[]"]').forEach(cb => cb.checked = false);
}

// Validar formulario antes de enviar
document.getElementById('formUsuario')?.addEventListener('submit', function(e) {
    var password = document.getElementById('password').value;
    var confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 6 caracteres');
        return false;
    }
    
    // Deshabilitar botón para evitar doble envío
    const btn = this.querySelector('button[type="submit"]');
    btn.innerHTML = '⏳ Guardando...';
    btn.disabled = true;
});

// Función para editar usuario (puedes implementarla después)
function editarUsuario(id) {
    alert('Funcionalidad de edición próximamente');
}
</script>

<style>
/* Estilos adicionales para los checkboxes */
.checkbox-card:hover {
    background: var(--primary-soft) !important;
    border-color: var(--primary) !important;
    transform: translateY(-2px);
}

.checkbox-card:has(input:checked) {
    background: var(--primary-soft) !important;
    border-color: var(--primary) !important;
}

.checkbox-card input:checked + span {
    font-weight: 600;
}

/* Animación para nuevas filas */
@keyframes highlight {
    from { background: var(--primary-soft); }
    to { background: transparent; }
}

tr:target {
    animation: highlight 2s ease;
}
</style>

<!-- Modal de confirmación (agregar antes del footer) -->
<div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center;">
    <div style="background: white; padding: var(--spacing-xl); border-radius: var(--radius-lg); max-width: 400px;">
        <h3 style="color: var(--danger);">⚠️ Confirmar Eliminación</h3>
        <p id="confirmMessage" style="margin: var(--spacing-lg) 0;"></p>
        <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end;">
            <button class="btn btn-outline" onclick="cerrarModal()">Cancelar</button>
            <a href="#" id="confirmButton" class="btn btn-danger">Eliminar</a>
        </div>
    </div>
</div>

<script>
function confirmarEliminacion(url, nombre) {
    document.getElementById('confirmMessage').innerHTML = `¿Eliminar permanentemente al usuario <strong>${nombre}</strong>?<br><br>Esta acción no se puede deshacer.`;
    document.getElementById('confirmButton').href = url;
    document.getElementById('confirmModal').style.display = 'flex';
    return false;
}

function cerrarModal() {
    document.getElementById('confirmModal').style.display = 'none';
}
</script>

<?php require_once '../../includes/footer.php'; ?>
