<?php
define('APP_RUNNING', true);

require_once __DIR__ . '/utils/config_seguranca.php';
require_once __DIR__ . '/utils/seguranca.php';
require_once __DIR__ . '/conexao.php';

session_start();

// Validar sessão
if (!isset($_SESSION['admin_logado'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Autorização por papel (Funcionário ou Administrador)
if (!checarAcessoFuncionario($conn)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissão insuficiente.']);
    exit;
}

header('Content-Type: application/json');

try {
    // Buscar empréstimos ativos ou atrasados
    $sql = "SELECT 
                e.id, 
                e.responsavel_nome, 
                e.responsavel_cpf, 
                e.responsavel_telefone, 
                e.responsavel_email, 
                e.responsavel_setor, 
                e.data_emprestimo,
                e.status
            FROM emprestimos e
            WHERE e.status IN ('ativo', 'atrasado')
            ORDER BY e.responsavel_nome ASC";

    $result = $conn->query($sql);
    $emprestimos = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Buscar itens deste empréstimo
            $stmtItens = $conn->prepare("SELECT patrimonio_codigo, equipamento_tipo FROM itens_emprestimo WHERE emprestimo_id = ?");
            $stmtItens->bind_param("i", $row['id']);
            $stmtItens->execute();
            $resItens = $stmtItens->get_result();
            
            $itens = [];
            while ($p = $resItens->fetch_assoc()) {
                $itens[] = $p;
            }
            
            $row['itens'] = $itens;
            $emprestimos[] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => $emprestimos]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar empréstimos: ' . $e->getMessage()]);
}
