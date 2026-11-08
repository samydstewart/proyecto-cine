<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cine Colombia | Tu cine favorito</title>

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
        }

        /* BARRA DE NAVEGACION */

        header {
            background-color: #080d18;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f5c400;
        }

        .logo {
            font-size: 27px;
            font-weight: bold;
            color: #f5c400;
        }

        nav {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 15px;
            transition: 0.3s;
        }

        nav a:hover {
            color: #f5c400;
        }

        .botones a {
            padding: 11px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin-left: 8px;
        }

        .login {
            color: white;
            border: 1px solid white;
        }

        .registro {
            background-color: #f5c400;
            color: #101522;
        }

        /* PORTADA PRINCIPAL */

        .portada {
            min-height: 420px;
            padding: 80px 8%;
            display: flex;
            align-items: center;
            background:
                linear-gradient(90deg, #101522 35%, rgba(16,21,34,0.5)),
                linear-gradient(135deg, #162d54, #101522);
        }

        .portada-contenido {
            max-width: 600px;
        }

        .portada h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .portada h1 span {
            color: #f5c400;
        }

        .portada p {
            color: #c5cad5;
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .boton-peliculas {
            display: inline-block;
            background-color: #f5c400;
            color: #101522;
            padding: 14px 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        /* SECCION DE PELICULAS */

        .seccion {
            padding: 55px 7%;
        }

        .titulo-seccion {
            font-size: 28px;
            margin-bottom: 30px;
            border-left: 5px solid #f5c400;
            padding-left: 15px;
        }

        .peliculas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
        }

        .pelicula {
            background-color: #1b2435;
            border-radius: 10px;
            overflow: hidden;
            transition: 0.3s;
        }

        .pelicula:hover {
            transform: translateY(-7px);
        }

        .poster {
            height: 280px;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
            font-size: 25px;
            font-weight: bold;
        }

        .poster1 {
            background: linear-gradient(135deg, #47234f, #bd4561);
        }

        .poster2 {
            background: linear-gradient(135deg, #183e67, #398db0);
        }

        .poster3 {
            background: linear-gradient(135deg, #613a19, #d18c31);
        }

        .poster4 {
            background: linear-gradient(135deg, #263b27, #70944d);
        }

        .info-pelicula {
            padding: 18px;
        }

        .info-pelicula h3 {
            margin-bottom: 10px;
        }

        .info-pelicula p {
            color: #aeb6c5;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .info-pelicula a {
            display: block;
            text-align: center;
            background-color: #f5c400;
            color: #101522;
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
        }

        /* PROXIMOS ESTRENOS */

        .estrenos {
            background-color: #080d18;
            padding: 55px 7%;
            text-align: center;
        }

        .estrenos h2 {
            font-size: 30px;
            margin-bottom: 15px;
            color: #f5c400;
        }

        .estrenos p {
            color: #c5cad5;
            font-size: 17px;
        }

        /* PIE DE PAGINA */

        footer {
            background-color: #080d18;
            padding: 25px;
            text-align: center;
            color: #929bab;
            border-top: 1px solid #293246;
        }

        /* DISEÑO PARA CELULARES */

        @media (max-width: 768px) {

            header {
                flex-direction: column;
                gap: 20px;
            }

            nav {
                flex-wrap: wrap;
                justify-content: center;
            }

            .portada h1 {
                font-size: 35px;
            }

            .portada {
                padding: 60px 7%;
            }

            .botones {
                display: flex;
                gap: 8px;
            }
        }

    </style>
</head>

<body>

    <!-- BARRA DE NAVEGACION -->

    <header>

        <div class="logo">
            CINE COLOMBIA
        </div>

        <nav>
            <a href="index.php">Inicio</a>
            <a href="peliculas.php">Películas</a>
            <a href="#estrenos">Estrenos</a>
        </nav>

        <div class="botones">
            <a href="login.php" class="login">Iniciar sesión</a>
            <a href="registro.php" class="registro">Registrarse</a>
        </div>

    </header>


    <!-- PORTADA PRINCIPAL -->

    <section class="portada">

        <div class="portada-contenido">

            <h1>
                Vive la magia del <span>cine</span>
            </h1>

            <p>
                Disfruta de las mejores películas, consulta nuestra
                cartelera y compra tus tiquetes de manera rápida y sencilla.
            </p>

            <a href="peliculas.php" class="boton-peliculas">
                Explorar películas
            </a>

        </div>

    </section>


    <!-- PELICULAS DESTACADAS -->

    <section class="seccion">

        <h2 class="titulo-seccion">
            Películas destacadas
        </h2>

        <div class="peliculas">

            <div class="pelicula">

                <div class="poster poster1">
                    PELÍCULA 1
                </div>

                <div class="info-pelicula">
                    <h3>Película destacada 1</h3>
                    <p>Consulta próximamente su información.</p>
                    <a href="peliculas.php">Ver película</a>
                </div>

            </div>


            <div class="pelicula">

                <div class="poster poster2">
                    PELÍCULA 2
                </div>

                <div class="info-pelicula">
                    <h3>Película destacada 2</h3>
                    <p>Consulta próximamente su información.</p>
                    <a href="peliculas.php">Ver película</a>
                </div>

            </div>


            <div class="pelicula">

                <div class="poster poster3">
                    PELÍCULA 3
                </div>

                <div class="info-pelicula">
                    <h3>Película destacada 3</h3>
                    <p>Consulta próximamente su información.</p>
                    <a href="peliculas.php">Ver película</a>
                </div>

            </div>


            <div class="pelicula">

                <div class="poster poster4">
                    PELÍCULA 4
                </div>

                <div class="info-pelicula">
                    <h3>Película destacada 4</h3>
                    <p>Consulta próximamente su información.</p>
                    <a href="peliculas.php">Ver película</a>
                </div>

            </div>

        </div>

    </section>


    <!-- PROXIMOS ESTRENOS -->

    <section class="estrenos" id="estrenos">

        <h2>Próximos estrenos</h2>

        <p>
            Prepárate para disfrutar de los mejores estrenos
            en nuestras salas de cine.
        </p>

    </section>


    <!-- PIE DE PAGINA -->

    <footer>

        <p>
            © 2026 Cine Colombia - Sistema de Gestión de Películas.
        </p>

    </footer>

</body>
</html>
