<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once "includes/conexion.php";

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
            p.id_peliculas,
            s.id_sala,
            s.nomb_sala
        FROM funciones f
        INNER JOIN peliculas p ON f.id_peliculas = p.id_peliculas
        INNER JOIN salas s ON f.id_sala = s.id_sala
        WHERE f.id_funcion = :id_funcion
        AND f.fecha_hora >= CURRENT_TIMESTAMP";

$consulta = $conexion->prepare($sql);
$consulta->execute([":id_funcion" => $id_funcion]);
$funcion = $consulta->fetch();

if (!$funcion) {
    die("La función no existe o ya no está disponible.");
}

// Consultar los asientos de la sala y si están libres en esta función
$sql = "SELECT
            a.id_asiento,
            a.numero_asiento,
            CASE
                WHEN b.id_boletos IS NULL THEN TRUE
                ELSE FALSE
            END AS disponible
        FROM asientos a
        LEFT JOIN boletos b
            ON a.id_asiento = b.id_asiento
            AND b.id_funcion = :id_funcion
        WHERE a.id_sala = :id_sala AND a.estado = TRUE
        ORDER BY a.numero_asiento ASC";

$consulta = $conexion->prepare($sql);
$consulta->execute([
    ":id_funcion" => $id_funcion,
    ":id_sala" => $funcion["id_sala"]
]);
$asientos = $consulta->fetchAll();
foreach ($asientos as &$asiento) {
    $asiento["disponible"] = in_array($asiento["disponible"], [true, 't', 1, '1'], true);
}
unset($asiento);

$titulo = "Seleccionar asiento | Cine Colombia";

$aviso = $_GET["error"] ?? "";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Selecciona tu asiento</h2>
    <?php if ($aviso !== ""): ?>
    <p class="alerta"><?= htmlspecialchars($aviso) ?></p>
<?php endif; ?>

    <div class="informacion-funcion">
        <p><strong>Película:</strong> <?= htmlspecialchars($funcion["nomb_p"]) ?></p>
        <p><strong>Sala:</strong> <?= htmlspecialchars($funcion["nomb_sala"]) ?></p>
        <p><strong>Fecha:</strong> <?= date("d/m/Y h:i A", strtotime($funcion["fecha_hora"])) ?></p>
        <p><strong>Precio:</strong> $<?= number_format($funcion["precio"], 0, ",", ".") ?></p>
    </div>

    <div class="pantalla">PANTALLA</div>

    <div class="leyenda">
        <span class="leyenda-item disponible">Disponible</span>
        <span class="leyenda-item ocupado">Ocupado</span>
        <span class="leyenda-item seleccionado">Seleccionado</span>
    </div>

    <?php if (count($asientos) > 0): ?>

        <form action="pago.php" method="POST">

            <input type="hidden" name="id_funcion" value="<?= $funcion['id_funcion'] ?>">

            <div class="asientos">
                <?php foreach ($asientos as $asiento): ?>

                    <?php if ($asiento["disponible"]): ?>

                        <label class="asiento disponible">
                            <input type="radio" name="id_asiento"
                                   value="<?= $asiento['id_asiento'] ?>" required>
                            <?= htmlspecialchars($asiento["numero_asiento"]) ?>
                        </label>

                    <?php else: ?>

                        <div class="asiento ocupado">
                            <?= htmlspecialchars($asiento["numero_asiento"]) ?>
                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn-primario btn-comprar">Continuar al pago</button>

        </form>

    <?php else: ?>

        <p class="alerta">Esta sala todavía no tiene asientos registrados.</p>

    <?php endif; ?>

    <a href="pelicula.php?id=<?= $funcion['id_peliculas'] ?>" class="btn-volver">← Volver a la película</a>

</main>

<?php require 'includes/footer.php'; ?>