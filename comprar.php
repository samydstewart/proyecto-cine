<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once "includes/conexion.php";

$sql = "SELECT
            f.id_funcion,
            f.fecha_hora,
            f.precio,
            p.nomb_p,
            p.duracion_m AS duracion,
            s.nomb_sala
        FROM funciones f
        INNER JOIN peliculas p ON f.id_peliculas = p.id_peliculas
        INNER JOIN salas s ON f.id_sala = s.id_sala
        WHERE f.fecha_hora >= CURRENT_TIMESTAMP
        ORDER BY f.fecha_hora ASC";

try {
    $consulta = $conexion->prepare($sql);
    $consulta->execute();
    $funciones = $consulta->fetchAll();
} catch (PDOException $error) {
    die("Error real: " . $error->getMessage());
}

$titulo = "Comprar tiquetes | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Compra tus tiquetes</h2>
    <p class="descripcion">Selecciona la película y la función que deseas disfrutar.</p>

    <div class="cuadricula-funciones">

        <?php if (count($funciones) > 0): ?>

            <?php foreach ($funciones as $funcion): ?>

                <div class="tarjeta-funcion">
                    <h3><?= htmlspecialchars($funcion["nomb_p"]) ?></h3>

                    <p>
                        <strong>Fecha y hora:</strong>
                        <?= date("d/m/Y h:i A", strtotime($funcion["fecha_hora"])) ?>
                    </p>

                    <p>
                        <strong>Sala:</strong>
                        <?= htmlspecialchars($funcion["nomb_sala"]) ?>
                    </p>

                    <p>
                        <strong>Duración:</strong>
                        <?= htmlspecialchars($funcion["duracion"]) ?> minutos
                    </p>

                    <p class="precio">
                        $<?= number_format($funcion["precio"], 0, ",", ".") ?>
                    </p>

                    <a class="btn-primario"
                       href="seleccionar-asientos.php?id_funcion=<?= $funcion["id_funcion"] ?>">
                        Seleccionar función
                    </a>
                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="tarjeta-funcion">
                <h3>No hay funciones disponibles.</h3>
                <p>Actualmente no existen funciones programadas. Vuelve a consultar más adelante.</p>
            </div>

        <?php endif; ?>

    </div>

</main>

<?php require 'includes/footer.php'; ?>