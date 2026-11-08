<?php

$servidor = "localhost";
$puerto = "5432";
$base_datos = "NOMBRE_DE_LA_BASE";
$usuario = "cine_app";
$contrasena = "TU_CONTRASENA";

try {

    $conexion = new PDO(
        "pgsql:host=$servidor;port=$puerto;dbname=$base_datos",
        $usuario,
        $contrasena
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $error) {

    error_log("Error de conexión: " . $error->getMessage());

    die("No fue posible conectar con la base de datos.");

}

?>
