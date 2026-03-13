<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: usuarios.php");
    exit;
}

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$password = $_POST['password'] ?? '';
$activo = $_POST['activo'] ?? 1;
$rol = $_POST['rol'] ?? '';
$modulos = $_POST['modulos'] ?? [];

try {
    $pdo->beginTransaction();
    
    // Actualizar datos básicos del usuario
    if (!empty($password)) {
        // Si se proporcionó nueva contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, password = ?, activo = ?, rol = ? WHERE id = ?");
        $stmt->execute([$nombre, $password_hash, $activo, $rol, $id]);
    } else {
        // Sin cambiar contraseña
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, activo = ?, rol = ? WHERE id = ?");
        $stmt->execute([$nombre, $activo, $rol, $id]);
    }
    
    // Actualizar permisos de módulos
    // Primero eliminar todos los permisos actuales
    $stmt = $pdo->prepare("DELETE FROM usuario_modulos WHERE usuario_id = ?");
    $stmt->execute([$id]);
    
    // Luego insertar los nuevos permisos
    if (!empty($modulos)) {
        $stmt = $pdo->prepare("INSERT INTO usuario_modulos (usuario_id, modulo_id) VALUES (?, ?)");
        foreach ($modulos as $modulo_id) {
            $stmt->execute([$id, $modulo_id]);
        }
    }
    
    $pdo->commit();
    
    // Si es el propio usuario el que se editó, actualizar sesión
    if ($id == $_SESSION['user_id']) {
        $_SESSION['usuario'] = $nombre;
        $_SESSION['rol'] = $rol;
    }
    
    header("Location: usuarios.php?success=Usuario actualizado correctamente");
    
} catch (PDOException $e) {
    $pdo->rollBack();
    header("Location: usuarios.php?error=Error al actualizar: " . $e->getMessage());
}

exit;