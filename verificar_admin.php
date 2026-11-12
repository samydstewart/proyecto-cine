<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/conexion.php";

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit();
}

// Consultar el rol del usuario
$sql = "SELECT nomb_rol
        FROM roles
        WHERE id_rol = :id_rol";

$consulta = $conexion->prepare($sql);

$consulta->execute([
    ":id_rol" => $_SESSION["id_rol"]
]);

$rol = $consulta->fetchColumn();

// Permitir el acceso solamente a administradores
if ($rol !== "Administrador") {
    http_response_code(403);
    exit("No tienes permisos para acceder a esta sección.");
}

?>
