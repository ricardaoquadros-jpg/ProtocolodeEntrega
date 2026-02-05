<?php
/**
 * SCRIPT DE MIGRAÇÃO UNIFICADO
 * 
 * Execute este script UMA VEZ no servidor de produção para
 * criar/atualizar as tabelas do banco de dados.
 * 
 * PROTEÇÃO: Requer senha de administrador para executar.
 * 
 * Uso: migrate.php?key=SUA_CHAVE_SECRETA
 */

define('APP_RUNNING', true);

// Chave de segurança para execução (mude esta chave!)
$MIGRATION_KEY = 'MIGRAR_PROTOCOLO_2024';

// Verificar chave
if (!isset($_GET['key']) || $_GET['key'] !== $MIGRATION_KEY) {
    http_response_code(403);
    die("Acesso negado. Forneça a chave correta.");
}

// Configuração
header('Content-Type: text/html; charset=utf-8');
echo "<pre style='font-family: monospace; background: #1e1e1e; color: #00ff00; padding: 20px;'>";
echo "===========================================\n";
echo "   MIGRAÇÃO DO BANCO DE DADOS\n";
echo "   Sistema de Protocolos - Prefeitura\n";
echo "===========================================\n\n";

// Conectar ao banco
require_once __DIR__ . '/conexao.php';

$migrations = [
    // 1. Verificar/criar coluna tipo_documento em protocolos
    [
        'name' => 'Adicionar coluna tipo_documento em protocolos',
        'check' => "SHOW COLUMNS FROM protocolos LIKE 'tipo_documento'",
        'sql' => "ALTER TABLE protocolos ADD COLUMN tipo_documento VARCHAR(20) NOT NULL DEFAULT 'cpf' AFTER cpf_matricula"
    ],
    
    // 2. Adicionar campos de perfil em usuarios_admin
    [
        'name' => 'Adicionar coluna nome_completo em usuarios_admin',
        'check' => "SHOW COLUMNS FROM usuarios_admin LIKE 'nome_completo'",
        'sql' => "ALTER TABLE usuarios_admin ADD COLUMN nome_completo VARCHAR(255) DEFAULT NULL"
    ],
    [
        'name' => 'Adicionar coluna email em usuarios_admin',
        'check' => "SHOW COLUMNS FROM usuarios_admin LIKE 'email'",
        'sql' => "ALTER TABLE usuarios_admin ADD COLUMN email VARCHAR(255) DEFAULT NULL"
    ],
    [
        'name' => 'Adicionar coluna telefone em usuarios_admin',
        'check' => "SHOW COLUMNS FROM usuarios_admin LIKE 'telefone'",
        'sql' => "ALTER TABLE usuarios_admin ADD COLUMN telefone VARCHAR(20) DEFAULT NULL"
    ],
    
    // 3. Adicionar tipo_transacao em itens_protocolo
    [
        'name' => 'Adicionar coluna tipo_transacao em itens_protocolo',
        'check' => "SHOW COLUMNS FROM itens_protocolo LIKE 'tipo_transacao'",
        'sql' => "ALTER TABLE itens_protocolo ADD COLUMN tipo_transacao VARCHAR(50) DEFAULT 'Entrega' AFTER patrimonio_codigo"
    ],
    
    // 4. Adicionar criado_por_id em protocolos
    [
        'name' => 'Adicionar coluna criado_por_id em protocolos',
        'check' => "SHOW COLUMNS FROM protocolos LIKE 'criado_por_id'",
        'sql' => "ALTER TABLE protocolos ADD COLUMN criado_por_id INT(11) DEFAULT NULL AFTER id"
    ],
    
    // 5. Adicionar foreign key para criado_por_id
    [
        'name' => 'Adicionar foreign key fk_protocolo_usuario',
        'check' => "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'protocolos' AND CONSTRAINT_NAME = 'fk_protocolo_usuario'",
        'sql' => "ALTER TABLE protocolos ADD CONSTRAINT fk_protocolo_usuario FOREIGN KEY (criado_por_id) REFERENCES usuarios_admin(id) ON DELETE SET NULL"
    ],
    
    // 6. Criar tabela de empréstimos
    [
        'name' => 'Criar tabela emprestimos',
        'check' => "SHOW TABLES LIKE 'emprestimos'",
        'sql' => "CREATE TABLE emprestimos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            responsavel_nome VARCHAR(255) NOT NULL,
            responsavel_cpf VARCHAR(20),
            responsavel_telefone VARCHAR(20),
            responsavel_email VARCHAR(255),
            responsavel_setor VARCHAR(100),
            data_emprestimo DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_previsao_devolucao DATE,
            data_devolucao DATETIME DEFAULT NULL,
            status ENUM('ativo','devolvido','atrasado') DEFAULT 'ativo',
            observacoes TEXT,
            criado_por_id INT,
            devolvido_por_id INT DEFAULT NULL,
            FOREIGN KEY (criado_por_id) REFERENCES usuarios_admin(id) ON DELETE SET NULL,
            FOREIGN KEY (devolvido_por_id) REFERENCES usuarios_admin(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    ],
    
    // 7. Criar tabela de itens de empréstimo
    [
        'name' => 'Criar tabela itens_emprestimo',
        'check' => "SHOW TABLES LIKE 'itens_emprestimo'",
        'sql' => "CREATE TABLE itens_emprestimo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            emprestimo_id INT NOT NULL,
            patrimonio_codigo VARCHAR(100) NOT NULL,
            equipamento_tipo VARCHAR(100),
            descricao TEXT,
            FOREIGN KEY (emprestimo_id) REFERENCES emprestimos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    ]
];

$success = 0;
$skipped = 0;
$failed = 0;

foreach ($migrations as $migration) {
    echo "[VERIFICANDO] {$migration['name']}... ";
    
    $check = $conn->query($migration['check']);
    
    if ($check && $check->num_rows > 0) {
        echo "<span style='color: #ffff00;'>JÁ EXISTE</span>\n";
        $skipped++;
    } else {
        echo "\n[EXECUTANDO] ";
        
        if ($conn->query($migration['sql'])) {
            echo "<span style='color: #00ff00;'>✓ SUCESSO</span>\n";
            $success++;
        } else {
            echo "<span style='color: #ff0000;'>✗ ERRO: " . $conn->error . "</span>\n";
            $failed++;
        }
    }
}

echo "\n===========================================\n";
echo "   RESUMO DA MIGRAÇÃO\n";
echo "===========================================\n";
echo "   Executados com sucesso: $success\n";
echo "   Já existentes (pulados): $skipped\n";
echo "   Falhas: $failed\n";
echo "===========================================\n";

if ($failed === 0) {
    echo "\n<span style='color: #00ff00; font-size: 18px;'>✓ MIGRAÇÃO CONCLUÍDA COM SUCESSO!</span>\n";
    echo "\nAgora você pode acessar o sistema normalmente.\n";
    echo "IMPORTANTE: Delete ou proteja este arquivo após a migração!\n";
} else {
    echo "\n<span style='color: #ff0000; font-size: 18px;'>✗ HOUVE ERROS NA MIGRAÇÃO</span>\n";
    echo "\nVerifique os erros acima e tente novamente.\n";
}

echo "</pre>";

$conn->close();
?>
