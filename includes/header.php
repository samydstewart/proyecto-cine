<?php
$pagina_actual = basename($_SERVER['PHP_SELF']);
function activo($archivo, $pagina_actual) {
    return $archivo === $pagina_actual ? ' activo' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo) ? $titulo : 'Cine Colombia' ?></title>
    <link rel="stylesheet" href="includes/style.css">
</head>
<body>

<header class="main-header">
    <div class="logo">
        <h1>Cine Colombia</h1>
    </div>

    <nav>
        <ul class="nav-links">
            <li><a href="index.php" class="<?= trim(activo('index.php', $pagina_actual)) ?>">Inicio</a></li>
            <li><a href="cartelera.php" class="<?= trim(activo('cartelera.php', $pagina_actual)) ?>">Cartelera</a></li>
            <li><a href="gestion.php" class="<?= trim(activo('gestion.php', $pagina_actual)) ?>">Gestión</a></li>
        </ul>
    </nav>
</header>