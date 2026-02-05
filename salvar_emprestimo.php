<?php
define('APP_RUNNING', true);

/* --- CONFIGURAÇÃO DE SEGURANÇA --- */
require_once __DIR__ . '/utils/config_seguranca.php';

session_start();
aplicarHeadersSeguranca();

/* --- LOGS DE ERRO --- */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs_php_errors.log');

require_once __DIR__ . '/utils/seguranca.php';

/* --- BANCO DE DADOS --- */
require __DIR__ . '/conexao.php';

/* --- VERIFICAR LOGIN --- */
if (!isset($_SESSION['admin_logado'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

/* --- APENAS POST --- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

/* --- RECEBER JSON --- */
header('Content-Type: application/json; charset=utf-8');

$json = file_get_contents('php://input');
$dados = json_decode($json, true);

if (!$dados) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

/* --- VALIDAÇÃO --- */
$nome = limparTexto($dados['responsavel_nome'] ?? '');
$cpf = limparTexto($dados['responsavel_cpf'] ?? '');
$telefone = limparTexto($dados['responsavel_telefone'] ?? '');
$email = limparEmail($dados['responsavel_email'] ?? '') ?: null;
$setor = limparTexto($dados['responsavel_setor'] ?? '');
$dataPrevisao = $dados['data_previsao'] ?? null;
$observacoes = limparTexto($dados['observacoes'] ?? '');
$itens = $dados['itens'] ?? [];

if (empty($nome)) {
    echo json_encode(['success' => false, 'message' => 'Nome do responsável é obrigatório']);
    exit;
}

if (empty($dataPrevisao)) {
    echo json_encode(['success' => false, 'message' => 'Data de previsão é obrigatória']);
    exit;
}

if (empty($itens) || !is_array($itens)) {
    echo json_encode(['success' => false, 'message' => 'Adicione pelo menos um equipamento']);
    exit;
}

$criadoPor = $_SESSION['admin_id'];

/* --- INICIAR TRANSAÇÃO --- */
$conn->begin_transaction();

try {
$assinatura = $dados['assinatura'] ?? null;
    
    // ... (existing validation)

    // 1. Inserir empréstimo
    $sql = "INSERT INTO emprestimos (responsavel_nome, responsavel_cpf, responsavel_telefone, responsavel_email, responsavel_setor, data_previsao_devolucao, observacoes, criado_por_id, assinatura_base64) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssis", 
        $nome, 
        $cpf, 
        $telefone, 
        $email, 
        $setor,
        $dataPrevisao,
        $observacoes,
        $criadoPor,
        $assinatura
    );

    if (!$stmt->execute()) {
        throw new Exception("Erro ao inserir empréstimo: " . $conn->error);
    }

    $emprestimoId = $conn->insert_id;

    // 2. Inserir itens
    $sqlItem = "INSERT INTO itens_emprestimo (emprestimo_id, patrimonio_codigo, equipamento_tipo, descricao) VALUES (?, ?, ?, ?)";
    $stmtItem = $conn->prepare($sqlItem);

    foreach ($itens as $item) {
        $patrimonio = limparTexto($item['patrimonio'] ?? '');
        $tipo = limparTexto($item['tipo'] ?? '');
        $descricao = limparTexto($item['descricao'] ?? '');

        if (empty($patrimonio)) continue;

        $stmtItem->bind_param("isss", $emprestimoId, $patrimonio, $tipo, $descricao);
        
        if (!$stmtItem->execute()) {
            throw new Exception("Erro ao inserir item: " . $conn->error);
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Empréstimo registrado com sucesso',
        'id' => $emprestimoId
    ]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Erro empréstimo: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
