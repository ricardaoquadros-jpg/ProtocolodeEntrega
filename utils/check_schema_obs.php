<?php
require_once __DIR__ . '/../conexao.php';

$res = $conn->query("DESCRIBE protocolos");
$found = false;
while ($row = $res->fetch_assoc()) {
    if ($row['Field'] === 'observacoes') {
        $found = true;
        break;
    }
}

echo $found ? "EXISTS" : "MISSING";
