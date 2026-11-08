<?php

session_start();

require_once "conexion.php";

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

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Iniciar sesión | Cine Colombia</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #101522;
            color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .contenedor {
            background-color: #1b2435;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }

        .logo {
            text-align: center;
            color: #f5c400;
            font-size: 27px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        .descripcion {
            text-align: center;
            color: #aeb6c5;
            margin-bottom: 30px;
        }

        .campo {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #39465d;
            border-radius: 6px;
            background-color: #101522;
            color: white;
            outline: none;
        }

        input:focus {
            border-color: #f5c400;
        }

        button {
            width: 100%;
            padding: 13px;
            background-color: #f5c400;
            color: #101522;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background-color: #dfb400;
        }

        .enlace {
            text-align: center;
            margin-top: 25px;
            color: #aeb6c5;
            font-size: 14px;
        }

        .enlace a {
            color: #f5c400;
            text-decoration: none;
        }

        .volver {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .volver:hover {
            color: #f5c400;
        }

    </style>

</head>

<body>

    <div class="contenedor">

        <div class="logo">
            CINE COLOMBIA
        </div>

        <h2>Bienvenido nuevamente</h2>

        <p class="descripcion">
            Ingresa a tu cuenta para continuar.
        </p>

        <form action="login.php" method="POST">
            <?php if (!empty($mensaje)): ?>
            <p>
                <?php echo htmlspecialchars($mensaje); ?>
            </p>
            <?php endif; ?>

            <div class="campo">

                <label for="correo">Correo electrónico</label>

                <input
                    type="email"
                    id="correo"
                    name="correo"
                    placeholder="ejemplo@correo.com"
                    required
                >

            </div>

            <div class="campo">

                <label for="contrasena">Contraseña</label>

                <input
                    type="password"
                    id="contrasena"
                    name="contrasena"
                    placeholder="Ingresa tu contraseña"
                    required
                >

            </div>

            <button type="submit">
                Iniciar sesión
            </button>

        </form>

        <div class="enlace">

            ¿No tienes una cuenta?

            <a href="registro.php">
                Regístrate aquí
            </a>

        </div>

        <a href="index.php" class="volver">
            ← Volver al inicio
        </a>

    </div>

</body>

</html>
