<?php

session_start();

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

// Consultar las funciones disponibles
$sql = "SELECT
            f.id_funcion,
            f.fecha_hora,
            f.precio,
            p.nomb_p,
            p.duracion_M,
            s.nomb_sala
        FROM funciones f
        INNER JOIN peliculas p
            ON f.id_peliculas = p.id_peliculas
        INNER JOIN salas s
            ON f.id_sala = s.id_sala
        WHERE f.fecha_hora >= CURRENT_TIMESTAMP
        ORDER BY f.fecha_hora ASC";

$consulta = $conexion->prepare($sql);
$consulta->execute();

$funciones = $consulta->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Comprar tiquetes | Cine Colombia</title>

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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            color: #ffd000;
        }

        header a {
            color: white;
            text-decoration: none;
        }

        .contenedor {
            width: 85%;
            margin: 50px auto;
        }

        h2 {
            color: #ffd000;
            margin-bottom: 15px;
        }

        .descripcion {
            color: #ccc;
            margin-bottom: 30px;
        }

        .peliculas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .tarjeta {
            background: #202020;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #333;
        }

        .tarjeta h3 {
            color: #ffd000;
            margin-bottom: 15px;
        }

        .tarjeta p {
            margin-bottom: 12px;
            color: #ddd;
        }

        .precio {
            font-size: 20px;
            font-weight: bold;
            color: #ffd000;
        }

        .boton {
            display: block;
            background: #ffd000;
            color: black;
            text-align: center;
            padding: 12px;
            margin-top: 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .boton:hover {
            background: #e6bd00;
        }

        .mensaje {
            background: #202020;
            padding: 25px;
            border-radius: 10px;
            color: #ccc;
        }
        .cerrar-sesion {
            color: white;
            text-decoration: none;
            float: right;
            background: #a52a2a;
            padding: 10px 15px;
            border-radius: 5px;
        }
    </style>

</head>

<body>

    <header>
        <a href="logout.php" class="cerrar-sesion">Cerrar sesión</a>

        <h1>CINE COLOMBIA</h1>

        <a href="index.php">Volver al inicio</a>

    </header>

    <div class="contenedor">

        <h2>Compra tus tiquetes</h2>

        <p class="descripcion">
            Selecciona la película y la función que deseas disfrutar.
        </p>

        <div class="peliculas">

            <?php if (count($funciones) > 0): ?>

                <?php foreach ($funciones as $funcion): ?>

                    <div class="tarjeta">

                        <h3>
                            <?php echo htmlspecialchars($funcion["nomb_p"]); ?>
                        </h3>

                        <p>
                            <strong>Fecha y hora:</strong>
                            <?php echo date("d/m/Y h:i A", strtotime($funcion["fecha_hora"])); ?>
                        </p>

                        <p>
                            <strong>Sala:</strong>
                            <?php echo htmlspecialchars($funcion["nomb_sala"]); ?>
                        </p>

                        <p>
                            <strong>Duración:</strong>
                            <?php echo htmlspecialchars($funcion["duracion_m"]); ?> minutos
                        </p>

                        <p class="precio">
                            $<?php echo number_format($funcion["precio"], 0, ",", "."); ?>
                        </p>

                        <a class="boton"
                           href="seleccionar_asiento.php?id_funcion=<?php echo $funcion["id_funcion"]; ?>">
                            Seleccionar función
                        </a>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="mensaje">
                    <h3>No hay funciones disponibles.</h3>

                    <p>
                        Actualmente no existen funciones programadas.
                        Vuelve a consultar más adelante.
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>
