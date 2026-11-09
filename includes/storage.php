<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;

function url_poster(?string $clave): string
{
    if (!$clave) {
        return '';
    }

    static $s3 = null;
    static $config = null;

    if ($s3 === null) {
        $config = require __DIR__ . '/config.php';
        $s3 = new S3Client([
            'version' => 'latest',
            'region' => $config['s3']['region'],
            'endpoint' => $config['s3']['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $config['s3']['llave'],
                'secret' => $config['s3']['secreto'],
            ],
        ]);
    }

    $comando = $s3->getCommand('GetObject', [
        'Bucket' => $config['s3']['bucket'],
        'Key' => $clave,
    ]);

    $peticion = $s3->createPresignedRequest($comando, '+1 hour');

    return (string) $peticion->getUri();
}