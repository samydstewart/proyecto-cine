<?php
session_start();
require_once "includes/conexion.php";
require_once "includes/seguridad.php";
require_once "includes/validaciones.php";
require_once "includes/storage.php";
requerir_admin();

const AVISOS_PELICULA = [
    "creada"      => "Película creada correctamente.",
    "actualizada" => "Película actualizada correctamente.",
    "eliminada"   => "Película eliminada.",
    "genero_ok"   => "Género creado correctamente.",
];

$error        = "";
$error_genero = "";
$aviso        = AVISOS_PELICULA[$_GET["ok"] ?? ""] ?? "";

$generos    = $conexion->query("SELECT id_genero, nomb_genero FROM generos ORDER BY nomb_genero")->fetchAll();
$ids_genero = array_map('intval', array_column($generos, 'id_genero'));

// Valores del formulario. Si hay ?editar=ID, se cargan desde la base
$f = [
    "id_peliculas" => "",
    "nomb_p"       => "",
    "anio"         => "",
    "duracion"     => "",
    "id_genero"    => "",
    "rating"       => "",
    "sinopsis"     => "",
    "poster_url"   => "",
];

$accion = $_POST["accion"] ?? "";

// ---------- POST ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!csrf_valido()) {
        if ($accion === "genero") {
            $error_genero = "La sesión del formulario expiró. Recarga la página.";
        } else {
            $error = "La sesión del formulario expiró. Recarga la página.";
        }

    } elseif ($accion === "genero") {
        $nombre_genero = texto_limpio($_POST["nombre_genero"] ?? "");
        $error_genero  = validar_genero($nombre_genero);

        if ($error_genero === "") {
            try {
                $existe = $conexion->prepare("SELECT 1 FROM generos WHERE lower(nomb_genero) = lower(:n)");
                $existe->execute([":n" => $nombre_genero]);

                if ($existe->fetch()) {
                    $error_genero = "Ese género ya existe.";
                } else {
                    $conexion->prepare("INSERT INTO generos (nomb_genero) VALUES (:n)")
                             ->execute([":n" => $nombre_genero]);
                    header("Location: admin_peliculas.php?ok=genero_ok");
                    exit();
                }
            } catch (PDOException $e) {
                $error_genero = mensaje_db($e, "No se pudo guardar el género.");
            }
        }

    } elseif ($accion === "eliminar") {
        $id = filter_input(INPUT_POST, "id_peliculas", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

        if (!$id) {
            $error = "Película no válida.";
        } else {
            try {
                $c = $conexion->prepare("SELECT COUNT(*) FROM funciones WHERE id_peliculas = :p");
                $c->execute([":p" => $id]);
                $total = (int) $c->fetchColumn();

                if ($total > 0) {
                    $error = "No se puede eliminar: tiene $total función(es) programada(s). Elimínalas primero.";
                } else {
                    // dirige y actua se borran solas (ON DELETE CASCADE)
                    $conexion->prepare("DELETE FROM peliculas WHERE id_peliculas = :p")->execute([":p" => $id]);
                    header("Location: admin_peliculas.php?ok=eliminada");
                    exit();
                }
            } catch (PDOException $e) {
                $error = mensaje_db($e, "No se pudo eliminar la película.");
            }
        }

    } elseif ($accion === "crear" || $accion === "actualizar") {

        $id = null;
        if ($accion === "actualizar") {
            $id = filter_input(INPUT_POST, "id_peliculas", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        }

        // Lo que escribió el administrador, para no perderlo si hay un error
        $f = [
            "id_peliculas" => (string) ($id ?? ""),
            "nomb_p"       => $_POST["nomb_p"] ?? "",
            "anio"         => $_POST["anio"] ?? "",
            "duracion"     => $_POST["duracion"] ?? "",
            "id_genero"    => $_POST["id_genero"] ?? "",
            "rating"       => $_POST["rating"] ?? "",
            "sinopsis"     => $_POST["sinopsis"] ?? "",
            "poster_url"   => "",
        ];

        if ($accion === "actualizar" && !$id) {
            $error = "Película no válida.";
        } else {
            $datos = [
                "nombre"   => texto_limpio($_POST["nomb_p"] ?? ""),
                "anio"     => trim($_POST["anio"] ?? ""),
                "duracion" => trim($_POST["duracion"] ?? ""),
                "genero"   => trim($_POST["id_genero"] ?? ""),
                "rating"   => trim($_POST["rating"] ?? ""),
                "poster"   => "", // el póster se sube como archivo, no como texto
                "sinopsis" => trim($_POST["sinopsis"] ?? ""),
            ];
            $error = validar_pelicula($datos, $ids_genero);
        }

        if ($error === "") {
            try {
                $dup = $conexion->prepare(
                    'SELECT 1 FROM peliculas
                     WHERE lower(nomb_p) = lower(:n) AND "año_p" = :a AND id_peliculas <> :id'
                );
                $dup->execute([
                    ":n"  => $datos["nombre"],
                    ":a"  => (int) $datos["anio"],
                    ":id" => $id ?? 0,
                ]);

                if ($dup->fetch()) {
                    $error = "Ya existe una película con ese nombre y año.";
                } else {
                    // Solo se sube imagen si se eligió un archivo nuevo.
                    // Si no, al editar se conserva el póster actual.
                    $clave_poster = null;
                    if (($_FILES["poster"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $clave_poster = subir_imagen($_FILES["poster"], "posters-pelis");
                    }

                    $parametros = [
                        ":n" => $datos["nombre"],
                        ":a" => (int) $datos["anio"],
                        ":d" => (int) $datos["duracion"],
                        ":g" => (int) $datos["genero"],
                        ":p" => $clave_poster,
                        ":r" => $datos["rating"] !== "" ? (int) $datos["rating"] : null,
                        ":s" => $datos["sinopsis"] !== "" ? $datos["sinopsis"] : null,
                    ];

                    if ($id === null) {
                        $conexion->prepare(
                            'INSERT INTO peliculas
                                (nomb_p, "año_p", duracion_m, id_genero, poster_url, calificacion_rt, sinopsis)
                             VALUES (:n, :a, :d, :g, :p, COALESCE(:r, 0), :s)'
                        )->execute($parametros);
                        header("Location: admin_peliculas.php?ok=creada");
                    } else {
                        $parametros[":id"] = $id;
                        $conexion->prepare(
                            'UPDATE peliculas
                             SET nomb_p = :n,
                                 "año_p" = :a,
                                 duracion_m = :d,
                                 id_genero = :g,
                                 poster_url = COALESCE(:p, poster_url),
                                 calificacion_rt = COALESCE(:r, calificacion_rt),
                                 sinopsis = :s
                             WHERE id_peliculas = :id'
                        )->execute($parametros);
                        header("Location: admin_peliculas.php?ok=actualizada");
                    }
                    exit();
                }
            } catch (PDOException $e) {
                $error = mensaje_db($e, "No se pudo guardar la película.");
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }
    }
}

// ---------- Cargar película para editar (GET ?editar=ID) ----------
$editar_id = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if ($editar_id && $_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        $st = $conexion->prepare(
            'SELECT id_peliculas, nomb_p, "año_p", duracion_m, id_genero,
                    calificacion_rt, sinopsis, poster_url
             FROM peliculas WHERE id_peliculas = :id'
        );
        $st->execute([":id" => $editar_id]);
        $fila = $st->fetch();

        if ($fila) {
            $f = [
                "id_peliculas" => (string) $fila["id_peliculas"],
                "nomb_p"       => $fila["nomb_p"],
                "anio"         => (string) $fila["año_p"],
                "duracion"     => (string) $fila["duracion_m"],
                "id_genero"    => (string) $fila["id_genero"],
                "rating"       => (string) $fila["calificacion_rt"],
                "sinopsis"     => $fila["sinopsis"] ?? "",
                "poster_url"   => $fila["poster_url"] ?? "",
            ];
        } else {
            $error = "La película no existe.";
        }
    } catch (PDOException $e) {
        $error = mensaje_db($e, "No se pudo cargar la película.");
    }
}

// ---------- Listado ----------
$listado = $conexion->query(
    'SELECT p.id_peliculas, p.nomb_p, p."año_p" AS anio, g.nomb_genero,
            p.calificacion_rt,
            (SELECT COUNT(*) FROM funciones f WHERE f.id_peliculas = p.id_peliculas) AS funciones
     FROM peliculas p
     JOIN generos g ON g.id_genero = p.id_genero
     ORDER BY p.id_peliculas DESC
     LIMIT 100'
)->fetchAll();

$es_edicion = $f["id_peliculas"] !== "";

$titulo = "Administrar películas | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Películas y géneros</h2>
    <?php require 'includes/admin_menu.php'; ?>

    <?php if ($aviso !== ""): ?>
        <p class="alerta alerta-exito"><?= htmlspecialchars($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="alerta"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- ===== GÉNEROS ===== -->
    <h3 class="subtitulo">Agregar género</h3>

    <?php if ($error_genero !== ""): ?>
        <p class="alerta"><?= htmlspecialchars($error_genero) ?></p>
    <?php endif; ?>

    <form method="POST" action="admin_peliculas.php" class="form-admin">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="genero">

        <label class="completo">
            Nombre del género
            <input type="text" name="nombre_genero" maxlength="40" required
                   pattern="[\p{L}][\p{L} \-]*" title="Solo letras, espacios y guiones"
                   value="<?= htmlspecialchars($_POST["nombre_genero"] ?? "") ?>">
        </label>

        <button type="submit" class="btn-secundario completo">Guardar género</button>
    </form>

    <!-- ===== PELÍCULA ===== -->
    <h3 class="subtitulo" id="formulario">
        <?= $es_edicion ? "Editar película" : "Agregar película" ?>
    </h3>

    <form method="POST" action="admin_peliculas.php" class="form-admin" enctype="multipart/form-data">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="<?= $es_edicion ? 'actualizar' : 'crear' ?>">
        <?php if ($es_edicion): ?>
            <input type="hidden" name="id_peliculas" value="<?= (int) $f["id_peliculas"] ?>">
        <?php endif; ?>

        <label class="completo">
            Nombre de la película
            <input type="text" name="nomb_p" maxlength="120" required
                   value="<?= htmlspecialchars($f["nomb_p"]) ?>">
        </label>

        <label>
            Año
            <input type="number" name="anio" min="1888" max="<?= (int) date('Y') + 2 ?>" required
                   value="<?= htmlspecialchars($f["anio"]) ?>">
        </label>

        <label>
            Duración (minutos)
            <input type="number" name="duracion" min="1" max="400" required
                   value="<?= htmlspecialchars($f["duracion"]) ?>">
        </label>

        <label>
            Género
            <select name="id_genero" required>
                <option value="">Selecciona un género</option>
                <?php foreach ($generos as $g): ?>
                    <option value="<?= (int) $g["id_genero"] ?>"
                        <?= (string) $g["id_genero"] === (string) $f["id_genero"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($g["nomb_genero"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Calificación (0 a 100)
            <input type="number" name="rating" min="0" max="100"
                   value="<?= htmlspecialchars($f["rating"]) ?>">
        </label>

        <label class="completo">
            Póster (JPG, PNG o WEBP, máximo 2 MB)
            <input type="file" name="poster" accept="image/jpeg,image/png,image/webp">
            <small>
                <?= $es_edicion && $f["poster_url"] !== ""
                    ? "Deja vacío para conservar el póster actual."
                    : "Opcional." ?>
            </small>
        </label>

        <label class="completo">
            Sinopsis (opcional, máximo 1000 caracteres)
            <textarea name="sinopsis" maxlength="1000" rows="4"><?= htmlspecialchars($f["sinopsis"]) ?></textarea>
        </label>

        <button type="submit" class="btn-filter completo">
            <?= $es_edicion ? "Guardar cambios" : "Guardar película" ?>
        </button>

        <?php if ($es_edicion): ?>
            <a href="admin_peliculas.php" class="btn-secundario completo">Cancelar edición</a>
        <?php endif; ?>
    </form>

    <!-- ===== LISTADO ===== -->
    <h3 class="subtitulo">Películas</h3>
    <table class="tabla">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Año</th>
                <th>Género</th>
                <th>Calif.</th>
                <th>Funciones</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($listado as $p): ?>
                <?php $puede_eliminar = (int) $p["funciones"] === 0; ?>
                <tr>
                    <td><?= (int) $p["id_peliculas"] ?></td>
                    <td><?= htmlspecialchars($p["nomb_p"]) ?></td>
                    <td><?= (int) $p["anio"] ?></td>
                    <td><?= htmlspecialchars($p["nomb_genero"]) ?></td>
                    <td><?= (int) $p["calificacion_rt"] ?></td>
                    <td><?= (int) $p["funciones"] ?></td>
                    <td class="acciones-tabla">
                        <a class="btn-secundario"
                           href="admin_peliculas.php?editar=<?= (int) $p["id_peliculas"] ?>#formulario">Editar</a>

                        <form method="POST" action="admin_peliculas.php"
                              onsubmit="return confirm('¿Eliminar esta película?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_peliculas" value="<?= (int) $p["id_peliculas"] ?>">
                            <button type="submit" class="btn-peligro"
                                <?= $puede_eliminar ? "" : 'disabled title="Tiene funciones programadas"' ?>>
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</main>

<?php require 'includes/footer.php'; ?>