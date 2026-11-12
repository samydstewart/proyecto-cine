<?php
session_start();
require_once "includes/conexion.php";
require_once "includes/seguridad.php";
require_once "includes/validaciones.php";
require_once "includes/storage.php";
requerir_admin();

// Nombres de tablas y columnas fijos aquí (no vienen del usuario),
// por eso es seguro usarlos dentro del SQL.
const TIPOS = [
    "actor" => [
        "etiqueta" => "Actor",     "plural" => "Actores",
        "tabla"    => "actores",   "id"     => "id_actor",
        "nom"      => "nomb1_a",   "ape"    => "apel_a",
        "fec"      => "fecha_nac_a",
        "carpeta"  => "actores",   "puente" => "actua",
    ],
    "director" => [
        "etiqueta" => "Director",   "plural" => "Directores",
        "tabla"    => "directores", "id"     => "id_director",
        "nom"      => "nomb1_d",    "ape"    => "apel_d",
        "fec"      => "fecha_nac_d",
        "carpeta"  => "directores", "puente" => "dirige",
    ],
];

const AVISOS_PERSONA = [
    "creada"      => "Persona registrada correctamente.",
    "actualizada" => "Persona actualizada correctamente.",
    "eliminada"   => "Persona eliminada.",
    "asignada"    => "Asignación guardada.",
];

function validar_persona(array $d): string
{
    if ($d["nombre"] === "" || mb_strlen($d["nombre"]) > 20 || !preg_match('/^\p{L}+$/u', $d["nombre"])) {
        return "El nombre solo puede tener letras y máximo 20 caracteres.";
    }
    if ($d["apellido"] === "" || mb_strlen($d["apellido"]) > 30
        || !preg_match('/^\p{L}+(?: \p{L}+)*$/u', $d["apellido"])) {
        return "Los apellidos solo pueden tener letras y espacios, máximo 30 caracteres.";
    }

    $fecha = DateTimeImmutable::createFromFormat('Y-m-d', $d["fecha"]);
    if ($fecha === false || $fecha->format('Y-m-d') !== $d["fecha"]) {
        return "La fecha de nacimiento no es válida.";
    }
    if ($fecha >= new DateTimeImmutable('today') || $fecha < new DateTimeImmutable('1880-01-01')) {
        return "La fecha de nacimiento debe ser anterior a hoy y posterior a 1880.";
    }
    return "";
}

$error = "";
$aviso = AVISOS_PERSONA[$_GET["ok"] ?? ""] ?? "";

$tipo = $_POST["tipo"] ?? $_GET["tipo"] ?? "actor";
if (!isset(TIPOS[$tipo])) {
    $tipo = "actor";
}
$cfg = TIPOS[$tipo];

$f = ["id" => "", "nombre" => "", "apellido" => "", "fecha" => ""];

// ---------- POST ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $id     = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

    if (!csrf_valido()) {
        $error = "La sesión del formulario expiró. Recarga la página.";

    } else {
        try {
            if ($accion === "crear" || $accion === "actualizar") {
                $f = [
                    "id"       => (string) ($id ?? ""),
                    "nombre"   => $_POST["nombre"] ?? "",
                    "apellido" => $_POST["apellido"] ?? "",
                    "fecha"    => $_POST["fecha_nac"] ?? "",
                ];

                $datos = [
                    "nombre"   => texto_limpio($f["nombre"]),
                    "apellido" => texto_limpio($f["apellido"]),
                    "fecha"    => trim($f["fecha"]),
                ];

                if ($accion === "actualizar" && !$id) {
                    $error = "Persona no válida.";
                } else {
                    $error = validar_persona($datos);
                }

                if ($error === "") {
                    $foto = null;
                    if (($_FILES["foto"]["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $foto = subir_imagen($_FILES["foto"], $cfg["carpeta"]);
                    }

                    $parametros = [
                        ":n"    => $datos["nombre"],
                        ":a"    => $datos["apellido"],
                        ":f"    => $datos["fecha"],
                        ":foto" => $foto,
                    ];

                    if ($accion === "crear") {
                        $conexion->prepare(
                            "INSERT INTO {$cfg['tabla']} ({$cfg['nom']}, {$cfg['ape']}, {$cfg['fec']}, foto_url)
                             VALUES (:n, :a, :f, :foto)"
                        )->execute($parametros);
                        $ok = "creada";
                    } else {
                        $parametros[":id"] = $id;
                        $conexion->prepare(
                            "UPDATE {$cfg['tabla']}
                             SET {$cfg['nom']} = :n, {$cfg['ape']} = :a, {$cfg['fec']} = :f,
                                 foto_url = COALESCE(:foto, foto_url)
                             WHERE {$cfg['id']} = :id"
                        )->execute($parametros);
                        $ok = "actualizada";
                    }

                    header("Location: admin_personas.php?tipo=$tipo&ok=$ok");
                    exit();
                }

            } elseif ($accion === "eliminar") {
                if (!$id) {
                    $error = "Persona no válida.";
                } else {
                    // Las filas de dirige o actua se borran solas (ON DELETE CASCADE)
                    $conexion->prepare("DELETE FROM {$cfg['tabla']} WHERE {$cfg['id']} = :id")
                             ->execute([":id" => $id]);
                    header("Location: admin_personas.php?tipo=$tipo&ok=eliminada");
                    exit();
                }

            } elseif ($accion === "asignar") {
                $persona  = filter_input(INPUT_POST, "id_persona", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
                $pelicula = filter_input(INPUT_POST, "id_pelicula", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

                if (!$persona || !$pelicula) {
                    $error = "Selecciona una persona y una película.";
                } else {
                    $conexion->prepare(
                        "INSERT INTO {$cfg['puente']} ({$cfg['id']}, id_peliculas)
                         VALUES (:p, :m) ON CONFLICT DO NOTHING"
                    )->execute([":p" => $persona, ":m" => $pelicula]);
                    header("Location: admin_personas.php?tipo=$tipo&ok=asignada");
                    exit();
                }
            }

        } catch (PDOException $e) {
            $error = mensaje_db($e, "No se pudo guardar.");
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

// ---------- Cargar persona para editar (GET ?tipo=...&editar=ID) ----------
$editar = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if ($editar && $_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        $st = $conexion->prepare(
            "SELECT {$cfg['id']} AS id, {$cfg['nom']} AS nombre, {$cfg['ape']} AS apellido, {$cfg['fec']} AS fecha
             FROM {$cfg['tabla']} WHERE {$cfg['id']} = :id"
        );
        $st->execute([":id" => $editar]);
        $fila = $st->fetch();

        if ($fila) {
            $f = [
                "id"       => (string) $fila["id"],
                "nombre"   => $fila["nombre"],
                "apellido" => $fila["apellido"],
                "fecha"    => $fila["fecha"],
            ];
        } else {
            $error = "La persona no existe.";
        }
    } catch (PDOException $e) {
        $error = mensaje_db($e, "No se pudo cargar la persona.");
    }
}

// ---------- Listados ----------
$personas = [];
foreach (TIPOS as $t => $c) {
    $personas[$t] = $conexion->query(
        "SELECT t.{$c['id']} AS id, t.{$c['nom']} AS nombre, t.{$c['ape']} AS apellido,
                t.foto_url,
                (SELECT COUNT(*) FROM {$c['puente']} x WHERE x.{$c['id']} = t.{$c['id']}) AS peliculas
         FROM {$c['tabla']} t
         ORDER BY t.{$c['ape']}"
    )->fetchAll();
}

$peliculas = $conexion->query("SELECT id_peliculas, nomb_p FROM peliculas ORDER BY nomb_p")->fetchAll();

$es_edicion = $f["id"] !== "";

$titulo = "Directores y actores | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Directores y actores</h2>
    <?php require 'includes/admin_menu.php'; ?>

    <?php if ($aviso !== ""): ?>
        <p class="alerta alerta-exito"><?= htmlspecialchars($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="alerta"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- ===== CREAR / EDITAR ===== -->
    <h3 class="subtitulo" id="formulario">
        <?= $es_edicion ? "Editar " . strtolower($cfg["etiqueta"]) : "Registrar persona" ?>
    </h3>

    <form method="POST" action="admin_personas.php" enctype="multipart/form-data" class="form-admin">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="<?= $es_edicion ? 'actualizar' : 'crear' ?>">
        <?php if ($es_edicion): ?>
            <input type="hidden" name="id" value="<?= (int) $f["id"] ?>">
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
        <?php endif; ?>

        <label>
            Tipo
            <select name="tipo" <?= $es_edicion ? "disabled" : "" ?>>
                <?php foreach (TIPOS as $t => $c): ?>
                    <option value="<?= $t ?>" <?= $t === $tipo ? "selected" : "" ?>>
                        <?= $c["etiqueta"] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Nombre
            <input type="text" name="nombre" maxlength="20" required
                   value="<?= htmlspecialchars($f["nombre"]) ?>">
        </label>

        <label>
            Apellidos
            <input type="text" name="apellido" maxlength="30" required
                   value="<?= htmlspecialchars($f["apellido"]) ?>">
        </label>

        <label>
            Fecha de nacimiento
            <input type="date" name="fecha_nac" min="1880-01-01" max="<?= date('Y-m-d') ?>" required
                   value="<?= htmlspecialchars($f["fecha"]) ?>">
        </label>

        <label class="completo">
            Foto (JPG, PNG o WEBP, máximo 2 MB)
            <input type="file" name="foto" accept="image/jpeg,image/png,image/webp">
            <small><?= $es_edicion ? "Deja vacío para conservar la foto actual." : "Opcional." ?></small>
        </label>

        <button type="submit" class="btn-filter completo">
            <?= $es_edicion ? "Guardar cambios" : "Guardar persona" ?>
        </button>

        <?php if ($es_edicion): ?>
            <a href="admin_personas.php?tipo=<?= htmlspecialchars($tipo) ?>" class="btn-secundario completo">
                Cancelar edición
            </a>
        <?php endif; ?>
    </form>

    <!-- ===== ASIGNAR A PELÍCULAS ===== -->
    <h3 class="subtitulo">Asignar a una película</h3>
    <div class="form-admin">
        <?php foreach (TIPOS as $t => $c): ?>
            <form method="POST" action="admin_personas.php" class="completo">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="asignar">
                <input type="hidden" name="tipo" value="<?= $t ?>">

                <label>
                    <?= $c["etiqueta"] ?>
                    <select name="id_persona" required>
                        <option value="">Selecciona</option>
                        <?php foreach ($personas[$t] as $p): ?>
                            <option value="<?= (int) $p["id"] ?>">
                                <?= htmlspecialchars($p["nombre"] . " " . $p["apellido"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Película
                    <select name="id_pelicula" required>
                        <option value="">Selecciona</option>
                        <?php foreach ($peliculas as $m): ?>
                            <option value="<?= (int) $m["id_peliculas"] ?>">
                                <?= htmlspecialchars($m["nomb_p"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <button type="submit" class="btn-secundario">Asignar <?= strtolower($c["etiqueta"]) ?></button>
            </form>
        <?php endforeach; ?>
    </div>

    <!-- ===== LISTADOS ===== -->
    <?php foreach (TIPOS as $t => $c): ?>
        <h3 class="subtitulo"><?= $c["plural"] ?></h3>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Películas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personas[$t] as $p): ?>
                    <tr>
                        <td>
                            <img class="thumb"
                                 src="<?= htmlspecialchars(url_imagen($p["foto_url"], FOTO_RESPALDO)) ?>" alt="">
                        </td>
                        <td><?= htmlspecialchars($p["nombre"] . " " . $p["apellido"]) ?></td>
                        <td><?= (int) $p["peliculas"] ?></td>
                        <td class="acciones-tabla">
                            <a class="btn-secundario"
                               href="admin_personas.php?tipo=<?= $t ?>&editar=<?= (int) $p["id"] ?>#formulario">Editar</a>

                            <form method="POST" action="admin_personas.php"
                                  onsubmit="return confirm('¿Eliminar a esta persona? Se quitará de sus películas.');">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="tipo" value="<?= $t ?>">
                                <input type="hidden" name="id" value="<?= (int) $p["id"] ?>">
                                <button type="submit" class="btn-peligro">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

</main>

<?php require 'includes/footer.php'; ?>