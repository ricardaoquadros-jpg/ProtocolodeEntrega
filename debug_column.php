<?php
define('APP_RUNNING', true);
require 'conexao.php';

$table = 'usuarios_admin';
$col_to_check = 'funcao';

$result = $conn->query("SHOW COLUMNS FROM $table LIKE '$col_to_check'");

if ($result->num_rows > 0) {
    echo "Column '$col_to_check' EXISTS in '$table'.\n";
} else {
    echo "Column '$col_to_check' MISSING in '$table'.\n";
    echo "Columns found:\n";
    $cols = $conn->query("DESCRIBE $table");
    while ($col = $cols->fetch_assoc()) {
        echo " - " . $col['Field'] . "\n";
    }
}
?>
