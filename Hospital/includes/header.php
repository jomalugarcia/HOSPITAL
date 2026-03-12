<?php
require_once __DIR__ . '/../config/config.php';

// Obtener módulos del usuario para el menú lateral
$menu_modulos = [];
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/db.php';
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
            ORDER BY m.nombre
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $menu_modulos = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error al cargar módulos: " . $e->getMessage());
        $menu_modulos = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SISTEMA_NOMBRE ?> | Panel de Control</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/estilos.css">
    
    <!-- CSS específico del módulo - VERSIÓN CORREGIDA -->
    <?php 
    // Obtener la ruta actual
    $ruta_actual = $_SERVER['PHP_SELF'];
    
    // Detectar si estamos en módulo almacén
    if (strpos($ruta_actual, '/modules/almacen/') !== false): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>modules/almacen/assets/css/almacen.css">
    <?php 
    // Detectar si estamos en módulo farmacia
    elseif (strpos($ruta_actual, '/modules/farmacia/') !== false): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>modules/farmacia/assets/css/farmacia.css">
    <?php endif; ?>
    
    <style>
        /* ===== ESTILOS PARA EL LAYOUT CON SIDEBAR ===== */
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
        }
        
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            background: var(--gray-100);
        }
        
        /* Layout principal */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-dark) 0%, #0a2a4a 100%);
            color: white;
            transition: width 0.3s ease;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        /* Contenido principal */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        /* Header dentro del main */
        .main-header {
            background: white;
            padding: var(--spacing-md) var(--spacing-xl);
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* Botón de toggle */
        .sidebar-toggle {
            background: none;
            border: none;
            color: var(--gray-700);
            font-size: 1.5rem;
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }
        
        .sidebar-toggle:hover {
            background: var(--gray-200);
        }
        
        /* Logo en sidebar */
        .sidebar-logo {
            padding: var(--spacing-xl) var(--spacing-lg);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: var(--spacing-lg);
        }
        
        .sidebar-logo h2 {
            color: white;
            margin: 0;
            font-size: 1.2rem;
            white-space: nowrap;
        }
        
        .sidebar-logo .logo-icon {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-sm);
        }
        
        /* Menú de navegación */
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            margin: var(--spacing-xs) 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            white-space: nowrap;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--warning);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: var(--success);
        }
        
        .nav-icon {
            font-size: 1.5rem;
            min-width: 40px;
            text-align: center;
        }
        
        .nav-text {
            margin-left: var(--spacing-sm);
            opacity: 1;
            transition: opacity 0.2s ease;
        }
        
        .sidebar.collapsed .nav-text {
            opacity: 0;
            width: 0;
            display: none;
        }
        
        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: var(--spacing-md) 0;
        }
        
        .sidebar.collapsed .nav-icon {
            min-width: auto;
        }
        
        .sidebar.collapsed .sidebar-logo h2 {
            display: none;
        }
        
        /* Info de usuario en sidebar */
        .user-info-sidebar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: var(--spacing-lg);
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-info-sidebar .user-name {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: white;
            white-space: nowrap;
        }
        
        .user-info-sidebar .user-avatar {
            font-size: 2rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .sidebar-toggle-mobile {
                display: block !important;
            }
        }
        
        .sidebar-toggle-mobile {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-700);
        }
        
        /* Scrollbar personalizada para sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
    </style>
</head>
<body class="<?php echo (strpos($_SERVER['PHP_SELF'], '/modules/almacen/') !== false) ? 'modulo-almacen' : ''; ?>">
    <div class="app-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">🏥</div>
                <h2><?= SISTEMA_NOMBRE ?></h2>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Menú principal -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                            <span class="nav-icon">📊</span>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    
                    <li class="nav-item" style="margin-top: var(--spacing-lg);">
                        <div style="padding: 0 var(--spacing-lg); color: rgba(255,255,255,0.5); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">
                            <span class="nav-text">Módulos</span>
                        </div>
                    </li>
                    
                    <?php if (!empty($menu_modulos)): ?>
                        <?php foreach ($menu_modulos as $modulo): ?>
                            <li class="nav-item">
                                <a href="<?= BASE_URL ?>modules/<?= $modulo['ruta'] ?>/index.php" 
                                   class="nav-link <?= strpos($_SERVER['PHP_SELF'], $modulo['ruta']) !== false ? 'active' : '' ?>">
                                    <span class="nav-icon"><?= $modulo['icono'] ?></span>
                                    <span class="nav-text"><?= $modulo['nombre'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <div style="padding: var(--spacing-md) var(--spacing-lg); color: rgba(255,255,255,0.5);">
                                No hay módulos asignados
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Info de usuario en sidebar -->
                <div class="user-info-sidebar">
                    <div class="user-name">
                        <span class="user-avatar">👤</span>
                        <span class="nav-text">
                            <strong><?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario') ?></strong>
                            <br>
                            <small style="font-size: 0.7rem; opacity: 0.7;"><?= $_SESSION['rol'] ?? '' ?></small>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <!-- Usuario no autenticado -->
                <div style="padding: var(--spacing-xl); text-align: center; color: white;">
                    <p>No has iniciado sesión</p>
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-sm btn-primary">Iniciar Sesión</a>
                </div>
            <?php endif; ?>
        </aside>
        
        <!-- Contenido principal -->
        <main class="main-content" id="mainContent">
            <!-- Header del main -->
            <header class="main-header">
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <button class="sidebar-toggle" id="sidebarToggle" title="Toggle sidebar">
                        ☰
                    </button>
                    <button class="sidebar-toggle-mobile" id="sidebarToggleMobile" title="Abrir menú">
                        ☰
                    </button>
                    <h2 style="margin: 0; font-size: 1.2rem;">
                        <?php
                        // Título dinámico según la página
                        $pagina = basename($_SERVER['PHP_SELF']);
                        $modulo = basename(dirname($_SERVER['PHP_SELF']));
                        
                        if ($pagina == 'dashboard.php') {
                            echo 'Panel Principal';
                        } elseif ($modulo == 'admin') {
                            echo 'Administración';
                        } elseif ($modulo == 'almacen') {
                            echo 'Módulo de Almacén';
                        } elseif ($modulo == 'farmacia') {
                            echo 'Módulo de Farmacia';
                        } else {
                            echo ucfirst(str_replace('.php', '', $pagina));
                        }
                        ?>
                    </h2>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                        <span style="display: flex; align-items: center; gap: var(--spacing-xs);">
                            <span>📅</span>
                            <span id="fechaActual"><?= date('d/m/Y') ?></span>
                            <span>⏰</span>
                            <span id="horaActual"></span>
                        </span>
                        <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm btn-danger" onclick="return confirm('¿Cerrar sesión?')">
                            🚪 Salir
                        </a>
                    </div>
                <?php endif; ?>
            </header>
            
            <!-- Contenedor para el contenido específico de cada página -->
            <div style="flex: 1; padding: var(--spacing-xl);">