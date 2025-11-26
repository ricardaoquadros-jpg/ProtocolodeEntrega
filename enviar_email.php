<?php
define('APP_RUNNING', true);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/utils/seguranca.php';

date_default_timezone_set('America/Sao_Paulo');

$email = limparEmail($_POST['email'] ?? '');
$nome  = limparTexto($_POST['nome'] ?? '');

if (!$email) {
    exit(json_encode(['success' => false, 'message' => 'E-mail inválido']));
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.guaiba.local';
    $mail->SMTPAuth   = false;
    $mail->Port       = 25;

    $mail->setFrom('nao-responda@guaiba.rs.gov.br', 'Protocolo TI');
    $mail->addAddress($email, $nome);

    // Segurança para anexos
    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== 0) {
        throw new Exception("PDF inválido.");
    }

    $tmp = $_FILES['pdf']['tmp_name'];

    // Confirma se é PDF real
    if (mime_content_type($tmp) !== 'application/pdf') {
        throw new Exception("Arquivo não é um PDF válido.");
    }

    $mail->addAttachment($tmp, 'protocolo.pdf');

    $mail->isHTML(true);
    $mail->Subject = "Protocolo Gerado";

    $mail->Body = "<p>Olá, <strong>{$nome}</strong>, seu PDF está em anexo.</p>";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'E-mail enviado']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => "Erro: {$e->getMessage()}"]);
}
?>
