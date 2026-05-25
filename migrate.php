<?php
/**
 * SCRIPT DE MIGRAÇÃO UNIFICADO
 * 
 * Execute este script UMA VEZ no servidor de produção para
 * criar/atualizar as tabelas do banco de dados.
 * 
 * PROTEÇÃO: Requer chave de migração segura para executar.
 */

define('APP_RUNNING', true);

// Carrega configurações se existirem
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
} elseif (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// A chave DEVE ser definida em config.php. Sem ela (ou usando valores conhecidos/
// vazados), a migração não roda (fail-closed).
$chavesProibidas = ['', 'MUDE_ESTA_CHAVE', 'MIGRAR_PROTOCOLO_2024'];
if (!defined('MIGRATION_KEY') || in_array(MIGRATION_KEY, $chavesProibidas, true)) {
    http_response_code(403);
    die("Migração desabilitada: defina uma MIGRATION_KEY forte e exclusiva em config.php antes de executar.");
}
$MIGRATION_KEY = MIGRATION_KEY;

// Se não for POST, exibe formulário seguro
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Migração de Banco de Dados</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>body { font-family: 'Inter', sans-serif; }</style>
    </head>
    <body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen p-4">
        <div class="bg-slate-800 p-8 rounded-lg shadow-lg w-full max-w-md border border-slate-700">
            <h1 class="text-xl font-bold text-center mb-1">Migração do Banco de Dados</h1>
            <p class="text-xs text-slate-400 text-center mb-6">Sistema de Protocolos - Prefeitura</p>
            <form method="POST" action="">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Chave de Migração:</label>
                    <input type="password" name="key" required placeholder="Digite a chave secreta" 
                        class="w-full bg-slate-700 border border-slate-600 rounded-md p-3 text-white outline-none focus:border-indigo-500 transition-all text-sm">
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-md transition-colors text-sm shadow-md">
                    Executar Migração
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Verificar chave via POST
$input_key = $_POST['key'] ?? '';
if ($input_key !== $MIGRATION_KEY) {
    http_response_code(403);
    die("Acesso negado. Chave de migração incorreta.");
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
    ],
    
    // 8. Criar tabela de logs de auditoria
    [
        'name' => 'Criar tabela logs_auditoria',
        'check' => "SHOW TABLES LIKE 'logs_auditoria'",
        'sql' => "CREATE TABLE logs_auditoria (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT DEFAULT NULL,
            usuario_nome VARCHAR(255) DEFAULT NULL,
            acao VARCHAR(255) NOT NULL,
            detalhes TEXT DEFAULT NULL,
            ip_endereco VARCHAR(45) DEFAULT NULL,
            data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios_admin(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    ],
    
    // 9. Criar tabela de tentativas de login (Brute Force Protection)
    [
        'name' => 'Criar tabela tentativas_login',
        'check' => "SHOW TABLES LIKE 'tentativas_login'",
        'sql' => "CREATE TABLE tentativas_login (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_endereco VARCHAR(45) NOT NULL,
            usuario VARCHAR(255) NOT NULL,
            tentativa_tempo DATETIME DEFAULT CURRENT_TIMESTAMP
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
