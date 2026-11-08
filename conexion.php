<?php

// Datos para conectar con PostgreSQL

$servidor = "localhost";
$puerto = "5432";
$base_datos = "cine_colombia";
$usuario = "postgres";
$contrasena = "TU_CONTRASEÑA";

// Intentamos realizar la conexión

try {

    $conexion = new PDO(
        "pgsql:host=$servidor;port=$puerto;dbname=$base_datos",
        $usuario,
        $contrasena
    );

    echo "¡Conexión exitosa con PostgreSQL!";

} catch (PDOException $error) {

    echo "Error de conexión: " . $error->getMessage();

}

?>
