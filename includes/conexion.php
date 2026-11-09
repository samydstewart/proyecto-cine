<?php
require_once __DIR__ . '/errores.php';

$ruta_config = __DIR__ . '/config.php';

if (!is_file($ruta_config)) {
    error_log("Falta el archivo includes/config.php");
    mostrar_error_servidor("El sitio no está configurado. Contacta al administrador.", 500);
}

$config = require $ruta_config;
$db = $config['db'] ?? null;

if (!$db || empty($db['host']) || empty($db['nombre']) || empty($db['usuario'])) {
    error_log("config.php no tiene la sección 'db' completa");
    mostrar_error_servidor("El sitio no está configurado. Contacta al administrador.", 500);
}

try {
    $conexion = new PDO(
        "pgsql:host={$db['host']};port=5432;dbname={$db['nombre']};sslmode=require",
        $db['usuario'],
        $db['clave'] ?? '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]
    );
} catch (PDOException $e) {
    error_log("No se pudo conectar a la base: " . $e->getMessage());
    mostrar_error_servidor("No hay conexión con la base de datos. Intenta más tarde.", 503);
}