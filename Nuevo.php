<?php


if (isset($_GET["nombre"])) {
    $nombre = $_GET["nombre"];
    echo "Hola mundo tu te llmas :" . $nombre;
}

if (isset($_GET["GetCategorias"])) {
    //conecto a la base de datos
    echo '[{"id":"1","nombre":"helados"},{"id":"2","nombre":"cono"},{"id":"3","nombre":"litro"}]';
}
if (isset($_POST["GetCategorias"])) {
    //conecto a la base de datos
    echo '[{"id":"1","nombre":"helados"},{"id":"2","nombre":"cono"},{"id":"3","nombre":"litro"}]';
}
