<?php

session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

// Obtener el identificador de la función
$id_funcion = filter_input(INPUT_GET, "id_funcion", FILTER_VALIDATE_INT);

if (!$id_funcion) {
    die("Función no válida.");
}

// Consultar información de la función
$sql = "SELECT
            f.id_funcion,
            f.fecha_hora,
            f.precio,
            p.nomb_p,
            s.id_sala,
            s.nomb_sala
        FROM funciones f
        INNER JOIN peliculas p
            ON f.id_peliculas = p.id_peliculas
        INNER JOIN salas s
            ON f.id_sala = s.id_sala
        WHERE f.id_funcion = :id_funcion
        AND f.fecha_hora >= CURRENT_TIMESTAMP";

$consulta = $conexion->prepare($sql);
$consulta->execute([
    ":id_funcion" => $id_funcion
]);

$funcion = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$funcion) {
    die("La función no existe o ya no está disponible.");
}

// Consultar los asientos de la sala
$sql = "SELECT *
        FROM asientos
        WHERE id_sala = :id_sala
        ORDER BY numero_asiento ASC";

$consulta = $conexion->prepare($sql);
$consulta->execute([
    ":id_sala" => $funcion["id_sala"]
]);

$asientos = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Seleccionar asiento | Cine Colombia</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #101010;
            color: white;
        }

        header {
            background: #080808;
            padding: 22px 8%;
        }

        header h1 {
            color: #ffd000;
        }

        .contenedor {
            width: 85%;
            margin: 40px auto;
        }

        h2 {
            color: #ffd000;
            margin-bottom: 15px;
        }

        .informacion {
            background: #202020;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            line-height: 1.8;
        }

        .pantalla {
            background: #ddd;
            color: #111;
            text-align: center;
            padding: 12px;
            width: 70%;
            margin: 30px auto;
            border-radius: 50% 50% 5px 5px;
            font-weight: bold;
        }

        .asientos {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 12px;
            max-width: 550px;
            margin: 30px auto;
        }

        .asiento {
            padding: 13px 5px;
            text-align: center;
            border-radius: 7px;
            font-size: 14px;
        }

        .disponible {
            background: #168b45;
        }

        .ocupado {
            background: #a52a2a;
            color: white;
        }

        .leyenda {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin: 25px 0;
        }

        .leyenda span {
            padding: 10px;
            border-radius: 5px;
        }

        .boton {
            display: block;
            width: 220px;
            margin: 30px auto;
            padding: 14px;
            background: #ffd000;
            color: black;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 7px;
        }

        .sin-asientos {
            text-align: center;
            color: #ccc;
            margin: 30px;
        }
        .asiento label {
            display: block;
            cursor: pointer;
        }
        
        .asiento input {
            display: none;
        }
        
        .asiento.seleccionado {
            background: #ffd000;
            color: black;
            outline: 2px solid white;
        }
        
        .asiento.ocupado {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .boton-comprar {
            display: block;
            width: 220px;
            margin: 30px auto;
            padding: 14px;
            background: #ffd000;
            color: black;
            border: none;
            border-radius: 7px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>

</head>

<body>

<header>
    <h1>CINE COLOMBIA</h1>
</header>

<div class="contenedor">

    <h2>Selecciona tu asiento</h2>

    <div class="informacion">

        <p>
            <strong>Película:</strong>
            <?php echo htmlspecialchars($funcion["nomb_p"]); ?>
        </p>

        <p>
            <strong>Sala:</strong>
            <?php echo htmlspecialchars($funcion["nomb_sala"]); ?>
        </p>

        <p>
            <strong>Fecha:</strong>
            <?php echo date("d/m/Y h:i A", strtotime($funcion["fecha_hora"])); ?>
        </p>

        <p>
            <strong>Precio:</strong>
            $<?php echo number_format($funcion["precio"], 0, ",", "."); ?>
        </p>

    </div>

    <div class="pantalla">
        PANTALLA
    </div>

    <div class="leyenda">

        <span class="disponible">Disponible</span>

        <span class="ocupado">Ocupado</span>

    </div>

    <?php if (count($asientos) > 0): ?>

    <form action="confirmar_compra.php" method="POST">

        <input type="hidden"
               name="id_funcion"
               value="<?php echo $funcion['id_funcion']; ?>">

        <div class="asientos">

            <?php foreach ($asientos as $asiento): ?>

                <?php if ($asiento["estado"]): ?>

                    <div class="asiento disponible">

                        <label>

                            <input
                                type="radio"
                                name="id_asiento"
                                value="<?php echo $asiento['id_asiento']; ?>"
                                required
                            >

                            <?php echo htmlspecialchars($asiento["numero_asiento"]); ?>

                        </label>

                    </div>

                <?php else: ?>

                    <div class="asiento ocupado">

                        <?php echo htmlspecialchars($asiento["numero_asiento"]); ?>

                    </div>

                <?php endif; ?>

            <?php endforeach; ?>

        </div>

        <button type="submit" class="boton-comprar">
            Continuar con la compra
        </button>

    </form>

<?php else: ?>

    <p class="sin-asientos">
        Esta sala todavía no tiene asientos registrados.
    </p>

<?php endif; ?>

    <a href="comprar.php" class="boton">
        Volver a funciones
    </a>

</div>

</body>

</html>
