<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: usuarios.php?error=ID no válido");
    exit;
}

$id_usuario = $_GET['id'];

// Verificar que no sea el propio usuario
if ($id_usuario == $_SESSION['user_id']) {
    header("Location: usuarios.php?error=No puedes eliminarte a ti mismo");
    exit;
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Primero eliminar los permisos del usuario (por la FK)
    $stmt = $pdo->prepare("DELETE FROM usuario_modulos WHERE usuario_id = ?");
    $stmt->execute([$id_usuario]);
    
    // Luego eliminar el usuario
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario]);
    
    // Verificar si se eliminó algún registro
    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        header("Location: usuarios.php?success=Usuario eliminado correctamente");
    } else {
        $pdo->rollBack();
        header("Location: usuarios.php?error=El usuario no existe");
    }
    
} catch (PDOException $e) {
    $pdo->rollBack();
    // Si es error de llave foránea
    if ($e->errorInfo[1] == 1451) {
        header("Location: usuarios.php?error=No se puede eliminar: el usuario tiene registros asociados");
    } else {
        header("Location: usuarios.php?error=Error al eliminar: " . $e->getMessage());
    }
}

exit;