<?php

require_once __DIR__ . "/verificar_admin.php";

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

require __DIR__ . "/includes/header.php";

?>

<style>
/* CONTENEDOR PRINCIPAL */
.admin-contenedor {
    max-width: 1200px;
    margin: 45px auto;
    padding: 30px;
    color: #ffffff;
}

/* ENCABEZADO */
.admin-encabezado {
    margin-bottom: 30px;
}

.admin-encabezado h2 {
    font-size: 32px;
    margin-bottom: 10px;
    color: #ffffff;
}

.admin-encabezado p {
    color: #bdbdbd;
    font-size: 15px;
}

/* TARJETA DE RESUMEN */
.admin-resumen {
    background: linear-gradient(135deg, #252525, #171717);
    border-left: 5px solid #e50914;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.admin-resumen span {
    color: #bdbdbd;
    font-size: 14px;
}

.admin-resumen h3 {
    font-size: 35px;
    margin: 8px 0 0;
    color: #ffffff;
}

/* MENSAJES */
.admin-alerta {
    background: #263c2d;
    border: 1px solid #3d8050;
    color: #a8e6b5;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
}

/* TABLA */
.admin-tabla-contenedor {
    background: #1c1c1c;
    border-radius: 12px;
    overflow-x: auto;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}

.admin-tabla {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.admin-tabla thead {
    background: #e50914;
}

.admin-tabla th {
    padding: 17px 15px;
    text-align: left;
    color: white;
    font-size: 14px;
}

.admin-tabla td {
    padding: 16px 15px;
    border-bottom: 1px solid #333333;
    color: #e0e0e0;
    font-size: 14px;
}

.admin-tabla tbody tr {
    transition: background 0.2s;
}

.admin-tabla tbody tr:hover {
    background: #292929;
}

/* IDENTIFICADOR */
.admin-id {
    color: #e50914;
    font-weight: bold;
}

/* ROLES */
.admin-rol {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    background: #343434;
    color: #ffffff;
}

.rol-administrador {
    background: #572126;
    color: #ff858b;
}

.rol-cliente {
    background: #173e50;
    color: #8bd7f5;
}

.rol-taquillero {
    background: #51421c;
    color: #f5d77c;
}

/* BOTÓN ELIMINAR */
.admin-boton-eliminar {
    background: #e50914;
    color: white;
    border: none;
    padding: 9px 15px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.2s;
}

.admin-boton-eliminar:hover {
    background: #b80710;
    transform: scale(1.04);
}

/* CUENTA ACTUAL */
.admin-cuenta-actual {
    color: #72d58a;
    font-size: 13px;
    font-weight: bold;
}

/* BUSCADOR */
.admin-buscador {
    width: 100%;
    max-width: 350px;
    padding: 12px 15px;
    margin-bottom: 20px;
    background: #252525;
    border: 1px solid #444;
    border-radius: 8px;
    color: white;
    outline: none;
}

.admin-buscador:focus {
    border-color: #e50914;
}

/* TABLA VACÍA */
.admin-vacio {
    text-align: center;
    padding: 30px !important;
    color: #aaa !important;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .admin-contenedor {
        padding: 15px;
        margin: 25px auto;
    }

    .admin-encabezado h2 {
        font-size: 25px;
    }

    .admin-resumen h3 {
        font-size: 28px;
    }
}
</style>

<main class="admin-contenedor">

    <div class="admin-encabezado">
        <h2>Administración de usuarios</h2>
        <p>Gestiona y consulta los usuarios registrados en Cine Colombia.</p>
    </div>

    <div class="admin-resumen">
        <span>Total de usuarios registrados</span>
        <h3><?= count($usuarios) ?></h3>
    </div>

    <?php if (isset($_GET["mensaje"])): ?>
        <div class="admin-alerta">
            <?= htmlspecialchars($_GET["mensaje"], ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <input
        type="text"
        id="buscadorUsuarios"
        class="admin-buscador"
        placeholder="Buscar usuario por nombre o correo..."
    >

    <div class="admin-tabla-contenedor">

        <table class="admin-tabla">

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

            <tbody id="tablaUsuarios">

                <?php foreach ($usuarios as $usuario): ?>

                    <tr>
                        <td class="admin-id">
                            #<?= htmlspecialchars($usuario["id_usuario"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $usuario["nomb1_u"] . " " .
                                ($usuario["nomb2_u"] ?? ""),
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($usuario["apel_u"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($usuario["correo_u"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($usuario["tel_u"]) ?>
                        </td>

                        <td>
                            <?php
                            $claseRol = "";

                            if ($usuario["nomb_rol"] === "Administrador") {
                                $claseRol = "rol-administrador";
                            } elseif ($usuario["nomb_rol"] === "Cliente") {
                                $claseRol = "rol-cliente";
                            } elseif ($usuario["nomb_rol"] === "Taquillero") {
                                $claseRol = "rol-taquillero";
                            }
                            ?>

                            <span class="admin-rol <?= $claseRol ?>">
                                <?= htmlspecialchars($usuario["nomb_rol"]) ?>
                            </span>
                        </td>

                        <td>

                            <?php if ($usuario["id_usuario"] != $_SESSION["id_usuario"]): ?>

                                <form action="eliminar_usuarios.php"
                                      method="POST"
                                      onsubmit="return confirm('¿Estás seguro de que deseas eliminar este usuario?');">

                                    <input type="hidden"
                                           name="id_usuario"
                                           value="<?= htmlspecialchars($usuario["id_usuario"]) ?>">

                                    <button type="submit" class="admin-boton-eliminar">
                                        Eliminar
                                    </button>

                                </form>

                            <?php else: ?>

                                <span class="admin-cuenta-actual">
                                    ✓ Tu cuenta
                                </span>

                            <?php endif; ?>

                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (empty($usuarios)): ?>

                    <tr>
                        <td colspan="7" class="admin-vacio">
                            No hay usuarios registrados.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

<script>
// Buscador de usuarios
document.getElementById("buscadorUsuarios").addEventListener("keyup", function() {

    let texto = this.value.toLowerCase();

    let filas = document.querySelectorAll("#tablaUsuarios tr");

    filas.forEach(function(fila) {

        let contenido = fila.textContent.toLowerCase();

        fila.style.display = contenido.includes(texto) ? "" : "none";

    });

});
</script>

<?php require __DIR__ . "/includes/footer.php"; ?>
