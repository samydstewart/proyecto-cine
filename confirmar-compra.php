<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once "includes/conexion.php";

const TIPO_VENTA_WEB = 1; // Web / Online

function volver_a_pago(int $id_funcion, int $id_asiento, string $mensaje): void
{
    header("Location: pago.php?" . http_build_query([
        "id_funcion" => $id_funcion,
        "id_asiento" => $id_asiento,
        "error"      => $mensaje,
    ]));
    exit();
}

function volver_a_seleccion(int $id_funcion, string $mensaje): void
{
    header("Location: seleccionar-asientos.php?" . http_build_query([
        "id_funcion" => $id_funcion,
        "error"      => $mensaje,
    ]));
    exit();
}


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: comprar.php");
    exit();
}

$id_funcion    = filter_input(INPUT_POST, "id_funcion", FILTER_VALIDATE_INT);
$id_asiento    = filter_input(INPUT_POST, "id_asiento", FILTER_VALIDATE_INT);
$id_forma_pago = filter_input(INPUT_POST, "id_forma_pago", FILTER_VALIDATE_INT);

if (!$id_funcion || !$id_asiento) {
    header("Location: comprar.php");
    exit();
}

if (!$id_forma_pago) {
    volver_a_pago($id_funcion, $id_asiento, "Selecciona un medio de pago.");
}

// 1. El medio de pago debe existir en la base
$consulta = $conexion->prepare(
    "SELECT nomb_medio FROM pagos_factura WHERE id_forma_pago = :forma"
);
$consulta->execute([":forma" => $id_forma_pago]);
$medio = $consulta->fetch();

if (!$medio) {
    volver_a_pago($id_funcion, $id_asiento, "El medio de pago no es válido.");
}

// 2. Si es tarjeta, valida los datos. Nunca se guardan ni se registran.
if (str_contains($medio["nomb_medio"], "Tarjeta")) {

    $titular = trim($_POST["titular"] ?? "");
    $vence   = trim($_POST["vencimiento"] ?? "");
    $cvv     = trim($_POST["cvv"] ?? "");

    if ($titular === "") {
        volver_a_pago($id_funcion, $id_asiento, "Escribe el nombre del titular.");
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $vence, $partes)) {
        volver_a_pago($id_funcion, $id_asiento, "El vencimiento debe tener el formato MM/AA.");
    }

    // La tarjeta vence al final de su mes
    $anio = 2000 + (int) $partes[2];
    $mes  = (int) $partes[1];
    $vencimiento = (new DateTimeImmutable(sprintf("%04d-%02d-01", $anio, $mes)))->modify("+1 month");

    if ($vencimiento <= new DateTimeImmutable()) {
        volver_a_pago($id_funcion, $id_asiento, "La tarjeta está vencida.");
    }

    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        volver_a_pago($id_funcion, $id_asiento, "El CVV debe tener 3 o 4 dígitos.");
    }
}

// 3. Registrar la compra en una sola transacción
try {
    $conexion->beginTransaction();

    $consulta = $conexion->prepare(
        "SELECT id_sala
         FROM funciones
         WHERE id_funcion = :funcion
           AND fecha_hora >= CURRENT_TIMESTAMP
         FOR UPDATE"
    );
    $consulta->execute([":funcion" => $id_funcion]);
    $funcion = $consulta->fetch();

    if (!$funcion) {
        throw new RuntimeException("La función ya no está disponible.");
    }

    $consulta = $conexion->prepare(
        "SELECT 1
         FROM asientos
         WHERE id_asiento = :asiento
           AND id_sala = :sala
           AND estado = TRUE"
    );
    $consulta->execute([
        ":asiento" => $id_asiento,
        ":sala"    => $funcion["id_sala"],
    ]);

    if (!$consulta->fetch()) {
        throw new RuntimeException("Ese asiento no es válido para esta sala.");
    }

    $consulta = $conexion->prepare(
        "INSERT INTO boletos (id_usuario, id_asiento, id_tipo_venta, id_forma_pago, id_funcion)
         VALUES (:usuario, :asiento, :tipo_venta, :forma_pago, :funcion)"
    );
    $consulta->execute([
        ":usuario"    => $_SESSION["id_usuario"],
        ":asiento"    => $id_asiento,
        ":tipo_venta" => TIPO_VENTA_WEB,
        ":forma_pago" => $id_forma_pago,
        ":funcion"    => $id_funcion,
    ]);

    $conexion->commit();

    header("Location: comprar.php?compra=ok");
    exit();

} catch (PDOException $error) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    // Índice único boletos_asiento_funcion_unico
    if ($error->getCode() === "23505") {
        volver_a_seleccion($id_funcion, "Ese asiento ya fue comprado por otra persona.");
    }

    error_log("Error al registrar compra: " . $error->getMessage());
    volver_a_seleccion($id_funcion, "No se pudo registrar la compra. Intenta de nuevo.");

} catch (RuntimeException $error) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    volver_a_seleccion($id_funcion, $error->getMessage());
}