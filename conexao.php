<?php
/**
 * CONEXÃO COM BANCO DE DADOS
 * Utiliza configurações do config.php
 */

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit("Acesso inválido.");
}

// Carrega configurações
if (!file_exists(__DIR__ . '/config.php')) {
    die("Erro: config.php não encontrado. Copie config.sample.php para config.php e configure.");
}
require_once __DIR__ . '/config.php';

// Conexão com banco
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log("ERRO DB: " . $conn->connect_error);
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Erro de conexão: " . $conn->connect_error);
    }
    die("Erro de conexão com o banco de dados.");
}

$conn->set_charset("utf8mb4");

// Define timezone
if (defined('TIMEZONE')) {
    date_default_timezone_set(TIMEZONE);
}

?>
