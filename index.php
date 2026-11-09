<?php
session_start();
require_once 'includes/conexion.php';
require_once 'includes/storage.php';

$titulo = "Cine Colombia | Tu cine favorito";

// 1. Filtros desde la URL
$search = trim($_GET['search'] ?? '');
$genre  = $_GET['genre'] ?? 'all';
$sort   = $_GET['sort'] ?? 'popular';

// 2. Géneros para el select
$generos = $conexion->query("SELECT id_genero, nomb_genero FROM generos ORDER BY nomb_genero")->fetchAll();

// 3. Condiciones con parámetros nombrados
$where  = [];
$params = [];

if ($search !== '') {
    $where[] = "p.nomb_p ILIKE :buscar";
    $params[':buscar'] = '%' . $search . '%';
}

if ($genre !== 'all' && ctype_digit($genre)) {
    $where[] = "g.id_genero = :genero";
    $params[':genero'] = (int) $genre;
}

// 4. Orden permitido
$orden = [
    'popular' => 'p.id_peliculas DESC',
    'rating'  => 'p.calificacion_rt DESC',
    'recent'  => 'p.año_p DESC',
];

$sql = "SELECT p.id_peliculas, p.nomb_p, p.año_p, p.duracion_m, p.poster_url,
               p.calificacion_rt, p.sinopsis, g.nomb_genero
        FROM peliculas p
        JOIN generos g ON p.id_genero = g.id_genero";

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY " . ($orden[$sort] ?? $orden['popular']);

// 5. Ejecutar
$consulta = $conexion->prepare($sql);
$consulta->execute($params);
$peliculas = $consulta->fetchAll();

require 'includes/header.php';
?>

<!-- PORTADA -->
<section class="portada">
    <div class="portada-contenido">
        <h1>Vive la magia del <span>cine</span></h1>
        <p>
            Disfruta de las mejores películas, consulta nuestra
            cartelera y compra tus tiquetes de manera rápida y sencilla.
        </p>
        <a href="#catalogo" class="boton-portada">Explorar películas</a>
    </div>
</section>

<!-- CATÁLOGO -->
<main class="container" id="catalogo">

    <aside class="filters-sidebar">
        <h2>Filtrar</h2>

        <form class="filter-form" method="get" action="index.php">

            <div class="filter-group">
                <label for="search">Buscar:</label>
                <input type="text" id="search" name="search"
                    placeholder="Título de película"
                    value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="filter-group">
                <label for="genre">Género:</label>
                <select id="genre" name="genre">
                    <option value="all">Todos los géneros</option>
                    <?php foreach ($generos as $gen): ?>
                        <option value="<?= $gen['id_genero'] ?>"
                            <?= ((string) $gen['id_genero'] === $genre) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($gen['nomb_genero']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="sort">Ordenar por:</label>
                <select id="sort" name="sort">
                    <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Más populares</option>
                    <option value="rating" <?= $sort === 'rating'  ? 'selected' : '' ?>>Mejor valoradas</option>
                    <option value="recent" <?= $sort === 'recent'  ? 'selected' : '' ?>>Más recientes</option>
                </select>
            </div>

            <button type="submit" class="btn-filter">Aplicar filtros</button>
        </form>
    </aside>

    <div>
        <?php if (count($peliculas) === 0): ?>
            <p class="alerta">No se encontraron películas con esos filtros.</p>
        <?php endif; ?>

        <section class="movie-grid">
            <?php foreach ($peliculas as $p): ?>
                <article class="movie-card">
                    <figure class="movie-poster">
                        <img src="<?= htmlspecialchars(url_poster($p['poster_url'])) ?>"
                            alt="Póster de <?= htmlspecialchars($p['nomb_p']) ?>">
                        <figcaption class="movie-year"><?= htmlspecialchars($p['año_p']) ?></figcaption>

                        <div class="movie-sinopsis">
                            <p><?= htmlspecialchars($p['sinopsis'] ?? 'Sinopsis no disponible.') ?></p>
                        </div>
                    </figure>

                    <div class="movie-info">
                        <h3>
                            <a href="pelicula.php?id=<?= $p['id_peliculas'] ?>" class="titulo-enlace">
                                <?= htmlspecialchars($p['nomb_p']) ?>
                            </a>
                        </h3>
                        <p><?= htmlspecialchars($p['nomb_genero']) ?> · <?= htmlspecialchars($p['duracion_m']) ?> min</p>
                        <div class="movie-meta">
                            <span class="rating">⭐ <?= htmlspecialchars($p['calificacion_rt']) ?></span>
                            <a href="pelicula.php?id=<?= $p['id_peliculas'] ?>" class="btn-tickets">Comprar boletos</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>

</main>

<!-- PRÓXIMOS ESTRENOS -->
<section class="estrenos" id="estrenos">
    <h2>Próximos estrenos</h2>
    <p>Prepárate para disfrutar de los mejores estrenos en nuestras salas de cine.</p>
</section>

<?php require 'includes/footer.php'; ?>