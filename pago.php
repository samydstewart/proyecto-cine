<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once "includes/conexion.php";

// Acepta POST (desde asientos) y GET (al volver con error)
$id_funcion = filter_var($_REQUEST["id_funcion"] ?? null, FILTER_VALIDATE_INT);
$id_asiento = filter_var($_REQUEST["id_asiento"] ?? null, FILTER_VALIDATE_INT);
$error = $_GET["error"] ?? "";

if (!$id_funcion || !$id_asiento) {
    header("Location: comprar.php");
    exit();
}

// Resumen de la compra. Verifica que la función siga vigente y el asiento libre.
$consulta = $conexion->prepare(
    "SELECT p.nomb_p, s.nomb_sala, f.fecha_hora, f.precio, a.numero_asiento
     FROM funciones f
     JOIN peliculas p ON p.id_peliculas = f.id_peliculas
     JOIN salas s ON s.id_sala = f.id_sala
     JOIN asientos a ON a.id_sala = f.id_sala AND a.id_asiento = :asiento
     WHERE f.id_funcion = :funcion
       AND f.fecha_hora >= CURRENT_TIMESTAMP
       AND a.estado = TRUE
       AND NOT EXISTS (
           SELECT 1 FROM boletos b
           WHERE b.id_asiento = a.id_asiento
             AND b.id_funcion = f.id_funcion
       )"
);
$consulta->execute([":funcion" => $id_funcion, ":asiento" => $id_asiento]);
$resumen = $consulta->fetch();

if (!$resumen) {
    header("Location: seleccionar-asientos.php?id_funcion=" . $id_funcion
        . "&error=" . urlencode("Ese asiento ya no está disponible."));
    exit();
}

// Medios de pago disponibles
$formas = $conexion->query(
    "SELECT id_forma_pago, nomb_medio, comision
     FROM pagos_factura
     ORDER BY id_forma_pago"
)->fetchAll();

$titulo = "Pago | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Pago</h2>

    <div class="informacion-funcion">
        <p><strong>Película:</strong> <?= htmlspecialchars($resumen["nomb_p"]) ?></p>
        <p><strong>Sala:</strong> <?= htmlspecialchars($resumen["nomb_sala"]) ?></p>
        <p><strong>Fecha:</strong> <?= date("d/m/Y h:i A", strtotime($resumen["fecha_hora"])) ?></p>
        <p><strong>Asiento:</strong> <?= htmlspecialchars($resumen["numero_asiento"]) ?></p>
        <p><strong>Precio del boleto:</strong> $<?= number_format($resumen["precio"], 0, ",", ".") ?></p>
    </div>

    <?php if ($error !== ""): ?>
        <p class="alerta"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="confirmar-compra.php" method="POST" class="form-pago">

        <input type="hidden" name="id_funcion" value="<?= $id_funcion ?>">
        <input type="hidden" name="id_asiento" value="<?= $id_asiento ?>">

        <fieldset class="medios">
            <legend>Medio de pago</legend>

            <?php foreach ($formas as $forma): ?>
                <?php
                    $es_tarjeta = str_contains($forma["nomb_medio"], "Tarjeta");
                    $total = $resumen["precio"] + $forma["comision"];
                ?>
                <label class="medio-opcion">
                    <input type="radio"
                           name="id_forma_pago"
                           value="<?= $forma['id_forma_pago'] ?>"
                           data-tarjeta="<?= $es_tarjeta ? 1 : 0 ?>"
                           required>
                    <span class="medio-nombre"><?= htmlspecialchars($forma["nomb_medio"]) ?></span>
                    <span class="medio-total">
                        Total: $<?= number_format($total, 0, ",", ".") ?>
                        <?php if ($forma["comision"] > 0): ?>
                            <small>(incluye comisión de $<?= number_format($forma["comision"], 0, ",", ".") ?>)</small>
                        <?php endif; ?>
                    </span>
                    <?php if ($forma["nomb_medio"] === "Efectivo"): ?>
                        <small class="medio-nota">Pagas en taquilla antes de la función.</small>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <div id="datos-tarjeta" class="datos-tarjeta" hidden>
            <h3>Datos de la tarjeta</h3>

            <div class="filter-group">
                <label for="titular">Nombre del titular</label>
                <input type="text" id="titular" name="titular" placeholder="Como aparece en la tarjeta">
            </div>

            <div class="filter-group">
                <label for="numero_tarjeta">Número de tarjeta</label>
                <input type="text" id="numero_tarjeta" name="numero_tarjeta"
                       inputmode="numeric" placeholder="4111 1111 1111 1111" maxlength="23">
            </div>

            <div class="fila-tarjeta">
                <div class="filter-group">
                    <label for="vencimiento">Vencimiento</label>
                    <input type="text" id="vencimiento" name="vencimiento"
                           placeholder="MM/AA" maxlength="5">
                </div>

                <div class="filter-group">
                    <label for="cvv">CVV</label>
                    <input type="password" id="cvv" name="cvv"
                           inputmode="numeric" placeholder="123" maxlength="4">
                </div>
            </div>

            <p class="nota">Simulación: no se cobra ni se guarda ningún dato de la tarjeta.</p>
        </div>

        <button type="submit" class="btn-primario btn-comprar">Confirmar pago</button>

    </form>

    <a href="seleccionar-asientos.php?id_funcion=<?= $id_funcion ?>" class="btn-volver">← Cambiar asiento</a>

</main>

<script>
document.querySelectorAll('input[name="id_forma_pago"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        var esTarjeta = this.dataset.tarjeta === '1';
        var caja = document.getElementById('datos-tarjeta');
        caja.hidden = !esTarjeta;
        caja.querySelectorAll('input').forEach(function (campo) {
            campo.required = esTarjeta;
        });
    });
});
</script>

<?php require 'includes/footer.php'; ?>