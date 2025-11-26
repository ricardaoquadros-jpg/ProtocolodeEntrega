<?php
/** -------------------------------------------------------
 *  SALVAR.PHP – CPF / MATRÍCULA + TELEFONE FORMATADOS
 * -------------------------------------------------------- */

define('APP_RUNNING', true);

/* --- HEADER JSON --- */
header('Content-Type: application/json; charset=utf-8');

/* --- LOG DE ERROS --- */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs_php_errors.log');

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

if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco']);
    exit;
}

$sql = "INSERT INTO protocolos (nome_recebedor, cpf_matricula, tipo_documento, telefone, email, assinatura_base64)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar SQL']);
    exit;
}

$stmt->bind_param("ssssss", 
    $nome, 
    $documento, 
    $tipoDocumento, 
    $telefone, 
    $email, 
    $assinatura
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
    $sql2 = "INSERT INTO protocolo_itens (protocolo_id, patrimonio_codigo, tipo_equipamento)
             VALUES (?, ?, ?)";

    $stmt2 = $conn->prepare($sql2);

    if ($stmt2) {
        foreach ($itens as $item) {
            $codigo = $item['patrimonio'] ?? '';
            $tipo   = $item['equipamento'] ?? '';

            if ($codigo !== '') {
                $stmt2->bind_param("iss", $idProtocolo, $codigo, $tipo);
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
