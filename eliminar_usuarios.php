```php
<?php

require_once "includes/verificar_admin.php";

// Comprobar que la solicitud sea POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_usuarios.php");
    exit();
}

// Obtener el ID del usuario que se quiere eliminar
$id_usuario = filter_input(INPUT_POST, "id_usuario", FILTER_VALIDATE_INT);

if (!$id_usuario) {
    header("Location: admin_usuarios.php?mensaje=" . urlencode("Usuario no válido."));
    exit();
}

// Evitar que el administrador elimine su propia cuenta
if ($id_usuario == $_SESSION["id_usuario"]) {
    header("Location: admin_usuarios.php?mensaje=" . urlencode("No puedes eliminar tu propia cuenta."));
    exit();
}

try {

    // Verificar que el usuario exista
    $sql = "SELECT id_usuario
            FROM usuarios
            WHERE id_usuario = :id_usuario";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":id_usuario" => $id_usuario
    ]);

    if (!$consulta->fetch()) {
        header("Location: admin_usuarios.php?mensaje=" . urlencode("El usuario no existe."));
        exit();
    }

    // Comprobar si tiene boletos registrados
    $sql = "SELECT COUNT(*)
            FROM boletos
            WHERE id_usuario = :id_usuario";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":id_usuario" => $id_usuario
    ]);

    $cantidad_boletos = $consulta->fetchColumn();

    if ($cantidad_boletos > 0) {
        header("Location: admin_usuarios.php?mensaje=" . urlencode("No se puede eliminar: el usuario tiene boletos registrados."));
        exit();
    }

    // Eliminar el usuario
    $sql = "DELETE FROM usuarios
            WHERE id_usuario = :id_usuario";

    $consulta = $conexion->prepare($sql);

    $consulta->execute([
        ":id_usuario" => $id_usuario
    ]);

    header("Location: admin_usuarios.php?mensaje=" . urlencode("Usuario eliminado correctamente."));
    exit();

} catch (PDOException $error) {

    error_log("Error al eliminar usuario: " . $error->getMessage());

    header("Location: admin_usuarios.php?mensaje=" . urlencode("Ocurrió un error al eliminar el usuario."));
    exit();
}

?>
```
