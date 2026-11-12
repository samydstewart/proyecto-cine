<?php
session_start();
require_once "includes/conexion.php";
require_once "includes/seguridad.php";
requerir_admin();

$salas     = $conexion->query("SELECT id_sala, nomb_sala FROM salas ORDER BY id_sala")->fetchAll();
$ids_salas = array_map('intval', array_column($salas, 'id_sala'));

// Sala seleccionada: viene por GET al elegirla o por POST después de cambiar un asiento
$id_sala = (int) ($_POST["id_sala"] ?? $_GET["id_sala"] ?? 0);
if (!in_array($id_sala, $ids_salas, true)) {
    $id_sala = $ids_salas[0] ?? 0;
}

$error = "";
$aviso = "";

if (($_GET["ok"] ?? "") === "mant") {
    $aviso = "Asiento puesto en mantenimiento. Boletos futuros afectados (clientes notificados): "
           . (int) ($_GET["n"] ?? 0) . ".";
} elseif (($_GET["ok"] ?? "") === "act") {
    $aviso = "Asiento activado.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_asiento = filter_input(INPUT_POST, "id_asiento", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    $activar    = ($_POST["accion"] ?? "") === "activar";

    if (!csrf_valido()) {
        $error = "La sesión del formulario expiró. Recarga la página.";
    } elseif (!$id_asiento) {
        $error = "Asiento no válido.";
    } else {
        try {
            // Cuántos boletos futuros tiene el asiento (para informar al admin)
            $afectados = 0;
            if (!$activar) {
                $c = $conexion->prepare(
                    "SELECT COUNT(*)
                     FROM boletos b
                     JOIN funciones f ON f.id_funcion = b.id_funcion
                     WHERE b.id_asiento = :a AND f.fecha_hora >= CURRENT_TIMESTAMP"
                );
                $c->execute([":a" => $id_asiento]);
                $afectados = (int) $c->fetchColumn();
            }

            // El trigger trg_notificar_mantenimiento crea las notificaciones
            $upd = $conexion->prepare("UPDATE asientos SET estado = :e WHERE id_asiento = :a");
            $upd->bindValue(":e", $activar, PDO::PARAM_BOOL);
            $upd->bindValue(":a", $id_asiento, PDO::PARAM_INT);
            $upd->execute();

            $ok = $activar ? "act" : "mant";
            header("Location: admin_asientos.php?id_sala=" . $id_sala . "&ok=" . $ok . "&n=" . $afectados);
            exit();
        } catch (PDOException $e) {
            $error = mensaje_db($e, "No se pudo cambiar el estado del asiento.");
        }
    }
}

$asientos = [];
if ($id_sala > 0) {
    $consulta = $conexion->prepare(
        "SELECT id_asiento, numero_asiento, estado
         FROM asientos
         WHERE id_sala = :s
         ORDER BY left(numero_asiento, 1), length(numero_asiento), numero_asiento"
    );
    $consulta->execute([":s" => $id_sala]);
    $asientos = $consulta->fetchAll();
}

$titulo = "Administrar asientos | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Estado de asientos</h2>
    <?php require 'includes/admin_menu.php'; ?>

    <form method="GET" action="admin_asientos.php" class="form-admin">
        <label class="completo">
            Sala
            <select name="id_sala" onchange="this.form.submit()">
                <?php foreach ($salas as $s): ?>
                    <option value="<?= (int) $s["id_sala"] ?>" <?= (int) $s["id_sala"] === $id_sala ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s["nomb_sala"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>

    <?php if ($aviso !== ""): ?>
        <p class="alerta alerta-exito"><?= htmlspecialchars($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="alerta"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <p class="descripcion">
        Verde: disponible. Gris: en mantenimiento. El estado aplica a todas las funciones de la sala.
        Si un asiento tiene boletos futuros, sus clientes recibirán una notificación.
    </p>

    <div class="asientos">
        <?php foreach ($asientos as $a): ?>
            <?php $disponible = in_array($a["estado"], [true, 't', 1, '1'], true); ?>
            <form method="POST" action="admin_asientos.php" class="asiento-form"
                  onsubmit="return confirm('¿Cambiar el estado de este asiento?');">
                <?= csrf_campo() ?>
                <input type="hidden" name="id_sala" value="<?= $id_sala ?>">
                <input type="hidden" name="id_asiento" value="<?= (int) $a["id_asiento"] ?>">
                <input type="hidden" name="accion" value="<?= $disponible ? 'mantenimiento' : 'activar' ?>">
                <button type="submit"
                        class="<?= $disponible ? 'asiento-activo' : 'asiento-mant' ?>"
                        title="<?= $disponible ? 'Poner en mantenimiento' : 'Activar asiento' ?>">
                    <?= htmlspecialchars($a["numero_asiento"]) ?>
                </button>
            </form>
        <?php endforeach; ?>
    </div>

</main>

<?php require 'includes/footer.php'; ?>