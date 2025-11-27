<?php
define('APP_RUNNING', true);
require 'conexao.php';

$sql = "ALTER TABLE usuarios_admin ADD COLUMN funcao VARCHAR(50) DEFAULT 'Usuário'";

if ($conn->query($sql) === TRUE) {
    echo "Column 'funcao' added successfully to 'usuarios_admin'.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

$conn->close();
?>
