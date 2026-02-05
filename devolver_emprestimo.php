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

header('Content-Type: application/json; charset=utf-8');

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
    echo json_encode([
        'success' => true,
        'message' => 'Devolução registrada com sucesso'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao registrar devolução: ' . $conn->error
    ]);
}

$conn->close();
?>
