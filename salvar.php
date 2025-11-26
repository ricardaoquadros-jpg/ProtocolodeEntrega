<?php
/** -------------------------------------------------------
 *  SALVAR.PHP – SEGURO, LIMPO E PRODUÇÃO-READY
 * --------------------------------------------------------
 */

define('APP_RUNNING', true);

/* --- HEADER JSON SEMPRE --- */
header('Content-Type: application/json; charset=utf-8');

/* --- LOGAR ERROS SEM EXIBIR NA TELA --- */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs_php_errors.log');

/* --- ARQUIVOS EXTERNOS --- */
require_once __DIR__ . '/utils/seguranca.php';

/* --- VERIFICA SE conexao.php EXISTE --- */
if (!file_exists(__DIR__ . '/conexao.php')) {
    echo json_encode([
        'success' => false,
        'message' => 'Arquivo de conexão não encontrado.'
    ]);
    exit;
}

require_once __DIR__ . '/conexao.php';

/* --- LER JSON RECEBIDO --- */
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);

if (!is_array($input)) {
    echo json_encode(['success' => false, 'message' => 'JSON inválido recebido']);
    exit;
}

/* --- Sanitização Segura --- */
$nome       = limparTexto($input['nome'] ?? '');
$cpf        = limparNumero($input['cpf'] ?? '');
$telefone   = limparTelefone($input['telefone'] ?? '');
$email      = limparEmail($input['email'] ?? '');
$assinatura = $input['assinatura'] ?? '';
$itens      = limparItens($input['itens'] ?? []);

/* --- Validações --- */
if (!$nome)    { echo json_encode(['success' => false, 'message' => 'Nome inválido']); exit; }
if (!$cpf)     { echo json_encode(['success' => false, 'message' => 'CPF/Matrícula inválido']); exit; }
if (!$email)   { echo json_encode(['success' => false, 'message' => 'E-mail inválido']); exit; }

if (strpos($assinatura, "data:image") !== 0) {
    echo json_encode(['success' => false, 'message' => 'Assinatura inválida!']); 
    exit;
}

/* --- Verifica Conexão --- */
if (!$conn || $conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro na conexão com o banco de dados.'
    ]);
    exit;
}

/* -------------------------------------------------------------------
 *  SALVAR PROTOCOLO NA TABELA `protocolos`
 * -------------------------------------------------------------------
 */
$sql = "INSERT INTO protocolos (nome_recebedor, cpf_matricula, telefone, email, assinatura_base64)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar comando SQL']);
    exit;
}

$stmt->bind_param("sssss", $nome, $cpf, $telefone, $email, $assinatura);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar no banco']);
    exit;
}

$idProtocolo = $stmt->insert_id;
$stmt->close();

/* -------------------------------------------------------------------
 *  SALVAR ITENS NA TABELA `protocolo_itens`
 * -------------------------------------------------------------------
 */
if (!empty($itens)) {

    $sqlItem = "INSERT INTO protocolo_itens (protocolo_id, patrimonio_codigo, tipo_equipamento)
                VALUES (?, ?, ?)";

    $stmt2 = $conn->prepare($sqlItem);

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

/* --- RETORNO LIMPO (SEM QUALQUER TEXTO ANTES) --- */
echo json_encode([
    'success' => true,
    'id'      => $idProtocolo,
    'data'    => date('d/m/Y H:i:s')
]);
exit;

?>
