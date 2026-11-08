<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

// Recibir los datos del formulario
$id_funcion = filter_input(INPUT_POST, "id_funcion", FILTER_VALIDATE_INT);
$id_asiento = filter_input(INPUT_POST, "id_asiento", FILTER_VALIDATE_INT);

if (!$id_funcion || !$id_asiento) {
    die("Los datos de la compra no son válidos.");
}

try {

    $conexion->beginTransaction();

    // Consultar la función
    $sql = "SELECT id_funcion, id_sala, precio
            FROM funciones
            WHERE id_funcion = :id_funcion
            AND fecha_hora >= CURRENT_TIMESTAMP
            FOR UPDATE";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([
        ":id_funcion" => $id_funcion
    ]);

    $funcion = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$funcion) {
        throw new Exception("La función no existe o ya no está disponible.");
    }

    // Verificar que el asiento pertenezca a la sala
    $sql = "SELECT id_asiento
            FROM asientos
            WHERE id_asiento = :id_asiento
            AND id_sala = :id_sala";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([
        ":id_asiento" => $id_asiento,
        ":id_sala" => $funcion["id_sala"]
    ]);

    if (!$consulta->fetch()) {
        throw new Exception("El asiento seleccionado no pertenece a esta sala.");
    }

    // Consultar el tipo de venta
    $sql = "SELECT id_tipo_venta
            FROM tipos_venta
            WHERE nomb_tipo = 'Venta en línea'";

    $tipo_venta = $conexion->query($sql)->fetch(PDO::FETCH_ASSOC);

    // Consultar el método de pago
    $sql = "SELECT id_forma_pago
            FROM Pagos_Factura
            WHERE nomb_medio = 'Tarjeta'";

    $forma_pago = $conexion->query($sql)->fetch(PDO::FETCH_ASSOC);

    if (!$tipo_venta || !$forma_pago) {
        throw new Exception("No se encontraron los datos de venta o pago.");
    }

    // Registrar la compra
    $sql = "INSERT INTO boletos (
                id_usuario,
                id_asiento,
                id_tipo_venta,
                id_forma_pago,
                id_funcion
            )
            VALUES (
                :id_usuario,
                :id_asiento,
                :id_tipo_venta,
                :id_forma_pago,
                :id_funcion
            )";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":id_usuario" => $_SESSION["id_usuario"],
        ":id_asiento" => $id_asiento,
        ":id_tipo_venta" => $tipo_venta["id_tipo_venta"],
        ":id_forma_pago" => $forma_pago["id_forma_pago"],
        ":id_funcion" => $id_funcion
    ]);

    $conexion->commit();

    $mensaje = "¡Tu compra se ha registrado correctamente!";

} catch (PDOException $error) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    if ($error->getCode() == "23505") {
        $mensaje = "Lo sentimos, ese asiento ya fue comprado por otra persona.";
    } else {
        error_log($error->getMessage());
        $mensaje = "Ocurrió un error al registrar la compra.";
    }

} catch (Exception $error) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    $mensaje = $error->getMessage();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Confirmación de compra | Cine Colombia</title>

    <style>
        body {
            background: #101010;
            color: white;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 80px 20px;
        }

        .contenedor {
            background: #202020;
            padding: 40px;
            border-radius: 12px;
            max-width: 500px;
            margin: auto;
        }

        h1 {
            color: #ffd000;
        }

        a {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 25px;
            background: #ffd000;
            color: black;
            text-decoration: none;
            border-radius: 7px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="contenedor">

        <h1>CINE COLOMBIA</h1>

        <h2><?php echo htmlspecialchars($mensaje); ?></h2>

        <a href="comprar.php">Volver a funciones</a>

    </div>

</body>
</html>
