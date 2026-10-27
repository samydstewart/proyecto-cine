<?php
    $pagina_actual = basename($_SERVER['PHP_SELF']);
    function activo($archivo, $pagina_actual) {
        return $archivo === $pagina_actual ? ' activo' : '';
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>