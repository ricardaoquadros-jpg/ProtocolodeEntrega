<?php
define('APP_RUNNING', true);

// Adjust path to reach root directory from utils/
require_once __DIR__ . '/../conexao.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create tables
$sqlStatements = [
    "CREATE TABLE IF NOT EXISTS `emprestimos` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `responsavel_nome` varchar(255) NOT NULL,
        `responsavel_cpf` varchar(20) DEFAULT NULL,
        `responsavel_telefone` varchar(20) DEFAULT NULL,
        `responsavel_email` varchar(255) DEFAULT NULL,
        `responsavel_setor` varchar(100) DEFAULT NULL,
        `data_emprestimo` datetime DEFAULT CURRENT_TIMESTAMP,
        `data_previsao_devolucao` date NOT NULL,
        `data_devolucao` datetime DEFAULT NULL,
        `status` enum('ativo','atrasado','devolvido') NOT NULL DEFAULT 'ativo',
        `observacoes` text DEFAULT NULL,
        `criado_por_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_GENERAL_CI;",

    "CREATE TABLE IF NOT EXISTS `itens_emprestimo` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `emprestimo_id` int(11) NOT NULL,
        `patrimonio_codigo` varchar(50) NOT NULL,
        `equipamento_tipo` varchar(100) DEFAULT NULL,
        `descricao` varchar(255) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `emprestimo_id` (`emprestimo_id`),
        CONSTRAINT `itens_emprestimo_ibfk_1` FOREIGN KEY (`emprestimo_id`) REFERENCES `emprestimos` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_GENERAL_CI;"
];

foreach ($sqlStatements as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

$conn->close();
?>
