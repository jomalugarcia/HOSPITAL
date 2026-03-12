<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'config/db.php';
require_once 'includes/header.php';

// Establecer zona horaria
date_default_timezone_set('America/Mexico_City');

// Obtener módulos permitidos para el usuario
$modulos = []; // Inicializar como array vacío

try {
    $stmt = $pdo->prepare("
        SELECT m.nombre, m.ruta,
               CASE m.nombre
                   WHEN 'Administración' THEN '👑'
                   WHEN 'Almacén' THEN '📦'
                   WHEN 'Citas' THEN '📅'
                   WHEN 'Doctor' THEN '🩺'
                   WHEN 'Farmacia' THEN '💊'
                   WHEN 'Registro Pacientes' THEN '📋'
                   ELSE '🔷'
               END as icono
        FROM usuario_modulos um
        JOIN modulos m ON um.modulo_id = m.id
        WHERE um.usuario_id = ?
        ORDER BY 
            CASE m.nombre
                WHEN 'Registro Pacientes' THEN 1
                WHEN 'Citas' THEN 2
                WHEN 'Doctor' THEN 3
                WHEN 'Farmacia' THEN 4
                WHEN 'Almacén' THEN 5
                WHEN 'Administración' THEN 6
                ELSE 7
            END
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $modulos = $stmt->fetchAll();
    
    // Si no hay módulos, al menos mostrar algo
    if (empty($modulos)) {
        error_log("Usuario {$_SESSION['user_id']} no tiene módulos asignados");
    }
    
} catch (PDOException $e) {
    error_log("Error al obtener módulos: " . $e->getMessage());
    $modulos = []; // Asegurar que sea array aunque haya error
}

// Obtener estadísticas generales para el dashboard
$stats = [];

// Total de pacientes (si tiene permiso)
if (usuarioTieneModulo($pdo, $_SESSION['user_id'], 'registro')) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM pacientes");
        $stats['pacientes'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $stats['pacientes'] = 0;
    }
}

// Citas de hoy (si tiene permiso)
if (usuarioTieneModulo($pdo, $_SESSION['user_id'], 'citas')) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM citas 
            WHERE fecha = CURDATE() AND estado = 'pendiente'
        ");
        $stmt->execute();
        $stats['citas_hoy'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $stats['citas_hoy'] = 0;
    }
}

// Productos con stock bajo (si tiene permiso de almacén)
if (usuarioTieneModulo($pdo, $_SESSION['user_id'], 'almacen')) {
    try {
        // Usar stock_actual directamente de productos
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM productos 
            WHERE activo = 1 AND stock_actual <= stock_minimo
        ");
        $stats['stock_bajo'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $stats['stock_bajo'] = 0;
    }
}

// Función auxiliar para verificar módulos
function usuarioTieneModulo($pdo, $user_id, $ruta) {
    try {
        $stmt = $pdo->prepare("
            SELECT 1 FROM usuario_modulos um
            JOIN modulos m ON um.modulo_id = m.id
            WHERE um.usuario_id = ? AND m.ruta = ?
        ");
        $stmt->execute([$user_id, $ruta]);
        return $stmt->fetch() ? true : false;
    } catch (PDOException $e) {
        return false;
    }
}

// Fecha y hora local
$fecha_actual = date('d/m/Y');
$hora_actual = date('H:i');
$hora_actual_12h = date('h:i A');

// Saludo según la hora
$hora = (int)date('H');
if ($hora >= 5 && $hora < 12) {
    $saludo = '¡Buenos días';
    $icono_saludo = '☀️';
} elseif ($hora >= 12 && $hora < 18) {
    $saludo = '¡Buenas tardes';
    $icono_saludo = '⛅';
} elseif ($hora >= 18 && $hora < 21) {
    $saludo = '¡Buenas noches';
    $icono_saludo = '🌆';
} else {
    $saludo = '¡Buenas noches';
    $icono_saludo = '🌙';
}
?>

<div class="fade-in">
    <!-- Header del dashboard -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl); flex-wrap: wrap; gap: var(--spacing-md);">
        <div>
            <h1 style="margin-bottom: var(--spacing-xs); display: flex; align-items: center; gap: var(--spacing-sm);">
                <?= $icono_saludo ?> <?= $saludo ?>, <span style="color: var(--primary);"><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></span>!
            </h1>
            <p style="color: var(--gray-600); display: flex; align-items: center; gap: var(--spacing-lg); flex-wrap: wrap;">
                <span style="display: flex; align-items: center; gap: var(--spacing-xs);">
                    <span style="font-size: 1.2rem;">📅</span> <?= $fecha_actual ?>
                </span>
                <span style="display: flex; align-items: center; gap: var(--spacing-xs);">
                    <span style="font-size: 1.2rem;">⏰</span> <?= $hora_actual ?> (<?= $hora_actual_12h ?>)
                </span>
            </p>
        </div>
        
        <!-- Acciones rápidas -->
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="#" onclick="window.location.reload()" class="btn btn-sm btn-outline" title="Actualizar dashboard">
                🔄 Actualizar
            </a>
            <a href="logout.php" class="btn btn-sm btn-danger" onclick="return confirm('¿Cerrar sesión?')">
                🚪 Salir
            </a>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <?php if (!empty($stats)): ?>
        <div class="stats-grid" style="margin-bottom: var(--spacing-xl);">
            <?php if (isset($stats['pacientes'])): ?>
                <div class="stat-card primary">
                    <div style="font-size: 2rem; margin-bottom: var(--spacing-sm);">👥</div>
                    <div class="stat-value"><?= $stats['pacientes'] ?></div>
                    <div class="stat-label">Pacientes Registrados</div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($stats['citas_hoy'])): ?>
                <div class="stat-card <?= $stats['citas_hoy'] > 0 ? 'warning' : 'success' ?>">
                    <div style="font-size: 2rem; margin-bottom: var(--spacing-sm);">📅</div>
                    <div class="stat-value"><?= $stats['citas_hoy'] ?></div>
                    <div class="stat-label">Citas para Hoy</div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($stats['stock_bajo'])): ?>
                <div class="stat-card <?= $stats['stock_bajo'] > 0 ? 'danger' : 'success' ?>">
                    <div style="font-size: 2rem; margin-bottom: var(--spacing-sm);">📦</div>
                    <div class="stat-value"><?= $stats['stock_bajo'] ?></div>
                    <div class="stat-label">Productos Stock Bajo</div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Módulos disponibles -->
    <?php if (!empty($modulos)): ?>
        <div style="margin-bottom: var(--spacing-lg);">
            <h2 style="display: flex; align-items: center; gap: var(--spacing-sm);">
                <span>🔷</span> Módulos del Sistema
            </h2>
            <p style="color: var(--gray-600); margin-bottom: var(--spacing-lg);">
                Selecciona un módulo para comenzar a trabajar
            </p>
        </div>

        <div class="grid-modulos">
            <?php foreach ($modulos as $m): ?>
                <a href="modules/<?= htmlspecialchars($m['ruta']) ?>/index.php" class="card-modulo">
                    <div style="font-size: 3rem; margin-bottom: var(--spacing-md);">
                        <?= $m['icono'] ?>
                    </div>
                    <h3 style="margin: 0; color: var(--primary-dark);"><?= htmlspecialchars($m['nombre']) ?></h3>
                    <p style="margin-top: var(--spacing-sm); font-size: var(--font-size-sm); opacity: 0.8;">
                        <?php
                        switch($m['nombre']) {
                            case 'Administración':
                                echo 'Gestión de usuarios y permisos';
                                break;
                            case 'Almacén':
                                echo 'Control de inventario y productos';
                                break;
                            case 'Citas':
                                echo 'Agendamiento de citas médicas';
                                break;
                            case 'Doctor':
                                echo 'Atención y diagnóstico de pacientes';
                                break;
                            case 'Farmacia':
                                echo 'Dispensación de medicamentos';
                                break;
                            case 'Registro Pacientes':
                                echo 'Registro y expedientes';
                                break;
                            default:
                                echo 'Acceder al módulo';
                        }
                        ?>
                    </p>
                </a>    
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning" style="text-align: center; padding: var(--spacing-xl);">
            <p style="font-size: 1.2rem;">⚠️ No tienes módulos asignados</p>
            <p>Contacta al administrador para que te asigne permisos</p>
        </div>
    <?php endif; ?>

    <!-- Mensaje de bienvenida -->
    <div style="text-align: center; margin-top: var(--spacing-xl); color: var(--gray-500); font-size: var(--font-size-sm);">
        <p>✨ Sistema de Gestión Hospitalaria - Versión 1.0</p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>