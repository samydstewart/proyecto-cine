<?php
// Muestra una página simple de error y termina la ejecución
function mostrar_error_servidor(string $mensaje, int $codigo = 500): void
{
    http_response_code($codigo);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Error | Cine Colombia</title>'
       . '<link rel="stylesheet" href="includes/style.css"></head>'
       . '<body><main class="pagina pagina-centrada"><div class="tarjeta-mensaje">'
       . '<h2>' . htmlspecialchars($mensaje) . '</h2>'
       . '<a href="index.php" class="btn-primario">Volver al inicio</a>'
       . '</div></main></body></html>';
    exit();
}

// Convierte un error de PDO en un mensaje seguro para el usuario.
// Los errores de triggers (45000) muestran su texto; el resto se guarda en el log.
function mensaje_db(PDOException $e, string $generico = "Ocurrió un error en la base de datos. Intenta más tarde."): string
{
    if ($e->getCode() === '45000'
        && preg_match('/ERROR:\s+(.+?)(?:\r?\n|$)/s', $e->getMessage(), $partes)) {
        return trim($partes[1]);
    }

    error_log("[SQLSTATE " . $e->getCode() . "] " . $e->getMessage());
    return $generico;
}

// Cualquier excepción que no se capture llega aquí
set_exception_handler(function (Throwable $e): void {
    error_log("Excepción no controlada: " . $e->getMessage()
        . " en " . $e->getFile() . ":" . $e->getLine());
    mostrar_error_servidor("Ocurrió un problema inesperado. Intenta más tarde.");
});

// Los avisos de PHP se guardan en el log y no se muestran al usuario
set_error_handler(function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    if (!(error_reporting() & $nivel)) {
        return false;
    }
    error_log("PHP [$nivel] $mensaje en $archivo:$linea");
    return true;
});