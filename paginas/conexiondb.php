<?php

$conexion = pg_connect("
host=ep-sparkling-poetry-b8020onr-pooler.c-14.us-east-1.aws.neon.tech
port=5432
dbname=neondb
user=neondb_owner
password=npg_VC0AUPTWBo9c
sslmode=require
");

if (!$conexion) {
    die("Error conectando con Neon");
}