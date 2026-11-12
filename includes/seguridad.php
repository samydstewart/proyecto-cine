<?php
require_once __DIR__ . '/errores.php';

const ROL_ADMIN = 1; // id_rol del rol "Administrador" en la tabla roles

// Exige sesión iniciada
function requerir_sesion(): void
{
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: login.php');
        exit();
    }
}

// Exige sesión iniciada y rol de administrador
function requerir_admin(): void
{
    requerir_sesion();

    if ((int) ($_SESSION['id_rol'] ?? 0) !== ROL_ADMIN) {
        mostrar_error_servidor('No tienes permisos para ver esta página.', 403);
    }
}

// Token CSRF: evita que otra página envíe formularios en nombre del usuario
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

// Devuelve el valor enviado para volver a llenar un campo tras un error
function old(string $campo): string
{
    return htmlspecialchars($_POST[$campo] ?? '');
}