<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_actual = basename($_SERVER['PHP_SELF']);

function activo(string $archivo, string $actual): string
{
    return $archivo === $actual ? 'activo' : '';
}

$logueado = isset($_SESSION['id_usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Cine Colombia') ?></title>
    <link rel="stylesheet" href="includes/style.css">
</head>
<body>

<header class="main-header">
    <div class="logo">
        <h1><a href="index.php">Cine Colombia</a></h1>
    </div>

    <nav>
        <ul class="nav-links">
            <li><a href="index.php" class="<?= activo('index.php', $pagina_actual) ?>">Cartelera</a></li>
            <li><a href="comprar.php" class="<?= activo('comprar.php', $pagina_actual) ?>">Comprar tiquetes</a></li>
        </ul>
    </nav>

    <div class="acciones">
        <?php if ($logueado): ?>
            <span>Hola, <?= htmlspecialchars($_SESSION['nombre']) ?></span>
            <a href="logout.php" class="btn-nav">Cerrar sesión</a>
        <?php else: ?>
            <a href="login.php" class="btn-nav">Iniciar sesión</a>
            <a href="registro.php" class="btn-nav btn-nav--primary">Registrarse</a>
        <?php endif; ?>
    </div>
</header>