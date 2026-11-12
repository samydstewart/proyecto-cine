<?php
// Quita espacios sobrantes y deja uno solo entre palabras
function texto_limpio(string $t): string
{
    return trim(preg_replace('/\s+/', ' ', $t));
}

// Devuelve "" si la película es válida, o el mensaje del primer error
function validar_pelicula(array $d, array $ids_genero): string
{
    $anio_max = (int) date("Y") + 2;

    if ($d["nombre"] === "" || mb_strlen($d["nombre"]) > 120) {
        return "El nombre es obligatorio y puede tener máximo 120 caracteres.";
    }

    if (!preg_match('/^[\p{L}\p{N} .:,!?&\'-]+$/u', $d["nombre"])) {
        return "El nombre contiene caracteres no permitidos.";
    }

    if (!preg_match('/^\d{4}$/', $d["anio"]) || (int) $d["anio"] < 1888 || (int) $d["anio"] > $anio_max) {
        return "El año debe tener 4 dígitos entre 1888 y $anio_max.";
    }

    if (!ctype_digit($d["duracion"]) || (int) $d["duracion"] < 1 || (int) $d["duracion"] > 400) {
        return "La duración debe ser un número entre 1 y 400 minutos.";
    }

    if (!ctype_digit($d["genero"]) || !in_array((int) $d["genero"], $ids_genero, true)) {
        return "Selecciona un género válido.";
    }

    if ($d["rating"] !== "" && (!ctype_digit($d["rating"]) || (int) $d["rating"] > 100)) {
        return "La calificación debe ser un número entre 0 y 100.";
    }

    if (mb_strlen($d["sinopsis"]) > 1000) {
        return "La sinopsis no puede tener más de 1000 caracteres.";
    }

    return "";
}

// Devuelve "" si la función es válida, o el mensaje del primer error
function validar_funcion(array $d, array $ids_pelis, array $ids_salas): string
{
    if (!ctype_digit($d["pelicula"]) || !in_array((int) $d["pelicula"], $ids_pelis, true)) {
        return "Selecciona una película válida.";
    }

    if (!ctype_digit($d["sala"]) || !in_array((int) $d["sala"], $ids_salas, true)) {
        return "Selecciona una sala válida.";
    }

    // datetime-local envía el formato 2026-11-12T18:00
    $fecha = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $d["fecha"]);
    if ($fecha === false) {
        return "La fecha y hora no tienen un formato válido.";
    }

    $ahora = new DateTimeImmutable();
    if ($fecha <= $ahora->modify("+30 minutes")) {
        return "La función debe programarse al menos 30 minutos en el futuro.";
    }
    if ($fecha > $ahora->modify("+60 days")) {
        return "Solo se pueden programar funciones con hasta 60 días de anticipación.";
    }

    if (!ctype_digit($d["precio"]) || (int) $d["precio"] < 1 || (int) $d["precio"] > 200000) {
        return "El precio debe ser un número entero entre 1 y 200.000.";
    }

    return "";
}

// Devuelve "" si el género es válido, o el mensaje del error
function validar_genero(string $nombre): string
{
    if ($nombre === "") {
        return "El nombre del género es obligatorio.";
    }
    if (mb_strlen($nombre) > 40) {
        return "El nombre del género no puede tener más de 40 caracteres.";
    }
    // Letras, espacios y guiones (ej. "Ciencia Ficción", "Sci-Fi")
    if (!preg_match('/^[\p{L}][\p{L} \-]*$/u', $nombre)) {
        return "El género solo puede tener letras, espacios y guiones.";
    }
    return "";
}