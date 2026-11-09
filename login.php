<?php

session_start();

require_once "includes/conexion.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $correo = trim($_POST["correo"]);
    $contrasena = $_POST["contrasena"];

    try {

        // Buscar el usuario por su correo
        $sql = "SELECT * FROM Usuarios WHERE correo_u = :correo";

        $consulta = $conexion->prepare($sql);

        $consulta->execute([
            ":correo" => $correo
        ]);

        $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

        // Verificar si existe y si la contraseña es correcta
        if ($usuario && password_verify(
            $contrasena,
            $usuario["contraseña_u"]
        )) {
            // Protege la sesion del usuario
            session_regenerate_id(true);
            // Guardar información del usuario en la sesión
            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            $_SESSION["nombre"] = $usuario["nomb1_u"];
            $_SESSION["correo"] = $usuario["correo_u"];
            $_SESSION["id_rol"] = $usuario["id_rol"];

            header("Location: comprar.php");
            exit();

        } else {

            $mensaje = "Correo o contraseña incorrectos.";

        }

    } catch (PDOException $error) {

        error_log($error->getMessage());

        $mensaje = "Ocurrió un error al intentar iniciar sesión.";

    }
}

$titulo = "Iniciar sesión | Cine Colombia";
require 'includes/header.php';
?>

<main class="pagina pagina-centrada">
    <div class="auth-box">

        <h2>Bienvenido nuevamente</h2>
        <p class="descripcion">Ingresa a tu cuenta para continuar.</p>

        <?php if (!empty($mensaje)): ?>
            <p class="alerta"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">

            <div class="filter-group">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo"
                       placeholder="ejemplo@correo.com" required>
            </div>

            <div class="filter-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena"
                       placeholder="Ingresa tu contraseña" required>
            </div>

            <button type="submit" class="btn-filter">Iniciar sesión</button>

        </form>

        <p class="enlace">
            ¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>
        </p>

    </div>
</main>

<?php require 'includes/footer.php'; ?>