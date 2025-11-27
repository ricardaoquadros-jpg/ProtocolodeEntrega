<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "banco";
$backup_file = "banco.sql";

// Connect to MySQL server
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.\n";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($db_name);

// Read the SQL file
if (!file_exists($backup_file)) {
    die("Backup file not found: $backup_file");
}

$sql_content = file_get_contents($backup_file);

// Execute multi-query
if ($conn->multi_query($sql_content)) {
    echo "Restoring database...\n";
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check if there are more result sets
    } while ($conn->next_result());
    echo "Database restored successfully from $backup_file.\n";
} else {
    echo "Error restoring database: " . $conn->error . "\n";
}

$conn->close();
?>
