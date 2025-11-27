<?php
define('APP_RUNNING', true);
require 'conexao.php';

// Check if protocolos table exists and show its structure
$result = $conn->query("SHOW TABLES LIKE 'protocolos'");
if ($result->num_rows > 0) {
    echo "Table 'protocolos' exists.\n";
    echo "Columns:\n";
    $cols = $conn->query("DESCRIBE protocolos");
    while ($col = $cols->fetch_assoc()) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} else {
    echo "Table 'protocolos' DOES NOT EXIST.\n";
}

echo "\n";

// Check if protocolo_itens table exists
$result2 = $conn->query("SHOW TABLES LIKE 'protocolo_itens'");
if ($result2->num_rows > 0) {
    echo "Table 'protocolo_itens' exists.\n";
    echo "Columns:\n";
    $cols2 = $conn->query("DESCRIBE protocolo_itens");
    while ($col2 = $cols2->fetch_assoc()) {
        echo "  - " . $col2['Field'] . " (" . $col2['Type'] . ")\n";
    }
} else {
    echo "Table 'protocolo_itens' DOES NOT EXIST.\n";
}
?>
