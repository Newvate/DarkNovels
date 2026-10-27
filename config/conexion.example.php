<?php

$host = 'localhost';
$db = 'darknovels';
$user = 'root';
$pass = '';

try {
    $conexion = new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('No se pudo conectar a la base de datos. Copia este archivo como config/conexion.php y configura tus datos.');
}
