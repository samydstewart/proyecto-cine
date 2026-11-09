<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

require_once "includes/conexion.php";

$titulo  = "Mis boletos | Cine Colombia";
$aviso   = (($_GET["compra"] ?? "") === "ok") ? "¡Tu compra se registró correctamente!" : "";
$boletos = [];

try {
    $consulta = $conexion->prepare(
        "SELECT b.id_boletos, b.fecha_compra, f.fecha_hora, f.precio,
                p.nomb_p, s.nomb_sala, a.numero_asiento, pf.nomb_medio,
                CASE WHEN f.fecha_hora >= CURRENT_TIMESTAMP THEN 1 ELSE 0 END AS vigente
         FROM boletos b
         JOIN funciones f   ON f.id_funcion = b.id_funcion
         JOIN peliculas p   ON p.id_peliculas = f.id_peliculas
         JOIN salas s       ON s.id_sala = f.id_sala
         JOIN asientos a    ON a.id_asiento = b.id_asiento
         JOIN pagos_factura pf ON pf.id_forma_pago = b.id_forma_pago
         WHERE b.id_usuario = :usuario
         ORDER BY f.fecha_hora ASC"
    );
    $consulta->execute([":usuario" => (int) $_SESSION["id_usuario"]]);
    $boletos = $consulta->fetchAll();
} catch (PDOException $e) {
    mostrar_error_servidor(mensaje_db($e, "No se pudieron cargar tus boletos."), 500);
}

$proximos = [];
$pasados  = [];
foreach ($boletos as $b) {
    if ((int) $b["vigente"] === 1) {
        $proximos[] = $b;
    } else {
        $pasados[] = $b;
    }
}
$pasados = array_reverse($pasados); // las más recientes primero

function tarjeta_boleto(array $b): string
{
    $clase = (int) $b["vigente"] === 1 ? "boleto" : "boleto boleto-pasado";

    return '<article class="' . $clase . '">'
        . '<p class="boleto-codigo">Boleto #' . (int) $b["id_boletos"] . '</p>'
        . '<h3>' . htmlspecialchars($b["nomb_p"]) . '</h3>'
        . '<p><strong>Fecha:</strong> ' . date("d/m/Y h:i A", strtotime($b["fecha_hora"])) . '</p>'
        . '<p><strong>Sala:</strong> ' . htmlspecialchars($b["nomb_sala"]) . '</p>'
        . '<p><strong>Asiento:</strong> ' . htmlspecialchars($b["numero_asiento"]) . '</p>'
        . '<p><strong>Pagado con:</strong> ' . htmlspecialchars($b["nomb_medio"]) . '</p>'
        . '<p class="boleto-precio">$' . number_format($b["precio"], 0, ",", ".") . '</p>'
        . '</article>';
}

require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Mis boletos</h2>

    <?php if ($aviso !== ""): ?>
        <p class="alerta alerta-exito"><?= htmlspecialchars($aviso) ?></p>
    <?php endif; ?>

    <h3 class="subtitulo">Próximas funciones</h3>

    <?php if (count($proximos) === 0): ?>
        <p class="descripcion">No tienes boletos para funciones próximas.</p>
        <a href="index.php" class="btn-primario btn-ancho">Ver cartelera</a>
    <?php else: ?>
        <div class="cuadricula-boletos">
            <?php foreach ($proximos as $b) echo tarjeta_boleto($b); ?>
        </div>
    <?php endif; ?>

    <?php if (count($pasados) > 0): ?>
        <h3 class="subtitulo">Funciones pasadas</h3>
        <div class="cuadricula-boletos">
            <?php foreach ($pasados as $b) echo tarjeta_boleto($b); ?>
        </div>
    <?php endif; ?>

</main>

<?php require 'includes/footer.php'; ?>