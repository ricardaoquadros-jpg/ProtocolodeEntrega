<?php
define('APP_RUNNING', true);
require_once __DIR__ . '/../conexao.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add assinatura_base64 column if not exists
$sql = "ALTER TABLE emprestimos ADD COLUMN assinatura_base64 LONGTEXT DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column assinatura_base64 added successfully\n";
} else {
    // Check if error is "Duplicate column name" (code 1060)
    if ($conn->errno == 1060) {
        echo "Column assinatura_base64 already exists.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

$conn->close();
?>
