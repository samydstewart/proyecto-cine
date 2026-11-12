<?php
session_start();
require_once "includes/conexion.php";
require_once "includes/seguridad.php";
requerir_admin();

$aviso = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id_usuario", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

    if (!csrf_valido()) {
        $error = "La sesión del formulario expiró. Recarga la página.";
    } elseif (!$id) {
        $error = "Usuario no válido.";
    } elseif ($id === (int) $_SESSION["id_usuario"]) {
        $error = "No puedes eliminar tu propia cuenta.";
    } else {
        try {
            // Un usuario con boletos o reembolsos no se puede borrar (conserva el historial)
            $conteo = $conexion->prepare(
                "SELECT (SELECT COUNT(*) FROM boletos WHERE id_usuario = :u1)
                      + (SELECT COUNT(*) FROM reembolsos WHERE id_usuario = :u2)"
            );
            $conteo->execute([":u1" => $id, ":u2" => $id]);

            if ((int) $conteo->fetchColumn() > 0) {
                $error = "Este usuario tiene boletos o reembolsos y no se puede eliminar.";
            } else {
                $borrar = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = :u");
                $borrar->execute([":u" => $id]);
                $aviso = "Usuario eliminado correctamente.";
            }
        } catch (PDOException $e) {
            $error = mensaje_db($e, "No se pudo eliminar el usuario.");
        }
    }
}

$usuarios = $conexion->query(
    "SELECT u.id_usuario, u.nomb1_u, u.apel_u, u.correo_u, u.tel_u, r.nomb_rol
     FROM usuarios u
     JOIN roles r ON r.id_rol = u.id_rol
     ORDER BY u.id_usuario"
)->fetchAll();

$titulo = "Administrar usuarios | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Administrar usuarios</h2>
    <?php require 'includes/admin_menu.php'; ?>

    <?php if ($aviso !== ""): ?>
        <p class="alerta alerta-exito"><?= htmlspecialchars($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="alerta"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <table class="tabla">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Rol</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= (int) $u["id_usuario"] ?></td>
                    <td><?= htmlspecialchars($u["nomb1_u"] . " " . $u["apel_u"]) ?></td>
                    <td><?= htmlspecialchars($u["correo_u"]) ?></td>
                    <td><?= htmlspecialchars($u["tel_u"]) ?></td>
                    <td><?= htmlspecialchars($u["nomb_rol"]) ?></td>
                    <td>
                        <form method="POST" action="admin_usuarios.php"
                              onsubmit="return confirm('¿Eliminar este usuario?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id_usuario" value="<?= (int) $u["id_usuario"] ?>">
                            <button type="submit" class="btn-peligro">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</main>

<?php require 'includes/footer.php'; ?>