<?php

$host = "localhost";
$dbname = "restaurante_inteligente";
$user = "restaurante_user";
$password = "TuPassword123!";

try {

    $conexion = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $password
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {

    die("Error de conexion: " . $e->getMessage());

}
?>