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

header('Content-Type: application/json; charset=utf-8');

/* --- VERIFICAR LOGIN --- */
if (!isset($_SESSION['admin_logado'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

/* --- APENAS POST --- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

/* --- VALIDAR CSRF --- */
if (!validarCSRFRequest()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de segurança inválido. Atualize a página.']);
    exit;
}

/* --- AUTORIZAÇÃO POR PAPEL (Funcionário ou Administrador) --- */
if (!checarAcessoFuncionario($conn)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissão insuficiente para registrar devoluções.']);
    exit;
}

/* --- RECEBER DADOS --- */
$emprestimoId = (int)($_POST['id'] ?? 0);

if ($emprestimoId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do empréstimo inválido']);
    exit;
}

/* --- VERIFICAR SE EXISTE E NÃO ESTÁ DEVOLVIDO --- */
$stmt = $conn->prepare("SELECT id, status FROM emprestimos WHERE id = ?");
$stmt->bind_param("i", $emprestimoId);
$stmt->execute();
$result = $stmt->get_result();
$emprestimo = $result->fetch_assoc();

if (!$emprestimo) {
    echo json_encode(['success' => false, 'message' => 'Empréstimo não encontrado']);
    exit;
}

if ($emprestimo['status'] === 'devolvido') {
    echo json_encode(['success' => false, 'message' => 'Este empréstimo já foi devolvido']);
    exit;
}

/* --- REGISTRAR DEVOLUÇÃO --- */
$devolvidoPor = $_SESSION['admin_id'];

$sqlUpdate = "UPDATE emprestimos SET status = 'devolvido', data_devolucao = NOW(), devolvido_por_id = ? WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("ii", $devolvidoPor, $emprestimoId);

if ($stmtUpdate->execute()) {
    registrarLogAuditoria($conn, 'EMPRESTIMO_DEVOLVIDO', "Empréstimo #{$emprestimoId} devolvido");
    echo json_encode([
        'success' => true,
        'message' => 'Devolução registrada com sucesso'
    ]);
} else {
    error_log("Erro ao registrar devolução #{$emprestimoId}: " . $conn->error);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao registrar devolução. Tente novamente.'
    ]);
}

$conn->close();
?>
