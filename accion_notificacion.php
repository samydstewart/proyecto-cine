<?php
session_start();
require_once "includes/conexion.php";
require_once "includes/seguridad.php";
requerir_sesion();

function volver(string $parametros): void
{
    header("Location: mis-notificaciones.php?" . $parametros);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    volver("");
}

if (!csrf_valido()) {
    volver("error=" . urlencode("La sesión del formulario expiró. Intenta de nuevo."));
}

$usuario = (int) $_SESSION["id_usuario"];
$accion  = $_POST["accion"] ?? "";
$id_noti = filter_input(INPUT_POST, "id_notificacion", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

if (!$id_noti) {
    volver("error=" . urlencode("Notificación no válida."));
}

try {
    $conexion->beginTransaction();

    // Bloquea la notificación: debe ser del usuario y seguir pendiente
    $bloqueo = $conexion->prepare(
        "SELECT id_boletos
         FROM notificaciones
         WHERE id_notificacion = :n
           AND id_usuario = :u
           AND estado = 'pendiente'
           AND id_boletos IS NOT NULL
         FOR UPDATE"
    );
    $bloqueo->execute([":n" => $id_noti, ":u" => $usuario]);
    $noti = $bloqueo->fetch();

    if (!$noti) {
        throw new RuntimeException("Esta notificación ya no está pendiente.");
    }

    $id_boleto = (int) $noti["id_boletos"];

    if ($accion === "cambiar") {
        $id_asiento = filter_input(INPUT_POST, "id_asiento", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
        if (!$id_asiento) {
            throw new RuntimeException("Selecciona un asiento.");
        }

        // Los triggers validan sala, función y estado del nuevo asiento.
        // El índice único impide que otra persona lo tenga al mismo tiempo.
        $mover = $conexion->prepare(
            "UPDATE boletos SET id_asiento = :asiento
             WHERE id_boletos = :b AND id_usuario = :u"
        );
        $mover->execute([":asiento" => $id_asiento, ":b" => $id_boleto, ":u" => $usuario]);

        $marcar = $conexion->prepare("UPDATE notificaciones SET estado = 'resuelta' WHERE id_notificacion = :n");
        $marcar->execute([":n" => $id_noti]);

        $conexion->commit();
        volver("ok=cambio");
    }

    if ($accion === "reembolsar") {
        $boleto_consulta = $conexion->prepare(
            "SELECT b.id_funcion, b.id_asiento, b.id_forma_pago, f.precio
             FROM boletos b
             JOIN funciones f ON f.id_funcion = b.id_funcion
             WHERE b.id_boletos = :b AND b.id_usuario = :u
             FOR UPDATE"
        );
        $boleto_consulta->execute([":b" => $id_boleto, ":u" => $usuario]);
        $boleto = $boleto_consulta->fetch();

        if (!$boleto) {
            throw new RuntimeException("El boleto ya no existe.");
        }

        // Registrar el reembolso antes de borrar el boleto
        $reembolso = $conexion->prepare(
            "INSERT INTO reembolsos (id_usuario, id_funcion, id_asiento, id_forma_pago, monto, motivo)
             VALUES (:u, :f, :a, :p, :m, 'asiento_mantenimiento')"
        );
        $reembolso->execute([
            ":u" => $usuario,
            ":f" => $boleto["id_funcion"],
            ":a" => $boleto["id_asiento"],
            ":p" => $boleto["id_forma_pago"],
            ":m" => $boleto["precio"],
        ]);

        $marcar = $conexion->prepare("UPDATE notificaciones SET estado = 'resuelta' WHERE id_notificacion = :n");
        $marcar->execute([":n" => $id_noti]);

        // Permite cancelar aunque falten menos de 2 horas. Solo dura esta transacción.
        $conexion->query("SELECT set_config('app.motivo_cancelacion', 'mantenimiento', true)");

        $borrar = $conexion->prepare("DELETE FROM boletos WHERE id_boletos = :b");
        $borrar->execute([":b" => $id_boleto]);

        $conexion->commit();
        volver("ok=reembolso");
    }

    throw new RuntimeException("Acción no válida.");

} catch (PDOException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    $mensaje = $e->getCode() === "23505"
        ? "Ese asiento ya fue tomado por otra persona."
        : mensaje_db($e, "No se pudo completar la acción.");
    volver("error=" . urlencode($mensaje));

} catch (RuntimeException $e) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    volver("error=" . urlencode($e->getMessage()));
}