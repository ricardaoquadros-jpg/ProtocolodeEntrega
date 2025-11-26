<?php
define('APP_RUNNING', true);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/utils/seguranca.php';

date_default_timezone_set('America/Sao_Paulo');

/* ------------------------- RECEBE DADOS ------------------------- */

$email = limparEmail($_POST['email'] ?? '');
$nome  = limparTexto($_POST['nome'] ?? '');
$id    = limparNumero($_POST['id_protocolo'] ?? '');

$dataHora = date('d/m/Y H:i:s');

/* ------------------------- VALIDAÇÃO ---------------------------- */

if (!$email) {
    exit(json_encode(['success' => false, 'message' => 'E-mail inválido']));
}

if (!$id) {
    exit(json_encode(['success' => false, 'message' => 'ID do protocolo ausente']));
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
    $mail->Host       = 'smtp.guaiba.local';
    $mail->SMTPAuth   = false;
    $mail->Port       = 25;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('nao-responda@guaiba.rs.gov.br', 'Protocolo TI');
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

    echo json_encode(['success' => true, 'message' => 'E-mail enviado']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Erro ao enviar: {$mail->ErrorInfo}"]);
}
