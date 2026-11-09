<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;

// Imagen de respaldo para pósters (rectángulo con texto)
const POSTER_RESPALDO = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="220" height="320"><rect width="100%" height="100%" fill="%232b2b2b"/><text x="50%" y="50%" fill="%23aaaaaa" font-family="Arial" font-size="16" text-anchor="middle">Sin póster</text></svg>';

// Imagen de respaldo para fotos de personas (círculo gris)
const FOTO_RESPALDO = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120"><rect width="100%" height="100%" fill="%232b2b2b"/></svg>';

function config_s3(): array
{
    static $cfg = null;

    if ($cfg === null) {
        $ruta = __DIR__ . '/config.php';

        if (!is_file($ruta)) {
            throw new RuntimeException("Falta includes/config.php");
        }

        $todo = require $ruta;

        if (empty($todo['s3'])) {
            throw new RuntimeException("Falta la sección 's3' en config.php");
        }

        foreach (['region', 'endpoint', 'llave', 'secreto', 'bucket'] as $campo) {
            if (empty($todo['s3'][$campo])) {
                throw new RuntimeException("Falta s3.$campo en config.php");
            }
        }

        $cfg = $todo['s3'];
    }

    return $cfg;
}

function cliente_s3(): S3Client
{
    static $s3 = null;

    if ($s3 === null) {
        $cfg = config_s3();
        $s3 = new S3Client([
            'version'                 => 'latest',
            'region'                  => $cfg['region'],
            'endpoint'                => $cfg['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials'             => [
                'key'    => $cfg['llave'],
                'secret' => $cfg['secreto'],
            ],
        ]);
    }

    return $s3;
}

// Genera una URL firmada para cualquier archivo del bucket.
// Acepta una clave ("actores/cillian.jpg") o una URL completa guardada antes.
function url_imagen(?string $clave, string $respaldo): string
{
    if (!$clave) {
        return $respaldo;
    }

    if (str_starts_with($clave, 'http')) {
        $clave = ltrim((string) parse_url($clave, PHP_URL_PATH), '/');
    }

    try {
        $cfg = config_s3();
        $comando = cliente_s3()->getCommand('GetObject', [
            'Bucket' => $cfg['bucket'],
            'Key'    => $clave,
        ]);

        $peticion = cliente_s3()->createPresignedRequest($comando, '+1 hour');
        return (string) $peticion->getUri();
    } catch (Throwable $e) {
        error_log("No se pudo generar la URL de '$clave': " . $e->getMessage());
        return $respaldo;
    }
}

// Atajo para los pósters de películas
function url_poster(?string $clave): string
{
    return url_imagen($clave, POSTER_RESPALDO);
}