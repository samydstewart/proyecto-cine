<?php
session_start();
require_once "includes/conexion.php";
require_once "includes/storage.php";

// 1. Leer y validar el id de la película desde la URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    die("Película no válida.");
}

// 2. Datos de la película y su género
$sql = "SELECT p.id_peliculas, p.nomb_p, p.año_p, p.duracion_m, p.poster_url,
               p.calificacion_rt, p.sinopsis, g.nomb_genero
        FROM peliculas p
        JOIN generos g ON p.id_genero = g.id_genero
        WHERE p.id_peliculas = :id";

$consulta = $conexion->prepare($sql);
$consulta->execute([":id" => $id]);
$pelicula = $consulta->fetch();

if (!$pelicula) {
    http_response_code(404);
    die("La película no existe.");
}

// 3. Directores de la película
$sql = "SELECT d.nomb1_d, d.apel_d
        FROM dirige di
        JOIN directores d ON d.id_director = di.id_director
        WHERE di.id_peliculas = :id";

$consulta = $conexion->prepare($sql);
$consulta->execute([":id" => $id]);
$directores = $consulta->fetchAll();

// 4. Actores de la película
$sql = "SELECT a.nomb1_a, a.apel_a
        FROM actua ac
        JOIN actores a ON a.id_actor = ac.id_actor
        WHERE ac.id_peliculas = :id
        ORDER BY a.apel_a";

$consulta = $conexion->prepare($sql);
$consulta->execute([":id" => $id]);
$actores = $consulta->fetchAll();

// 5. Funciones futuras de esta película
$sql = "SELECT f.id_funcion, f.fecha_hora, f.precio, s.nomb_sala, s.tipo_sala
        FROM funciones f
        JOIN salas s ON s.id_sala = f.id_sala
        WHERE f.id_peliculas = :id
          AND f.fecha_hora >= CURRENT_TIMESTAMP
        ORDER BY f.fecha_hora ASC";

$consulta = $conexion->prepare($sql);
$consulta->execute([":id" => $id]);
$funciones = $consulta->fetchAll();

// 6. Agrupar las funciones por día: ["2026-06-15" => [funcion, funcion], ...]
$funciones_por_dia = [];
foreach ($funciones as $f) {
    $dia = date("Y-m-d", strtotime($f["fecha_hora"]));
    $funciones_por_dia[$dia][] = $f;
}

$titulo = $pelicula["nomb_p"] . " | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <div class="detalle-pelicula">

        <figure class="detalle-poster">
            <img src="<?= htmlspecialchars(url_poster($pelicula["poster_url"])) ?>"
                 alt="Póster de <?= htmlspecialchars($pelicula["nomb_p"]) ?>">
        </figure>

        <div class="detalle-info">
            <h1 class="detalle-titulo"><?= htmlspecialchars($pelicula["nomb_p"]) ?></h1>

            <p class="detalle-meta">
                <?= htmlspecialchars($pelicula["año_p"]) ?> ·
                <?= htmlspecialchars($pelicula["nomb_genero"]) ?> ·
                <?= htmlspecialchars($pelicula["duracion_m"]) ?> min ·
                ⭐ <?= htmlspecialchars($pelicula["calificacion_rt"]) ?>
            </p>

            <p class="detalle-sinopsis">
                <?= htmlspecialchars($pelicula["sinopsis"] ?? "Sinopsis no disponible.") ?>
            </p>

            <p>
                <strong>Director:</strong>
                <?php if (count($directores) > 0): ?>
                    <?= implode(", ", array_map(fn($d) => htmlspecialchars($d["nomb1_d"] . " " . $d["apel_d"]), $directores)) ?>
                <?php else: ?>
                    No registrado
                <?php endif; ?>
            </p>

            <p>
                <strong>Reparto:</strong>
                <?php if (count($actores) > 0): ?>
                    <?= implode(", ", array_map(fn($a) => htmlspecialchars($a["nomb1_a"] . " " . $a["apel_a"]), $actores)) ?>
                <?php else: ?>
                    No registrado
                <?php endif; ?>
            </p>
        </div>

    </div>

    <section class="funciones-seccion">

        <h2 class="titulo-seccion">Funciones disponibles</h2>

        <?php if (count($funciones_por_dia) === 0): ?>
            <p class="alerta">No hay funciones programadas para esta película.</p>
        <?php endif; ?>

        <?php foreach ($funciones_por_dia as $dia => $lista): ?>

            <div class="dia-funciones">
                <h3 class="dia-titulo">
                    <?= date("l d/m/Y", strtotime($dia)) ?>
                </h3>

                <div class="horarios">
                    <?php foreach ($lista as $f): ?>
                        <a class="horario"
                           href="seleccionar-asientos.php?id_funcion=<?= $f['id_funcion'] ?>">
                            <span class="horario-hora"><?= date("h:i A", strtotime($f["fecha_hora"])) ?></span>
                            <span class="horario-sala"><?= htmlspecialchars($f["nomb_sala"]) ?></span>
                            <span class="horario-precio">$<?= number_format($f["precio"], 0, ",", ".") ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php endforeach; ?>

    </section>

</main>

<?php require 'includes/footer.php'; ?>