<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/validaciones.php';

class ValidacionesTest extends TestCase
{
    public function test_texto_limpio_quita_espacios_extra(): void
    {
        $this->assertSame("Juan Pablo", texto_limpio("  Juan    Pablo  "));
    }

    public function test_genero_valido(): void
    {
        $this->assertSame("", validar_genero("Ciencia Ficción"));
    }

    public function test_genero_con_numeros_se_rechaza(): void
    {
        $this->assertNotSame("", validar_genero("Género 2"));
    }

    public function test_genero_vacio_se_rechaza(): void
    {
        $this->assertNotSame("", validar_genero(""));
    }

    public function test_pelicula_con_anio_invalido(): void
    {
        $datos = [
            "nombre" => "Matrix", "anio" => "1700", "duracion" => "136",
            "genero" => "1", "rating" => "90", "poster" => "", "sinopsis" => "",
        ];
        $this->assertNotSame("", validar_pelicula($datos, [1]));
    }

    public function test_pelicula_valida(): void
    {
        $datos = [
            "nombre" => "Matrix", "anio" => "1999", "duracion" => "136",
            "genero" => "1", "rating" => "90", "poster" => "", "sinopsis" => "",
        ];
        $this->assertSame("", validar_pelicula($datos, [1]));
    }
}