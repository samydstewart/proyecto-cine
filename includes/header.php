<?php
require_once __DIR__ . '/seguridad.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_actual = basename($_SERVER['PHP_SELF']);

function activo(string $archivo, string $actual): string
{
    return $archivo === $actual ? 'activo' : '';
}

$logueado = isset($_SESSION['id_usuario']);
$es_admin = $logueado && (int) ($_SESSION['id_rol'] ?? 0) === ROL_ADMIN;

// Notificaciones pendientes del cliente, para el contador del menú
$pendientes = 0;
if ($logueado && !$es_admin && isset($conexion)) {

    
    try {
        $contador = $conexion->prepare(
            "SELECT COUNT(*) FROM notificaciones WHERE id_usuario = :u AND estado = 'pendiente'"
        );
        $contador->execute([':u' => (int) $_SESSION['id_usuario']]);
        $pendientes = (int) $contador->fetchColumn();
    } catch (PDOException $e) {
        error_log("Contador de notificaciones: " . $e->getMessage());
    }
}
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
                <a href="index.php" class="<?= activo('index.php', $pagina_actual) ?>">
                    Cartelera
                </a>
            </li>

            <?php if ($logueado && !$es_admin): ?>
                <li>
                    <a href="mis-boletos.php" class="<?= activo('mis-boletos.php', $pagina_actual) ?>">
                        Mis boletos
                    </a>
                </li>
                <li>
                    <a href="mis-notificaciones.php" class="<?= activo('mis-notificaciones.php', $pagina_actual) ?>">
                        Notificaciones
                        <?php if ($pendientes > 0): ?>
                            <span class="badge"><?= $pendientes ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($es_admin): ?>
                <li>
                    <a href="admin_usuarios.php" class="nav-admin <?= activo('admin_usuarios.php', $pagina_actual) ?>">
                        ⚙ Administrar
                    </a>
                </li>
            <?php endif; ?>
            <?php if (!$es_admin): ?>
                <li>
    <a href="mi-perfil.php" class="<?= activo('mi-perfil.php', $pagina_actual) ?>">Mi perfil</a>
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