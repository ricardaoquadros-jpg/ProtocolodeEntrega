<?php
header('Content-Type: application/json');

// CONFIGURAÇÃO DO BANCO DE DADOS (EDITE AQUI)
$host = 'localhost';
$db   = 'banco';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $conn->connect_error]));
}

// Recebe o JSON do Javascript
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
    exit;
}

// Inserir Protocolo (Recebedor)
$stmt = $conn->prepare("INSERT INTO protocolos (nome_recebedor, cpf_matricula, telefone, email, assinatura_base64) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $input['nome'], $input['cpf'], $input['telefone'], $input['email'], $input['assinatura']);

if ($stmt->execute()) {
    $protocolo_id = $stmt->insert_id; // Pega o ID gerado

    // Inserir os Itens (Patrimônios)
    $stmt_item = $conn->prepare("INSERT INTO protocolo_itens (protocolo_id, patrimonio_codigo, tipo_equipamento) VALUES (?, ?, ?)");
    
    foreach ($input['itens'] as $item) {
        $stmt_item->bind_param("iss", $protocolo_id, $item['patrimonio'], $item['equipamento']);
        $stmt_item->execute();
    }

    echo json_encode(['success' => true, 'id' => $protocolo_id, 'data' => date('d/m/Y H:i:s')]);

    // --- ENVIAR EMAIL AUTOMÁTICO ---
    require_once 'enviar_email.php';
    $dataHora = date('d/m/Y H:i:s');
    enviarEmailProtocolo($input['email'], $input['nome'], $protocolo_id, $dataHora);

} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar protocolo: ' . $stmt->error]);
}

$conn->close();
?>