<?php
session_start();
require_once "includes/conexion.php";
require_once "includes/seguridad.php";
require_once "includes/validaciones.php";
requerir_sesion();

const REGEX_APELLIDO = '/^\p{L}+(?: \p{L}+)*$/u';

$usuario_id = (int) $_SESSION["id_usuario"];
$error = "";

$aviso = match ($_GET["ok"] ?? "") {
    "datos"      => "Tus datos se actualizaron correctamente.",
    "contrasena" => "Tu contraseña se cambió correctamente.",
    default      => "",
};

try {
    $consulta = $conexion->prepare(
        "SELECT nomb1_u, nomb2_u, apel_u, correo_u, tel_u
         FROM usuarios WHERE id_usuario = :u"
    );
    $consulta->execute([":u" => $usuario_id]);
    $perfil = $consulta->fetch();

    if (!$perfil) {
        mostrar_error_servidor("Tu cuenta ya no existe.", 404);
    }
} catch (PDOException $e) {
    mostrar_error_servidor(mensaje_db($e, "No se pudo cargar tu perfil."), 500);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    if (!csrf_valido()) {
        $error = "La sesión del formulario expiró. Recarga la página.";

    } elseif ($accion === "datos") {
        $nombre   = texto_limpio($_POST["nombre"] ?? "");
        $segundo  = texto_limpio($_POST["segundo_nombre"] ?? "");
        $apellido = texto_limpio($_POST["apellido"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $correo   = strtolower(trim($_POST["correo"] ?? ""));

        if ($nombre === "" || mb_strlen($nombre) > 20 || !preg_match('/^\p{L}+$/u', $nombre)) {
            $error = "El primer nombre solo puede tener letras y máximo 20 caracteres.";
        } elseif ($segundo !== "" && (mb_strlen($segundo) > 20 || !preg_match('/^\p{L}+$/u', $segundo))) {
            $error = "El segundo nombre solo puede tener letras y máximo 20 caracteres.";
        } elseif ($apellido === "" || mb_strlen($apellido) > 20 || !preg_match(REGEX_APELLIDO, $apellido)) {
            $error = "Los apellidos solo pueden tener letras y espacios, máximo 20 caracteres.";
        } elseif (!preg_match('/^\d{10}$/', $telefono)) {
            $error = "El teléfono debe tener exactamente 10 dígitos.";
        } elseif (mb_strlen($correo) > 100 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo electrónico no es válido.";
        } else {
            try {
                $actualizar = $conexion->prepare(
                    "UPDATE usuarios
                     SET nomb1_u = :n, nomb2_u = :s, apel_u = :a, tel_u = :t, correo_u = :c
                     WHERE id_usuario = :u"
                );
                $actualizar->execute([
                    ":n" => $nombre,
                    ":s" => $segundo !== "" ? $segundo : null,
                    ":a" => $apellido,
                    ":t" => $telefono,
                    ":c" => $correo,
                    ":u" => $usuario_id,
                ]);

                // Mantener el saludo y el correo de la sesión al día
                $_SESSION["nombre"] = $nombre;
                $_SESSION["correo"] = $correo;

                header("Location: mi-perfil.php?ok=datos");
                exit();

            } catch (PDOException $e) {
                if ($e->getCode() === "23505") {
                    $error = str_contains($e->getMessage(), "correo_u")
                        ? "Ese correo ya está en uso por otra cuenta."
                        : "Ese teléfono ya está en uso por otra cuenta.";
                } else {
                    $error = mensaje_db($e, "No se pudieron guardar tus datos.");
                }
            }
        }

    } elseif ($accion === "contrasena") {
        $actual     = $_POST["actual"] ?? "";
        $nueva      = $_POST["nueva"] ?? "";
        $confirmar  = $_POST["confirmar"] ?? "";
        $largo      = mb_strlen($nueva);

        if ($actual === "") {
            $error = "Escribe tu contraseña actual.";
        } elseif ($largo < 8 || $largo > 72 || !preg_match('/\p{L}/u', $nueva) || !preg_match('/\d/', $nueva)) {
            $error = "La nueva contraseña debe tener entre 8 y 72 caracteres, con al menos una letra y un número.";
        } elseif ($nueva !== $confirmar) {
            $error = "La confirmación no coincide con la nueva contraseña.";
        } else {
            try {
                $consulta = $conexion->prepare(
                    'SELECT "contraseña_u" FROM usuarios WHERE id_usuario = :u'
                );
                $consulta->execute([":u" => $usuario_id]);
                $hash = $consulta->fetchColumn();

                if (!password_verify($actual, $hash)) {
                    $error = "La contraseña actual no es correcta.";
                } elseif (password_verify($nueva, $hash)) {
                    $error = "La nueva contraseña debe ser distinta de la actual.";
                } else {
                    $actualizar = $conexion->prepare(
                        'UPDATE usuarios SET "contraseña_u" = :h WHERE id_usuario = :u'
                    );
                    $actualizar->execute([
                        ":h" => password_hash($nueva, PASSWORD_DEFAULT),
                        ":u" => $usuario_id,
                    ]);

                    // Cambia el identificador de sesión después de un cambio sensible
                    session_regenerate_id(true);

                    header("Location: mi-perfil.php?ok=contrasena");
                    exit();
                }
            } catch (PDOException $e) {
                $error = mensaje_db($e, "No se pudo cambiar la contraseña.");
            }
        }
    }
}

// Si hubo error, se muestran los valores que escribió el usuario; si no, los de la base
$v_nombre   = $_POST["nombre"]         ?? $perfil["nomb1_u"];
$v_segundo  = $_POST["segundo_nombre"] ?? ($perfil["nomb2_u"] ?? "");
$v_apellido = $_POST["apellido"]       ?? $perfil["apel_u"];
$v_telefono = $_POST["telefono"]       ?? $perfil["tel_u"];
$v_correo   = $_POST["correo"]         ?? $perfil["correo_u"];

$titulo = "Mi perfil | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina">

    <h2 class="titulo-seccion">Mi perfil</h2>

    <?php if ($aviso !== ""): ?>
        <p class="alerta alerta-exito"><?= htmlspecialchars($aviso) ?></p>
    <?php endif; ?>
    <?php if ($error !== ""): ?>
        <p class="alerta"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <div class="pagina-perfil">

        <section class="auth-box">
            <h3 class="subtitulo">Datos personales</h3>
            <form method="POST" action="mi-perfil.php" novalidate>
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="datos">

                <div class="filter-group">
                    <label for="nombre">Primer nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="20" required
                        value="<?= htmlspecialchars($v_nombre) ?>">
                </div>

                <div class="filter-group">
                    <label for="segundo_nombre">Segundo nombre <small>(opcional)</small></label>
                    <input type="text" id="segundo_nombre" name="segundo_nombre" maxlength="20"
                        value="<?= htmlspecialchars($v_segundo) ?>">
                </div>

                <div class="filter-group">
                    <label for="apellido">Apellidos</label>
                    <input type="text" id="apellido" name="apellido" maxlength="20" required
                        value="<?= htmlspecialchars($v_apellido) ?>">
                </div>

                <div class="filter-group">
                    <label for="telefono">Teléfono (10 dígitos)</label>
                    <input type="tel" id="telefono" name="telefono" maxlength="10" required
                        pattern="[0-9]{10}" inputmode="numeric"
                        value="<?= htmlspecialchars($v_telefono) ?>">
                </div>

                <div class="filter-group">
                    <label for="correo">Correo electrónico</label>
                    <input type="email" id="correo" name="correo" maxlength="100" required
                        value="<?= htmlspecialchars($v_correo) ?>">
                </div>

                <button type="submit" class="btn-filter">Guardar datos</button>
            </form>
        </section>

        <section class="auth-box">
            <h3 class="subtitulo">Cambiar contraseña</h3>
            <form method="POST" action="mi-perfil.php" novalidate>
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="contrasena">

                <div class="filter-group">
                    <label for="actual">Contraseña actual</label>
                    <input type="password" id="actual" name="actual" required>
                </div>

                <div class="filter-group">
                    <label for="nueva">Nueva contraseña</label>
                    <input type="password" id="nueva" name="nueva" minlength="8" maxlength="72" required
                        placeholder="Mínimo 8 caracteres, con letra y número">
                </div>

                <div class="filter-group">
                    <label for="confirmar">Confirmar nueva contraseña</label>
                    <input type="password" id="confirmar" name="confirmar" maxlength="72" required>
                </div>

                <button type="submit" class="btn-filter">Cambiar contraseña</button>
            </form>
        </section>

    </div>

</main>

<?php require 'includes/footer.php'; ?>