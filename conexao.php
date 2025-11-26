<?php
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso proibido.');
}

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = ''; 
$DB_NAME = 'banco'; // <-- confirmado pelo seu protocolos.php

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_errno) {
    error_log('Erro MySQL: ' . $conn->connect_error);
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Erro ao conectar ao banco de dados.'
    ]));
}

$conn->set_charset('utf8mb4');
?>
