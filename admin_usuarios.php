
<?php

require_once "includes/verificar_admin.php";

// Consultar todos los usuarios y sus respectivos roles
$sql = "SELECT
            u.id_usuario,
            u.nomb1_u,
            u.nomb2_u,
            u.apel_u,
            u.correo_u,
            u.tel_u,
            r.nomb_rol
        FROM usuarios u
        INNER JOIN roles r ON u.id_rol = r.id_rol
        ORDER BY u.id_usuario ASC";

$consulta = $conexion->prepare($sql);
$consulta->execute();

$usuarios = $consulta->fetchAll(PDO::FETCH_ASSOC);

$titulo = "Administrar usuarios | Cine Colombia";

require "includes/header.php";

?>

<main class="pagina">

    <h2>Administración de usuarios</h2>

    <p>Desde aquí puedes consultar y administrar los usuarios registrados.</p>

    <?php if (isset($_GET["mensaje"])): ?>
        <p class="alerta">
            <?= htmlspecialchars($_GET["mensaje"]) ?>
        </p>
    <?php endif; ?>

    <div style="overflow-x: auto;">

        <table style="width: 100%; border-collapse: collapse;">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($usuarios as $usuario): ?>

                    <tr>
                        <td><?= htmlspecialchars($usuario["id_usuario"]) ?></td>

                        <td>
                            <?= htmlspecialchars(
                                $usuario["nomb1_u"] . " " .
                                ($usuario["nomb2_u"] ?? "")
                            ) ?>
                        </td>

                        <td><?= htmlspecialchars($usuario["apel_u"]) ?></td>

                        <td><?= htmlspecialchars($usuario["correo_u"]) ?></td>

                        <td><?= htmlspecialchars($usuario["tel_u"]) ?></td>

                        <td><?= htmlspecialchars($usuario["nomb_rol"]) ?></td>

                        <td>

                            <?php if ($usuario["id_usuario"] != $_SESSION["id_usuario"]): ?>

                                <form action="eliminar_usuario.php"
                                      method="POST"
                                      onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">

                                    <input type="hidden"
                                           name="id_usuario"
                                           value="<?= htmlspecialchars($usuario["id_usuario"]) ?>">

                                    <button type="submit">
                                        Eliminar
                                    </button>

                                </form>

                            <?php else: ?>

                                <span>Tu cuenta</span>

                            <?php endif; ?>

                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (empty($usuarios)): ?>

                    <tr>
                        <td colspan="7">No hay usuarios registrados.</td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

<?php require "includes/footer.php"; ?>
```
