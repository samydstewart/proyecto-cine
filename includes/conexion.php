<?php
$config = require __DIR__ . '/config.php';
$db = $config['db'];

$dsn = "pgsql:host={$db['host']};port=5432;dbname={$db['nombre']};sslmode=require";

try {
    $conexion = new PDO($dsn, $db['usuario'], $db['clave'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $error) {
    error_log($error->getMessage());
    die("No se pudo conectar con la base de datos.");
}