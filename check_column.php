<?php
define('APP_RUNNING', true);
require 'conexao.php';

// Check if tipo_documento column exists in protocolos table
$result = $conn->query("SHOW COLUMNS FROM protocolos LIKE 'tipo_documento'");

if ($result->num_rows > 0) {
    echo "Column 'tipo_documento' EXISTS in 'protocolos'.\n";
} else {
    echo "Column 'tipo_documento' MISSING in 'protocolos'.\n";
    echo "All columns in protocolos:\n";
    $cols = $conn->query("DESCRIBE protocolos");
    while ($col = $cols->fetch_assoc()) {
        echo " - " . $col['Field'] . "\n";
    }
}
?>
