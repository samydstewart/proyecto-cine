<?php
$admin_links = [
    'admin_usuarios.php'  => 'Usuarios',
    'admin_peliculas.php' => 'Películas',
    'admin_funciones.php' => 'Funciones',
    'admin_asientos.php'  => 'Asientos',
    'admin_personas.php' => 'Personas'
];
?>
<nav class="admin-nav">
    <?php foreach ($admin_links as $archivo => $nombre): ?>
        <a href="<?= $archivo ?>"
           class="btn-secundario <?= activo($archivo, basename($_SERVER['PHP_SELF'])) ?>">
            <?= $nombre ?>
        </a>
    <?php endforeach; ?>
</nav>