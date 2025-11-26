<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

// --- LÓGICA DE RECEBIMENTO DO POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Verifica se os dados necessários foram enviados
    if (!isset($_FILES['pdf']) || !isset($_POST['email']) || !isset($_POST['nome'])) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
        exit;
    }

    $email = $_POST['email'];
    $nome = $_POST['nome'];
    $idProtocolo = $_POST['id_protocolo'] ?? 'N/A'; // Recebe o ID ou usa padrão
    $pdfFile = $_FILES['pdf'];
    $dataHora = date('d/m/Y H:i:s');

    // Chama a função de envio
    $resultado = enviarEmailProtocolo($email, $nome, $idProtocolo, $dataHora, $pdfFile);

    echo json_encode($resultado);
    exit;
}

function enviarEmailProtocolo($destinatarioEmail, $destinatarioNome, $idProtocolo, $dataHora, $pdfFile, $debug = false) {

    date_default_timezone_set('America/Sao_Paulo'); // GMT-3

    if (empty($destinatarioEmail)) {
        return ['success' => false, 'message' => 'E-mail destinatário ausente'];
    }

    $mail = new PHPMailer(true);
    $debugLog = '';

    $mail->Debugoutput = function($str, $level) use (&$debugLog) {
        $debugLog .= $str . "\n";
    };

    try {

        // SMTP Zimbra interno
        $mail->SMTPDebug = $debug ? 2 : 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.guaiba.local';
        $mail->SMTPAuth = false;  
        $mail->Port = 25;
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;

        // Remetente oficial
        $mail->setFrom('nao-responda@guaiba.rs.gov.br', 'Sistema de Protocolos');

        // Destinatário
        $mail->addAddress($destinatarioEmail, $destinatarioNome);
        $mail->addCC('ti@guaiba.rs.gov.br', 'TI - Guaíba');

        // Assunto
        $mail->Subject = "Protocolo de Entrega #{$idProtocolo}";

        // Corpo HTML
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        $mail->Body = "
            <h2 style='font-family: Arial;'>Olá, {$destinatarioNome}!</h2>
            <p style='font-family: Arial; font-size: 14px;'>
                Seu protocolo foi gerado com sucesso.
            </p>

            <p style='font-family: Arial; font-size: 14px;'>
                <strong>ID do Protocolo:</strong> {$idProtocolo}<br>
                <strong>Data e Hora:</strong> {$dataHora} (GMT-3)
            </p>

            <p style='font-family: Arial; font-size: 14px;'>
                O PDF está anexado a este e-mail.
            </p>

            <p style='margin-top: 20px; font-family: Arial; font-size: 14px;'>
                Atenciosamente,<br>
                <strong>Equipe de TI – Prefeitura de Guaíba</strong>
            </p>
        ";

        $mail->AltBody = "Olá {$destinatarioNome}, seu protocolo {$idProtocolo} foi gerado. O PDF está em anexo.";

        // 🔥 ANEXO DO PDF (recebido via upload)
        if ($pdfFile && is_uploaded_file($pdfFile['tmp_name'])) {
            $mail->addAttachment($pdfFile['tmp_name'], 'protocolo.pdf');
        }

        // Enviar
        $mail->send();

        return ['success' => true, 'message' => 'E-mail enviado com sucesso!', 'debug' => $debugLog];

    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo, 'debug' => $debugLog];
    }
}
?>
