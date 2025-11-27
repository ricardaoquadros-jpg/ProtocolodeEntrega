<?php
require 'conexao.php';

if ($conn->ping()) {
    echo "Connection verified successfully!";
} else {
    echo "Connection failed: " . $conn->error;
}
?>
