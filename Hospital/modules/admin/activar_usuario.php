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

try {
    // Cambiar el estado del usuario (1 = activo)
    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
    $stmt->execute([$id_usuario]);
    
    if ($stmt->rowCount() > 0) {
        header("Location: usuarios.php?success=Usuario activado correctamente");
    } else {
        header("Location: usuarios.php?error=El usuario no existe");
    }
    
} catch (PDOException $e) {
    header("Location: usuarios.php?error=Error al activar: " . $e->getMessage());
}

exit;