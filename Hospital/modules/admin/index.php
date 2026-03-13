<?php
require_once '../../config/config.php';
$modulo_requerido = 'admin';
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
?>

<h2>Módulo Administración</h2>

<div class="grid-modulos">

    <a href="usuarios.php" class="card-modulo">
        👤 Usuarios
    </a>

    <a href="doctores.php" class="card-modulo">
        🩺 Doctores
    </a>

    <a href="horarios.php" class="card-modulo">
        🕒 Horarios
    </a>

    <a href="../almacen/index.php" class="card-modulo">
    🏢 Almacén
    </a>

</div>

<?php require_once '../../includes/footer.php'; ?>
