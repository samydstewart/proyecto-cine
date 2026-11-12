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
$es_admin = $logueado && isset($_SESSION['id_rol']) && (int) $_SESSION['id_rol'] === 1;
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
        <h1>
            <a href="index.php">
                CINE<span>COLOMBIA</span>
            </a>
        </h1>
        <small>Vive la magia del cine</small>
    </div>

    <nav>
        <ul class="nav-links">

            <li>
                <a href="index.php"
                   class="<?= activo('index.php', $pagina_actual) ?>">
                    Cartelera
                </a>
            </li>

            <?php if ($logueado): ?>
                <li>
                    <a href="mis-boletos.php"
                       class="<?= activo('mis-boletos.php', $pagina_actual) ?>">
                        Mis boletos
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($es_admin): ?>
                <li>
                    <a href="admin_usuarios.php"
                       class="nav-admin <?= activo('admin_usuarios.php', $pagina_actual) ?>">
                        ⚙ Administrar usuarios
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </nav>

    <div class="acciones">

        <?php if ($logueado): ?>

            <span class="saludo">
                Hola, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
            </span>

            <a href="logout.php" class="btn-nav">
                Cerrar sesión
            </a>

        <?php else: ?>

            <a href="login.php" class="btn-nav">
                Iniciar sesión
            </a>

            <a href="registro.php" class="btn-nav btn-nav--primary">
                Registrarse
            </a>

        <?php endif; ?>

    </div>

</header>
