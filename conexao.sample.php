<?php

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit("Acesso inválido.");
}

$host = "localhost";
$user = "seu_usuario";
$pass = "sua_senha";
$db   = "seu_banco";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    error_log("ERRO DB: " . $conn->connect_error);
    exit;
}

$conn->set_charset("utf8mb4");

?>
