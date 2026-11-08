<?php

require_once "conexion.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $segundo_nombre = trim($_POST["segundo_nombre"]);
    $apellido = trim($_POST["apellido"]);
    $correo = trim($_POST["correo"]);
    $telefono = trim($_POST["telefono"]);
    $contrasena = $_POST["contrasena"];
    $confirmar = $_POST["confirmar"];

    // Verificar que las contraseñas coincidan
    if ($contrasena !== $confirmar) {

        $mensaje = "Las contraseñas no coinciden.";

    } else {

        try {

            // Buscar el rol correspondiente al usuario
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

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Crear cuenta | Cine Colombia</title>

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
            padding: 30px 15px;
        }

        .contenedor {
            background-color: #1b2435;
            width: 100%;
            max-width: 500px;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }

        .logo {
            text-align: center;
            color: #f5c400;
            font-size: 27px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        .descripcion {
            text-align: center;
            color: #aeb6c5;
            margin-bottom: 25px;
        }

        .campo {
            margin-bottom: 17px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px;
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
            margin-top: 10px;
        }

        button:hover {
            background-color: #dfb400;
        }

        .enlace {
            text-align: center;
            margin-top: 22px;
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

        <h2>Crear una cuenta</h2>

        <p class="descripcion">
            Regístrate y disfruta de la experiencia del cine.
        </p>

        <form action="#" method="POST">

            <div class="campo">
                <label for="nombre">Primer nombre</label>
                <input type="text" id="nombre" name="nombre"
                       placeholder="Ingresa tu primer nombre" required>
            </div>

            <div class="campo">
                <label for="segundo_nombre">Segundo nombre</label>
                <input type="text" id="segundo_nombre" name="segundo_nombre"
                       placeholder="Ingresa tu segundo nombre">
            </div>

            <div class="campo">
                <label for="apellido">Apellidos</label>
                <input type="text" id="apellido" name="apellido"
                       placeholder="Ingresa tus apellidos" required>
            </div>

            <div class="campo">
                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" name="correo"
                       placeholder="ejemplo@correo.com" required>
            </div>

            <div class="campo">
                <label for="telefono">Número de teléfono</label>
                <input type="tel" id="telefono" name="telefono"
                       placeholder="Ingresa tu teléfono" required>
            </div>

            <div class="campo">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena"
                       placeholder="Crea una contraseña" required>
            </div>

            <div class="campo">
                <label for="confirmar">Confirmar contraseña</label>
                <input type="password" id="confirmar" name="confirmar"
                       placeholder="Repite tu contraseña" required>
            </div>

            <button type="submit">
                Crear cuenta
            </button>

        </form>

        <div class="enlace">
            ¿Ya tienes una cuenta?
            <a href="login.php">Inicia sesión</a>
        </div>

        <a href="index.php" class="volver">
            ← Volver al inicio
        </a>

    </div>

</body>

</html>
