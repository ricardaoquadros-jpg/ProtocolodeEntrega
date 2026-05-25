<?php
define('APP_RUNNING', true);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/utils/config_seguranca.php';
require __DIR__ . '/utils/seguranca.php';

/* --- CARREGAR CONFIGURAÇÕES --- */
require __DIR__ . '/conexao.php';

date_default_timezone_set('America/Sao_Paulo');

header('Content-Type: application/json; charset=utf-8');

/* --- VERIFICAR LOGIN --- */
session_start();
if (!isset($_SESSION['admin_logado'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Acesso negado']));
}

/* --- CSRF --- */
if (!validarCSRFRequest()) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Token de segurança inválido. Atualize a página.']));
}

/* --- AUTORIZAÇÃO POR PAPEL --- */
if (!checarAcessoFuncionario($conn)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Permissão insuficiente.']));
}

/* ------------------------- RECEBE DADOS ------------------------- */

$id = (int) limparNumero($_POST['id_protocolo'] ?? '');

$dataHora = date('d/m/Y H:i:s');

/* ------------------------- VALIDAÇÃO ---------------------------- */

if (!$id) {
    exit(json_encode(['success' => false, 'message' => 'ID do protocolo ausente']));
}

/* --- DESTINATÁRIO AUTORITATIVO: vem do banco, NUNCA do cliente ---
   Evita uso do servidor SMTP como relay para endereços arbitrários. */
$stmtDest = $conn->prepare("SELECT nome_recebedor, email FROM protocolos WHERE id = ?");
$stmtDest->bind_param("i", $id);
$stmtDest->execute();
$protoDest = $stmtDest->get_result()->fetch_assoc();
$stmtDest->close();

if (!$protoDest) {
    exit(json_encode(['success' => false, 'message' => 'Protocolo não encontrado']));
}

$email = limparEmail($protoDest['email'] ?? '');
$nome  = limparTexto($protoDest['nome_recebedor'] ?? '');

if (!$email) {
    exit(json_encode(['success' => false, 'message' => 'Este protocolo não possui e-mail cadastrado para envio.']));
}

if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== 0) {
    exit(json_encode(['success' => false, 'message' => 'PDF inválido']));
}

/* ---- Confirma se é PDF real ---- */
if (mime_content_type($_FILES['pdf']['tmp_name']) !== 'application/pdf') {
    exit(json_encode(['success' => false, 'message' => 'Arquivo enviado não é PDF']));
}

/* ------------------------- CONFIG MAIL ---------------------------- */

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.guaiba.local';
    $mail->SMTPAuth   = defined('SMTP_AUTH') ? SMTP_AUTH : false;
    $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 25;
    $mail->CharSet    = 'UTF-8';

    if ($mail->SMTPAuth) {
        $mail->Username = defined('SMTP_USER') ? SMTP_USER : '';
        $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : '';
    }

    $fromEmail = defined('EMAIL_FROM') ? EMAIL_FROM : 'nao-responda@guaiba.rs.gov.br';
    $fromName  = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Protocolo TI';

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($email, $nome);

    /* ----------- ANEXO ------------ */
    $mail->addAttachment($_FILES['pdf']['tmp_name'], 'protocolo.pdf');

    /* ----------- MENSAGEM ----------- */
    $mail->isHTML(false);

    $mensagem = "
Olá, {$nome}!
Seu protocolo foi gerado com sucesso.

ID do Protocolo: {$id}
Data e Hora: {$dataHora}

O PDF está anexado a este e-mail.

Atenciosamente,
Equipe de TI – Prefeitura de Guaíba
";

    $mail->Subject = "Protocolo Gerado - ID {$id}";
    $mail->Body    = $mensagem;

    /* ----------- ENVIO ----------- */
    $mail->send();

    registrarLogAuditoria($conn, 'PROTOCOLO_EMAIL_ENVIADO', "Protocolo #{$id} enviado para {$email}");

    echo json_encode(['success' => true, 'message' => 'E-mail enviado']);

} catch (Exception $e) {
    error_log("Erro ao enviar email protocolo #{$id}: " . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => "Erro ao enviar e-mail. Tente novamente."]);
}
