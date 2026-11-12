<?php
session_start();
require_once "includes/conexion.php";
require_once "includes/seguridad.php";
requerir_sesion();

$usuario = (int) $_SESSION["id_usuario"];

$aviso = match ($_GET["ok"] ?? "") {
    "cambio"    => "Asiento cambiado correctamente. Tu boleto ya tiene el nuevo asiento.",
    "reembolso" => "Reembolso registrado. El boleto fue cancelado.",
    default     => "",
};
$error = $_GET["error"] ?? "";

try {
    $consulta = $conexion->prepare(
        "SELECT n.id_notificacion, n.mensaje, n.estado, n.fecha_creada, n.id_boletos,
                f.id_funcion, f.fecha_hora, f.id_sala,
                p.nomb_p, a.numero_asiento
         FROM notificaciones n
         LEFT JOIN boletos b   ON b.id_boletos = n.id_boletos
         LEFT JOIN funciones f ON f.id_funcion = b.id_funcion
         LEFT JOIN peliculas p ON p.id_peliculas = f.id_peliculas
         LEFT JOIN asientos a  ON a.id_asiento = b.id_asiento
         WHERE n.id_usuario = :u
         ORDER BY (n.estado = 'pendiente') DESC, n.fecha_creada DESC"
    );
    $consulta->execute([":u" => $usuario]);
    $notificaciones = $consulta->fetchAll();

    // Asientos libres en la función de cada notificación pendiente
    $libres_consulta = $conexion->prepare(
        "SELECT a.id_asiento, a.numero_asiento
         FROM asientos a
         WHERE a.id_sala = :sala
           AND a.estado = TRUE
           AND NOT EXISTS (
               SELECT 1 FROM boletos x
               WHERE x.id_asiento = a.id_asiento AND x.id_funcion = :funcion
           )
         ORDER BY left(a.numero_asiento, 1), length(a.numero_asiento), a.numero_asiento"
    );

    $asientos_libres = [];
    foreach ($notificaciones as $n) {
        if ($n["estado"] === "pendiente" && $n["id_boletos"] !== null) {
            $libres_consulta->execute([":sala" => $n["id_sala"], ":funcion" => $n["id_funcion"]]);
            $asientos_libres[$n["id_notificacion"]] = $libres_consulta->fetchAll();
        }
    }
} catch (PDOException $e) {
    mostrar_error_servidor(mensaje_db($e, "No se pudieron cargar tus notificaciones."), 500);
}

$titulo = "Mis notificaciones | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Notificaciones</h2>

    <?php if ($aviso !== ""): ?>
        <p class="alerta alerta-exito"><?= htmlspecialchars($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="alerta"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (count($notificaciones) === 0): ?>
        <p class="descripcion">No tienes notificaciones.</p>
    <?php endif; ?>

    <?php foreach ($notificaciones as $n): ?>
        <?php
            $pendiente = $n["estado"] === "pendiente";
            $accionable = $pendiente && $n["id_boletos"] !== null;
            $libres = $asientos_libres[$n["id_notificacion"]] ?? [];
        ?>
        <article class="notif <?= $pendiente ? 'notif-pendiente' : 'notif-resuelta' ?>">

            <p class="notif-fecha"><?= date("d/m/Y h:i A", strtotime($n["fecha_creada"])) ?></p>

            <h3><?= htmlspecialchars($n["nomb_p"] ?? "Boleto cancelado") ?></h3>

            <?php if ($n["fecha_hora"] !== null): ?>
                <p class="descripcion">
                    Función: <?= date("d/m/Y h:i A", strtotime($n["fecha_hora"])) ?>
                    · Asiento actual: <?= htmlspecialchars($n["numero_asiento"] ?? "-") ?>
                </p>
            <?php endif; ?>

            <p><?= htmlspecialchars($n["mensaje"]) ?></p>

            <?php if ($accionable): ?>
                <div class="notif-acciones">

                    <?php if (count($libres) > 0): ?>
                        <form action="accion_notificacion.php" method="POST" class="notif-form">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id_notificacion" value="<?= (int) $n["id_notificacion"] ?>">
                            <input type="hidden" name="accion" value="cambiar">
                            <select name="id_asiento" required>
                                <option value="">Elige un asiento</option>
                                <?php foreach ($libres as $l): ?>
                                    <option value="<?= (int) $l["id_asiento"] ?>">
                                        <?= htmlspecialchars($l["numero_asiento"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-primario">Cambiar asiento</button>
                        </form>
                    <?php else: ?>
                        <p class="descripcion">No hay asientos libres en esta función.</p>
                    <?php endif; ?>

                    <form action="accion_notificacion.php" method="POST" class="notif-form"
                          onsubmit="return confirm('¿Cancelar el boleto y pedir el reembolso?');">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="id_notificacion" value="<?= (int) $n["id_notificacion"] ?>">
                        <input type="hidden" name="accion" value="reembolsar">
                        <button type="submit" class="btn-secundario">Pedir reembolso</button>
                    </form>

                </div>
            <?php elseif (!$pendiente): ?>
                <p class="notif-estado">Resuelta</p>
            <?php endif; ?>

        </article>
    <?php endforeach; ?>

</main>

<?php require 'includes/footer.php'; ?>