<?php

require_once "includes/conexion.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $segundo_nombre = trim($_POST["segundo_nombre"]);
    $apellido = trim($_POST["apellido"]);
    $telefono = trim($_POST["telefono"]);
    $contrasena = $_POST["contrasena"];
    $confirmar = $_POST["confirmar"];
    $correo = trim($_POST["correo"]);
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo electrónico no es válido.";
    } elseif (strlen($contrasena) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($contrasena !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden.";
    } else {
        try {
            $consultaRol = $conexion->prepare(
                "SELECT id_rol FROM roles WHERE nomb_rol = :rol"
            );

            $consultaRol->execute([
                ":rol" => "Cliente"
            ]);

            $rol = $consultaRol->fetch(PDO::FETCH_ASSOC);

            if (!$rol) {

                $mensaje = "No se encontró el rol de usuario en la base de datos.";

            } else {

                // Encriptar la contraseña
                $passwordHash = password_hash(
                    $contrasena,
                    PASSWORD_DEFAULT
                );

                // Insertar el usuario
                $sql = "INSERT INTO Usuarios
                (nomb1_u, nomb2_u, apel_u, correo_u, contraseña_u, tel_u, id_rol)
                VALUES
                (:nombre, :segundo, :apellido, :correo, :password, :telefono, :rol)";

                $consulta = $conexion->prepare($sql);

                $consulta->execute([
                    ":nombre" => $nombre,
                    ":segundo" => $segundo_nombre ?: null,
                    ":apellido" => $apellido,
                    ":correo" => $correo,
                    ":password" => $passwordHash,
                    ":telefono" => $telefono,
                    ":rol" => $rol["id_rol"]
                ]);

                $mensaje = "¡Registro exitoso! Ya puedes iniciar sesión.";

            }

        } catch (PDOException $error) {

            if ($error->getCode() == "23505") {
                $mensaje = "El correo o teléfono ya se encuentra registrado.";
            } else {
                $mensaje = "Ocurrió un error al registrar el usuario.";
                error_log($error->getMessage());
            }

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

        <?php if (!empty($mensaje)): ?>
            <p class="alerta"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <form action="registro.php" method="POST">

            <div class="filter-group">
                <label for="nombre">Primer nombre</label>
                <input type="text" id="nombre" name="nombre"
                       placeholder="Ingresa tu primer nombre" required>
            </div>

            <div class="filter-group">
                <label for="segundo_nombre">Segundo nombre</label>
                <input type="text" id="segundo_nombre" name="segundo_nombre"
                       placeholder="Ingresa tu segundo nombre">
            </div>

            <div class="filter-group">
                <label for="apellido">Apellidos</label>
                <input type="text" id="apellido" name="apellido"
                       placeholder="Ingresa tus apellidos" required>
            </div>

            <div class="filter-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo"
                       placeholder="ejemplo@correo.com" required>
            </div>

            <div class="filter-group">
                <label for="telefono">Número de teléfono</label>
                <input type="tel" id="telefono" name="telefono"
                       placeholder="Ingresa tu teléfono" required>
            </div>

            <div class="filter-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena"
                       placeholder="Crea una contraseña" required>
            </div>

            <div class="filter-group">
                <label for="confirmar">Confirmar contraseña</label>
                <input type="password" id="confirmar" name="confirmar"
                       placeholder="Repite tu contraseña" required>
            </div>

            <button type="submit" class="btn-filter">Crear cuenta</button>

        </form>

        <p class="enlace">
            ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión</a>
        </p>

    </div>
</main>

<?php require 'includes/footer.php'; ?>