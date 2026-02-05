<?php
define('APP_RUNNING', true);
require_once __DIR__ . '/../conexao.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add devolvido_por_id column
$sql = "ALTER TABLE emprestimos ADD COLUMN devolvido_por_id INT(11) DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column devolvido_por_id added successfully\n";
} else {
    if ($conn->errno == 1060) {
        echo "Column devolvido_por_id already exists.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

$conn->close();
?>
