<?php
require_once "includes/conexion.php";

$mensaje = "";

/**
 * Valida los datos del formulario de registro.
 * Devuelve el mensaje de error, o null si todo es válido.
 */
function validar_registro(array $d): ?string
{
    // Campos obligatorios
    if ($d["nombre"] === "" || $d["apellido"] === "" || $d["telefono"] === ""
        || $d["correo"] === "" || $d["contrasena"] === "") {
        return "Completa todos los campos obligatorios.";
    }

    // Primer nombre: solo letras (incluye tildes y ñ), máximo 20
    if (mb_strlen($d["nombre"]) > 20 || !preg_match('/^\p{L}+$/u', $d["nombre"])) {
        return "El primer nombre solo puede tener letras y máximo 20 caracteres.";
    }

    // Segundo nombre: opcional, mismas reglas
    if ($d["segundo"] !== "" && (mb_strlen($d["segundo"]) > 20 || !preg_match('/^\p{L}+$/u', $d["segundo"]))) {
        return "El segundo nombre solo puede tener letras y máximo 20 caracteres.";
    }

    // Apellidos: letras con espacios simples entre palabras (ej. "De la Cruz"), máximo 20
    if (mb_strlen($d["apellido"]) > 20 || !preg_match('/^\p{L}+(?: \p{L}+)*$/u', $d["apellido"])) {
        return "Los apellidos solo pueden tener letras y espacios, con máximo 20 caracteres.";
    }

    // Teléfono: exactamente 10 dígitos
    if (!preg_match('/^\d{10}$/', $d["telefono"])) {
        return "El teléfono debe tener exactamente 10 dígitos, sin letras ni símbolos.";
    }

    // Correo
    if (mb_strlen($d["correo"]) > 100 || !filter_var($d["correo"], FILTER_VALIDATE_EMAIL)) {
        return "El correo electrónico no es válido.";
    }

    // Contraseña: entre 8 y 72 caracteres (límite de bcrypt), con al menos una letra y un número
    $largo = mb_strlen($d["contrasena"]);
    if ($largo < 8 || $largo > 72) {
        return "La contraseña debe tener entre 8 y 72 caracteres.";
    }
    if (!preg_match('/\p{L}/u', $d["contrasena"]) || !preg_match('/\d/', $d["contrasena"])) {
        return "La contraseña debe tener al menos una letra y un número.";
    }
    if ($d["contrasena"] !== $d["confirmar"]) {
        return "Las contraseñas no coinciden.";
    }

    return null;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Limpiar espacios y dejar un solo espacio entre palabras
    $datos = [
        "nombre"     => preg_replace('/\s+/', ' ', trim($_POST["nombre"] ?? "")),
        "segundo"    => preg_replace('/\s+/', ' ', trim($_POST["segundo_nombre"] ?? "")),
        "apellido"   => preg_replace('/\s+/', ' ', trim($_POST["apellido"] ?? "")),
        "telefono"   => trim($_POST["telefono"] ?? ""),
        "correo"     => strtolower(trim($_POST["correo"] ?? "")),
        "contrasena" => $_POST["contrasena"] ?? "",
        "confirmar"  => $_POST["confirmar"] ?? "",
    ];

    $mensaje = validar_registro($datos) ?? "";

    if ($mensaje === "") {
        try {
            $consultaRol = $conexion->prepare("SELECT id_rol FROM roles WHERE nomb_rol = :rol");
            $consultaRol->execute([":rol" => "Cliente"]);
            $rol = $consultaRol->fetch();

            if (!$rol) {
                throw new RuntimeException("No existe el rol 'Cliente' en la tabla roles.");
            }

            $consulta = $conexion->prepare(
                "INSERT INTO usuarios
                    (nomb1_u, nomb2_u, apel_u, correo_u, contraseña_u, tel_u, id_rol)
                 VALUES
                    (:nombre, :segundo, :apellido, :correo, :password, :telefono, :rol)"
            );

            $consulta->execute([
                ":nombre"   => $datos["nombre"],
                ":segundo"  => $datos["segundo"] !== "" ? $datos["segundo"] : null,
                ":apellido" => $datos["apellido"],
                ":correo"   => $datos["correo"],
                ":password" => password_hash($datos["contrasena"], PASSWORD_DEFAULT),
                ":telefono" => $datos["telefono"],
                ":rol"      => $rol["id_rol"],
            ]);

            header("Location: login.php?registro=ok");
            exit();

        } catch (PDOException $error) {
            if ($error->getCode() === "23505") {
                // La restricción única indica qué campo se repitió
                $mensaje = str_contains($error->getMessage(), "correo_u")
                    ? "Ese correo ya está registrado."
                    : "Ese número de teléfono ya está registrado.";
            } else {
                error_log($error->getMessage());
                $mensaje = "Ocurrió un error al registrar el usuario. Intenta más tarde.";
            }
        } catch (RuntimeException $error) {
            error_log($error->getMessage());
            $mensaje = "No se pudo crear la cuenta. Contacta al administrador.";
        }
    }
}

$titulo = "Crear cuenta | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina pagina-centrada">
    <div class="auth-box">

        <h2>Crear una cuenta</h2>
        <p class="descripcion">Regístrate y disfruta de la experiencia del cine.</p>

        <?php if ($mensaje !== ""): ?>
            <p class="alerta"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <form action="registro.php" method="POST" novalidate>

            <div class="filter-group">
                <label for="nombre">Primer nombre</label>
                <input type="text" id="nombre" name="nombre" maxlength="20"
                    pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+" title="Solo letras, máximo 20"
                    value="<?= htmlspecialchars($_POST["nombre"] ?? "") ?>"
                    placeholder="Ingresa tu primer nombre" required>
            </div>

            <div class="filter-group">
                <label for="segundo_nombre">Segundo nombre</label>
                <input type="text" id="segundo_nombre" name="segundo_nombre" maxlength="20"
                    pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+" title="Solo letras, máximo 20"
                    value="<?= htmlspecialchars($_POST["segundo_nombre"] ?? "") ?>"
                    placeholder="Opcional">
            </div>

            <div class="filter-group">
                <label for="apellido">Apellidos</label>
                <input type="text" id="apellido" name="apellido" maxlength="20"
                    pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+( [A-Za-zÁÉÍÓÚáéíóúÑñÜü]+)*"
                    title="Solo letras y espacios, máximo 20"
                    value="<?= htmlspecialchars($_POST["apellido"] ?? "") ?>"
                    placeholder="Ingresa tus apellidos" required>
            </div>

            <div class="filter-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo" maxlength="100"
                    value="<?= htmlspecialchars($_POST["correo"] ?? "") ?>"
                    placeholder="ejemplo@correo.com" required>
            </div>

            <div class="filter-group">
                <label for="telefono">Número de teléfono</label>
                <input type="tel" id="telefono" name="telefono" maxlength="10"
                    pattern="[0-9]{10}" title="Exactamente 10 dígitos"
                    inputmode="numeric"
                    value="<?= htmlspecialchars($_POST["telefono"] ?? "") ?>"
                    placeholder="3001234567" required>
            </div>

            <div class="filter-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena"
                    minlength="8" maxlength="72"
                    placeholder="Mínimo 8 caracteres, con letra y número" required>
            </div>

            <div class="filter-group">
                <label for="confirmar">Confirmar contraseña</label>
                <input type="password" id="confirmar" name="confirmar"
                    maxlength="72" placeholder="Repite tu contraseña" required>
            </div>

            <button type="submit" class="btn-filter">Crear cuenta</button>

        </form>

        <p class="enlace">
            ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a>
        </p>

    </div>
</main>

<?php require 'includes/footer.php'; ?>