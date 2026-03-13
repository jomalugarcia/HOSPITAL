<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../config/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: usuarios.php?error=ID no válido");
    exit;
}

$id_usuario = $_GET['id'];

// Verificar que no sea el propio usuario
if ($id_usuario == $_SESSION['user_id']) {
    header("Location: usuarios.php?error=No puedes desactivarte a ti mismo");
    exit;
}

try {
    // Cambiar el estado del usuario (0 = inactivo)
    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
    $stmt->execute([$id_usuario]);
    
    if ($stmt->rowCount() > 0) {
        header("Location: usuarios.php?success=Usuario desactivado correctamente");
    } else {
        header("Location: usuarios.php?error=El usuario no existe");
    }
    
} catch (PDOException $e) {
    header("Location: usuarios.php?error=Error al desactivar: " . $e->getMessage());
}

exit;