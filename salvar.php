<?php
/** -------------------------------------------------------
 *  SALVAR.PHP – CPF / MATRÍCULA + TELEFONE FORMATADOS
 * -------------------------------------------------------- */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs_php_errors.log');

define('APP_RUNNING', true);

date_default_timezone_set('America/Sao_Paulo');

/* --- HEADER JSON --- */
header('Content-Type: application/json; charset=utf-8');

/* --- IMPORTS --- */
require_once __DIR__ . '/utils/seguranca.php';

if (!file_exists(__DIR__ . '/conexao.php')) {
    echo json_encode(['success' => false, 'message' => 'Arquivo conexao.php não encontrado']);
    exit;
}
require_once __DIR__ . '/conexao.php';

/* --- LER JSON --- */
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit;
}

/* ===============================
    CAMPOS RECEBIDOS DO FRONT-END
   =============================== */

$nome            = limparTexto($input['nome'] ?? '');
$documento       = limparNumero($input['documento'] ?? '');   // Pode ser CPF ou Matrícula
$tipoDocumento   = limparTexto($input['tipo_documento'] ?? 'cpf');
$telefone        = limparTelefone($input['telefone'] ?? '');
$email           = limparEmail($input['email'] ?? '');
$observacoes     = limparTexto($input['observacoes'] ?? '');
$assinatura      = $input['assinatura'] ?? '';
$itens           = limparItens($input['itens'] ?? []);

/* ===============================
    VALIDAÇÕES
   =============================== */

if (!$nome) {
    echo json_encode(['success' => false, 'message' => 'Nome inválido']);
    exit;
}

if (!$documento) {
    echo json_encode(['success' => false, 'message' => 'CPF ou Matrícula obrigatório']);
    exit;
}

if ($tipoDocumento === "cpf") {
    if (strlen($documento) !== 11) {
        echo json_encode(['success' => false, 'message' => 'CPF deve ter 11 números']);
        exit;
    }
} else {
    if (strlen($documento) < 6 || strlen($documento) > 7) {
        echo json_encode(['success' => false, 'message' => 'Matrícula deve ter 6 ou 7 números']);
        exit;
    }
}

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
    exit;
}

if (strpos($assinatura, "data:image") !== 0) {
    echo json_encode(['success' => false, 'message' => 'Assinatura inválida!']);
    exit;
}

/* ===============================
    SALVAR NO BANCO
   =============================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$criadoPor = $_SESSION['admin_id'] ?? null;

if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco']);
    exit;
}

$sql = "INSERT INTO protocolos (nome_recebedor, cpf_matricula, tipo_documento, telefone, email, observacoes, assinatura_base64, criado_por_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar SQL']);
    exit;
}

$stmt->bind_param("sssssssi", 
    $nome, 
    $documento, 
    $tipoDocumento, 
    $telefone, 
    $email, 
    $observacoes,
    $assinatura,
    $criadoPor
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar protocolo']);
    exit;
}

$idProtocolo = $stmt->insert_id;
$stmt->close();

/* ===============================
    SALVAR ITENS
   =============================== */

if (!empty($itens)) {
    $sql2 = "INSERT INTO protocolo_itens (protocolo_id, patrimonio_codigo, tipo_transacao, tipo_equipamento)
             VALUES (?, ?, ?, ?)";

    $stmt2 = $conn->prepare($sql2);

    if ($stmt2) {
        foreach ($itens as $item) {
            $codigo    = $item['patrimonio'] ?? '';
            $transacao = $item['transacao'] ?? 'ENTREGA'; // Default if missing
            $tipo      = $item['equipamento'] ?? '';

            if ($codigo !== '') {
                $stmt2->bind_param("isss", $idProtocolo, $codigo, $transacao, $tipo);
                $stmt2->execute();
            }
        }
        $stmt2->close();
    }
}

$conn->close();

/* ===============================
    RETORNO FINAL LIMPO
   =============================== */

echo json_encode([
    'success' => true,
    'id'      => $idProtocolo,
    'data'    => date('d/m/Y H:i:s')
]);
exit;

?>
