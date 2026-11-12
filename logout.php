<?php
session_start();

// Vaciar los datos de la sesión
$_SESSION = [];

// Borrar la cookie del navegador
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $p["path"],
        $p["domain"],
        $p["secure"],
        $p["httponly"]
    );
}

// Destruir el archivo de sesión en el servidor
session_destroy();

header("Location: index.php");
exit();
?>