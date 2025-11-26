<?php
$host = "smtp.guaiba.rs.gov.br";
$port = 25;

echo "Testando conexão para $host:$port...<br>";

$fp = fsockopen($host, $port, $errno, $errstr, 8);

if (!$fp) {
    echo "❌ Falhou: [$errno] $errstr";
} else {
    echo "✅ Conectou com sucesso!";
    fclose($fp);
}
?>
