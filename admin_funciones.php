<?php
session_start();
require_once "includes/conexion.php";
require_once "includes/seguridad.php";
require_once "includes/validaciones.php";
requerir_admin();

const AVISOS_FUNCION = [
    "creada"      => "Función programada correctamente.",
    "actualizada" => "Función actualizada correctamente.",
    "eliminada"   => "Función eliminada.",
];

$error = "";
$aviso = AVISOS_FUNCION[$_GET["ok"] ?? ""] ?? "";

$peliculas = $conexion->query("SELECT id_peliculas, nomb_p FROM peliculas ORDER BY nomb_p")->fetchAll();
$salas     = $conexion->query("SELECT id_sala, nomb_sala FROM salas ORDER BY id_sala")->fetchAll();
$ids_pelis = array_map('intval', array_column($peliculas, 'id_peliculas'));
$ids_salas = array_map('intval', array_column($salas, 'id_sala'));

$f = [
    "id_funcion" => "",
    "pelicula"   => "",
    "sala"       => "",
    "fecha"      => "",
    "precio"     => "",
];

// Devuelve los datos de una función con sus conteos, o null si no existe
function estado_funcion(PDO $c, int $id): ?array
{
    $st = $c->prepare(
        "SELECT f.id_funcion, f.id_peliculas, f.id_sala, f.fecha_hora, f.precio,
                CASE WHEN f.fecha_hora <= CURRENT_TIMESTAMP THEN 1 ELSE 0 END AS pasada,
                (SELECT COUNT(*) FROM boletos b    WHERE b.id_funcion = f.id_funcion) AS boletos,
                (SELECT COUNT(*) FROM reembolsos r WHERE r.id_funcion = f.id_funcion) AS reembolsos
         FROM funciones f
         WHERE f.id_funcion = :id"
    );
    $st->execute([":id" => $id]);
    $fila = $st->fetch();
    return $fila ?: null;
}

// ---------- POST ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $id     = filter_input(INPUT_POST, "id_funcion", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

    if (!csrf_valido()) {
        $error = "La sesión del formulario expiró. Recarga la página.";

    } elseif ($accion === "eliminar") {
        try {
            $actual = $id ? estado_funcion($conexion, $id) : null;

            if (!$actual) {
                $error = "La función no existe.";
            } elseif ((int) $actual["reembolsos"] > 0) {
                $error = "No se puede eliminar: tiene reembolsos registrados. Se conserva el historial.";
            } elseif ((int) $actual["boletos"] > 0 && (int) $actual["pasada"] === 0) {
                $error = "No se puede eliminar: tiene " . (int) $actual["boletos"]
                       . " boleto(s) vendido(s) y aún no ocurre. Solo se eliminan funciones pasadas o sin boletos.";
            } else {
                $conexion->beginTransaction();
                try {
                    if ((int) $actual["boletos"] > 0) {
                        // Función pasada con boletos: se borran sus boletos.
                        // Usa la misma excepción administrativa de fn_proteger_boleto.
                        $conexion->query("SELECT set_config('app.motivo_cancelacion', 'mantenimiento', true)");
                        $conexion->prepare("DELETE FROM boletos WHERE id_funcion = :f")
                                 ->execute([":f" => $id]);
                    }
                    $conexion->prepare("DELETE FROM funciones WHERE id_funcion = :f")
                             ->execute([":f" => $id]);
                    $conexion->commit();
                } catch (Throwable $t) {
                    $conexion->rollBack();
                    throw $t;
                }

                header("Location: admin_funciones.php?ok=eliminada");
                exit();
            }
        } catch (PDOException $e) {
            $error = mensaje_db($e, "No se pudo eliminar la función.");
        }

    } elseif ($accion === "crear" || $accion === "actualizar") {

        $f = [
            "id_funcion" => (string) ($id ?? ""),
            "pelicula"   => $_POST["id_peliculas"] ?? "",
            "sala"       => $_POST["id_sala"] ?? "",
            "fecha"      => $_POST["fecha_hora"] ?? "",
            "precio"     => $_POST["precio"] ?? "",
        ];

        $datos = [
            "pelicula" => trim($f["pelicula"]),
            "sala"     => trim($f["sala"]),
            "fecha"    => trim($f["fecha"]),
            "precio"   => trim($f["precio"]),
        ];

        try {
            if ($accion === "actualizar" && !$id) {
                $error = "Función no válida.";
            }

            if ($error === "" && $accion === "actualizar") {
                $actual = estado_funcion($conexion, $id);
                if (!$actual) {
                    $error = "La función no existe.";
                } elseif ((int) $actual["pasada"] === 1) {
                    $error = "No se puede modificar una función que ya pasó.";
                } elseif ((int) $actual["boletos"] > 0) {
                    $error = "No se puede modificar: la función ya tiene boletos vendidos.";
                }
            }

            if ($error === "") {
                $error = validar_funcion($datos, $ids_pelis, $ids_salas);
            }

            if ($error === "") {
                // validar_funcion ya comprobó el formato, así que $fecha siempre es válido
                $fecha = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $datos["fecha"]);

                $parametros = [
                    ":f"  => $fecha->format("Y-m-d H:i:s"),
                    ":s"  => (int) $datos["sala"],
                    ":p"  => (int) $datos["pelicula"],
                    ":pr" => (int) $datos["precio"],
                ];

                // Los triggers rechazan solapamientos y fechas pasadas
                if ($accion === "crear") {
                    $conexion->prepare(
                        "INSERT INTO funciones (fecha_hora, id_sala, id_peliculas, precio)
                         VALUES (:f, :s, :p, :pr)"
                    )->execute($parametros);
                    header("Location: admin_funciones.php?ok=creada");
                } else {
                    $parametros[":id"] = $id;
                    $conexion->prepare(
                        "UPDATE funciones
                         SET fecha_hora = :f, id_sala = :s, id_peliculas = :p, precio = :pr
                         WHERE id_funcion = :id"
                    )->execute($parametros);
                    header("Location: admin_funciones.php?ok=actualizada");
                }
                exit();
            }
        } catch (PDOException $e) {
            $error = mensaje_db($e, "No se pudo guardar la función.");
        }
    }
}

// ---------- Cargar función para editar (GET ?editar=ID) ----------
$editar_id = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if ($editar_id && $_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        $actual = estado_funcion($conexion, $editar_id);

        if (!$actual) {
            $error = "La función no existe.";
        } elseif ((int) $actual["pasada"] === 1 || (int) $actual["boletos"] > 0) {
            $error = "Esta función no se puede editar: ya pasó o tiene boletos vendidos.";
        } else {
            $f = [
                "id_funcion" => (string) $actual["id_funcion"],
                "pelicula"   => (string) $actual["id_peliculas"],
                "sala"       => (string) $actual["id_sala"],
                "fecha"      => date('Y-m-d\TH:i', strtotime($actual["fecha_hora"])),
                "precio"     => (string) (int) $actual["precio"],
            ];
        }
    } catch (PDOException $e) {
        $error = mensaje_db($e, "No se pudo cargar la función.");
    }
}

// ---------- Listado ----------
$listado = $conexion->query(
    "SELECT f.id_funcion, f.fecha_hora, f.precio, p.nomb_p, s.nomb_sala,
            CASE WHEN f.fecha_hora <= CURRENT_TIMESTAMP THEN 1 ELSE 0 END AS pasada,
            (SELECT COUNT(*) FROM boletos b    WHERE b.id_funcion = f.id_funcion) AS boletos,
            (SELECT COUNT(*) FROM reembolsos r WHERE r.id_funcion = f.id_funcion) AS reembolsos
     FROM funciones f
     JOIN peliculas p ON p.id_peliculas = f.id_peliculas
     JOIN salas s     ON s.id_sala = f.id_sala
     ORDER BY f.fecha_hora DESC
     LIMIT 100"
)->fetchAll();

$es_edicion = $f["id_funcion"] !== "";

$titulo = "Administrar funciones | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Funciones</h2>
    <?php require 'includes/admin_menu.php'; ?>

    <?php if ($aviso !== ""): ?>
        <p class="alerta alerta-exito"><?= htmlspecialchars($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="alerta"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <h3 class="subtitulo" id="formulario">
        <?= $es_edicion ? "Editar función" : "Programar función" ?>
    </h3>

    <form method="POST" action="admin_funciones.php" class="form-admin">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="<?= $es_edicion ? 'actualizar' : 'crear' ?>">
        <?php if ($es_edicion): ?>
            <input type="hidden" name="id_funcion" value="<?= (int) $f["id_funcion"] ?>">
        <?php endif; ?>

        <label class="completo">
            Película
            <select name="id_peliculas" required>
                <option value="">Selecciona una película</option>
                <?php foreach ($peliculas as $p): ?>
                    <option value="<?= (int) $p["id_peliculas"] ?>"
                        <?= (string) $p["id_peliculas"] === $f["pelicula"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($p["nomb_p"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Sala
            <select name="id_sala" required>
                <option value="">Selecciona una sala</option>
                <?php foreach ($salas as $s): ?>
                    <option value="<?= (int) $s["id_sala"] ?>"
                        <?= (string) $s["id_sala"] === $f["sala"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($s["nomb_sala"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Fecha y hora
            <input type="datetime-local" name="fecha_hora" required
                   value="<?= htmlspecialchars($f["fecha"]) ?>">
        </label>

        <label>
            Precio (pesos)
            <input type="number" name="precio" min="1" max="200000" step="1" required
                   value="<?= htmlspecialchars($f["precio"]) ?>">
        </label>

        <button type="submit" class="btn-filter completo">
            <?= $es_edicion ? "Guardar cambios" : "Programar función" ?>
        </button>

        <?php if ($es_edicion): ?>
            <a href="admin_funciones.php" class="btn-secundario completo">Cancelar edición</a>
        <?php endif; ?>
    </form>

    <h3 class="subtitulo">Funciones</h3>
    <table class="tabla">
        <thead>
            <tr>
                <th>ID</th>
                <th>Película</th>
                <th>Sala</th>
                <th>Fecha</th>
                <th>Precio</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($listado as $fn): ?>
                <?php
                    $pasada         = (int) $fn["pasada"] === 1;
                    $boletos        = (int) $fn["boletos"];
                    $reembolsos     = (int) $fn["reembolsos"];
                    $puede_editar   = !$pasada && $boletos === 0;
                    $puede_eliminar = $reembolsos === 0 && ($boletos === 0 || $pasada);

                    if ($pasada) {
                        $estado = "Pasada ($boletos boletos)";
                    } elseif ($boletos > 0) {
                        $estado = "Con $boletos boleto(s)";
                    } else {
                        $estado = "Sin boletos";
                    }
                ?>
                <tr>
                    <td><?= (int) $fn["id_funcion"] ?></td>
                    <td><?= htmlspecialchars($fn["nomb_p"]) ?></td>
                    <td><?= htmlspecialchars($fn["nomb_sala"]) ?></td>
                    <td><?= date("d/m/Y h:i A", strtotime($fn["fecha_hora"])) ?></td>
                    <td>$<?= number_format($fn["precio"], 0, ",", ".") ?></td>
                    <td><span class="estado-pill"><?= htmlspecialchars($estado) ?></span></td>
                    <td class="acciones-tabla">
                        <?php if ($puede_editar): ?>
                            <a class="btn-secundario"
                               href="admin_funciones.php?editar=<?= (int) $fn["id_funcion"] ?>#formulario">Editar</a>
                        <?php endif; ?>

                        <form method="POST" action="admin_funciones.php"
                              onsubmit="return confirm('<?= $pasada && $boletos > 0
                                  ? "Esta función ya pasó y tiene boletos. Se borrarán también sus boletos. ¿Continuar?"
                                  : "¿Eliminar esta función?" ?>');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_funcion" value="<?= (int) $fn["id_funcion"] ?>">
                            <button type="submit" class="btn-peligro"
                                <?= $puede_eliminar ? "" : 'disabled title="Tiene boletos vigentes o reembolsos"' ?>>
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