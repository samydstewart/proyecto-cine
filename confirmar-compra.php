<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once "includes/conexion.php";

const TIPO_VENTA_WEB = 1; // Web / Online

function ir_a(string $url): void
{
    header("Location: " . $url);
    exit();
}

function volver_a_pago(int $id_funcion, int $id_asiento, string $mensaje): void
{
    ir_a("pago.php?" . http_build_query([
        "id_funcion" => $id_funcion,
        "id_asiento" => $id_asiento,
        "error"      => $mensaje,
    ]));
}

function volver_a_seleccion(int $id_funcion, string $mensaje): void
{
    ir_a("seleccionar-asientos.php?" . http_build_query([
        "id_funcion" => $id_funcion,
        "error"      => $mensaje,
    ]));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    ir_a("comprar.php");
}

$id_funcion    = filter_input(INPUT_POST, "id_funcion", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
$id_asiento    = filter_input(INPUT_POST, "id_asiento", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
$id_forma_pago = filter_input(INPUT_POST, "id_forma_pago", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if (!$id_funcion) {
    ir_a("comprar.php");
}
if (!$id_asiento) {
    volver_a_seleccion($id_funcion, "Debes seleccionar un asiento.");
}
if (!$id_forma_pago) {
    volver_a_pago($id_funcion, $id_asiento, "Selecciona un medio de pago.");
}

// 1. Validar el medio de pago (consulta fuera de la transacción)
$medio = null;
try {
    $consulta = $conexion->prepare("SELECT nomb_medio FROM pagos_factura WHERE id_forma_pago = :forma");
    $consulta->execute([":forma" => $id_forma_pago]);
    $medio = $consulta->fetch();
} catch (PDOException $e) {
    error_log("Compra: error al leer medio de pago: " . $e->getMessage());
    volver_a_pago($id_funcion, $id_asiento, "No se pudo validar el medio de pago. Intenta de nuevo.");
}

if (!$medio) {
    volver_a_pago($id_funcion, $id_asiento, "El medio de pago no es válido.");
}

// 2. Si es tarjeta, validar titular, vencimiento y CVV (no se guardan)
if (str_contains($medio["nomb_medio"], "Tarjeta")) {

    $titular = trim($_POST["titular"] ?? "");
    $vence   = trim($_POST["vencimiento"] ?? "");
    $cvv     = trim($_POST["cvv"] ?? "");

    if ($titular === "" || mb_strlen($titular) > 60) {
        volver_a_pago($id_funcion, $id_asiento, "Escribe el nombre del titular (máximo 60 caracteres).");
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $vence, $partes)) {
        volver_a_pago($id_funcion, $id_asiento, "El vencimiento debe tener el formato MM/AA.");
    }

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

// 3. Registrar la compra en una transacción
// $paso se inicializa aquí para que el catch siempre pueda usarla
$paso = "iniciar transaccion";

try {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    $conexion->beginTransaction();

    $paso = "bloquear funcion";
    $consulta = $conexion->prepare(
        "SELECT id_sala FROM funciones
         WHERE id_funcion = :funcion
           AND fecha_hora >= CURRENT_TIMESTAMP
         FOR UPDATE"
    );
    $consulta->execute([":funcion" => $id_funcion]);
    $funcion = $consulta->fetch();

    if (!$funcion) {
        throw new RuntimeException("La función ya no está disponible.");
    }

    $paso = "insertar boleto";
    $consulta = $conexion->prepare(
        "INSERT INTO boletos (id_usuario, id_asiento, id_tipo_venta, id_forma_pago, id_funcion)
         VALUES (:usuario, :asiento, :tipo_venta, :forma_pago, :funcion)"
    );
    $consulta->execute([
        ":usuario"    => (int) $_SESSION["id_usuario"],
        ":asiento"    => $id_asiento,
        ":tipo_venta" => TIPO_VENTA_WEB,
        ":forma_pago" => $id_forma_pago,
        ":funcion"    => $id_funcion,
    ]);

    $paso = "confirmar";
    $conexion->commit();

    ir_a("mis-boletos.php?compra=ok");

} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    // Registrar el error ORIGINAL en el log, con el paso donde ocurrió
    error_log("Compra fallida en paso [$paso] SQLSTATE " . $e->getCode() . ": " . $e->getMessage());

    if ($e->getCode() === "23505") {
        volver_a_seleccion($id_funcion, "Ese asiento ya fue comprado por otra persona.");
    }

    if ($e->getCode() === "45000") {
        // Mensaje de un trigger: se muestra tal cual
        $texto = trim((string) preg_replace('/^.*?ERROR:\s+/s', '', $e->getMessage()));
        $texto = trim((string) preg_replace('/\R.*$/s', '', $texto));
        volver_a_seleccion($id_funcion, $texto !== "" ? $texto : "La compra no es válida.");
    }

    volver_a_seleccion($id_funcion, "No se pudo registrar la compra en el paso [$paso]. Revisa el log.");

} catch (RuntimeException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    volver_a_seleccion($id_funcion, $e->getMessage());
}