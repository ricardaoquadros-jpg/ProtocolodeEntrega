<?php
/**
 * Script para adicionar a coluna 'observacoes' na tabela 'protocolos'
 */
define('APP_RUNNING', true);

require_once __DIR__ . '/../conexao.php';

echo "--- Atualizando Banco de Dados ---\n";

// Verificar se a coluna já existe
$res = $conn->query("SHOW COLUMNS FROM protocolos LIKE 'observacoes'");

if ($res && $res->num_rows > 0) {
    echo "A coluna 'observacoes' JÁ EXISTE. Nenhuma alteração necessária.\n";
} else {
    // Adicionar a coluna
    $sql = "ALTER TABLE protocolos ADD COLUMN observacoes TEXT NULL AFTER email";
    
    if ($conn->query($sql) === TRUE) {
        echo "Coluna 'observacoes' adicionada com SUCESSO!\n";
    } else {
        echo "ERRO ao adicionar coluna: " . $conn->error . "\n";
    }
}

$conn->close();
echo "--- Fim ---\n";
?>
