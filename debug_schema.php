<?php
define('APP_RUNNING', true);
require 'conexao.php';

$table = 'usuarios_admin';
$result = $conn->query("SHOW TABLES LIKE '$table'");

if ($result->num_rows > 0) {
    echo "Table '$table' exists.\n";
    $cols = $conn->query("DESCRIBE $table");
    while ($col = $cols->fetch_assoc()) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} else {
    echo "Table '$table' DOES NOT EXIST.\n";
    echo "Listing all tables:\n";
    $all_tables = $conn->query("SHOW TABLES");
    while ($row = $all_tables->fetch_row()) {
        echo "- " . $row[0] . "\n";
    }
}
?>
