<?php
session_start();

// 1. SEGURANÇA: Verifica login
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("ID do protocolo não fornecido.");
}

$id_protocolo = intval($_GET['id']);

// CONFIGURAÇÃO DO BANCO
$host = 'localhost';
$db   = 'banco';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// 2. BUSCAR DADOS DO PROTOCOLO
$stmt = $conn->prepare("SELECT * FROM protocolos WHERE id = ?");
$stmt->bind_param("i", $id_protocolo);
$stmt->execute();
$result = $stmt->get_result();
$protocolo = $result->fetch_assoc();

if (!$protocolo) {
    die("Protocolo não encontrado.");
}

// 3. BUSCAR ITENS DO PROTOCOLO
$stmt_itens = $conn->prepare("SELECT patrimonio_codigo, tipo_equipamento FROM protocolo_itens WHERE protocolo_id = ?");
$stmt_itens->bind_param("i", $id_protocolo);
$stmt_itens->execute();
$res_itens = $stmt_itens->get_result();

$itens = [];
while ($row = $res_itens->fetch_assoc()) {
    $itens[] = $row;
}

// Montar objeto de dados para o JS
$dados_js = [
    'nome' => $protocolo['nome_recebedor'],
    'cpf' => $protocolo['cpf_matricula'],
    'telefone' => $protocolo['telefone'],
    'email' => $protocolo['email'],
    'assinatura' => $protocolo['assinatura_base64'],
    'itens' => $itens
];

$data_hora_formatada = date('d/m/Y H:i:s', strtotime($protocolo['data_criacao']));

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar PDF - Protocolo #<?php echo $id_protocolo; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { font-family: sans-serif; text-align: center; padding-top: 50px; color: #555; }
        .loading { font-size: 18px; }
    </style>
</head>
<body>

    <div class="loading">Gerando PDF, aguarde...</div>

    <script>
        const dados = <?php echo json_encode($dados_js); ?>;
        const dataHora = "<?php echo $data_hora_formatada; ?>";
        const idProtocolo = "<?php echo $id_protocolo; ?>";
        
        // URL da Logo (Mesma do index.html)
        const LOGO_PREFEITURA_URL = 'https://i.imgur.com/Hi25PGf.jpeg';

        async function gerarPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const larguraPagina = doc.internal.pageSize.getWidth();
            const alturaPagina = doc.internal.pageSize.getHeight();
            const corIndigo = [67, 56, 202];
            let y = 15;

            // --- 1. CABEÇALHO ---
            doc.setFontSize(18);
            doc.setTextColor(corIndigo[0], corIndigo[1], corIndigo[2]);
            doc.setFont("helvetica", "bold");
            doc.text("PROTOCOLO DE ENTREGA E RECEBIMENTO", larguraPagina / 2, y, null, null, "center");

            y += 7;
            doc.setFontSize(10);
            doc.setTextColor(150);
            doc.setFont("helvetica", "normal");
            doc.text(`ID: #${idProtocolo} | Data e Hora: ${dataHora}`, larguraPagina / 2, y, null, null, "center");

            // Linha Separadora Principal
            y += 7;
            doc.setLineWidth(0.8);
            doc.setDrawColor(corIndigo[0], corIndigo[1], corIndigo[2]);
            doc.line(10, y, larguraPagina - 10, y);
            y += 10;

            // --- 2. DADOS DO RECEBEDOR ---
            doc.setFontSize(14);
            doc.setTextColor(50);
            doc.setFont("helvetica", "bold");
            doc.text("Dados do Recebedor", 10, y);

            y += 10;
            doc.setFontSize(11);
            doc.setFont("helvetica", "normal");

            // Campos
            doc.text(`Nome: ${dados.nome}`, 10, y);
            y += 10;
            doc.text(`CPF/Matrícula: ${dados.cpf}`, 10, y);
            y += 10;
            doc.text(`Telefone: ${dados.telefone}`, 10, y);
            y += 10;
            doc.text(`Email: ${dados.email || 'Não informado'}`, 10, y);

            // Linha Separadora
            y += 7;
            doc.setLineWidth(0.1);
            doc.setDrawColor(200);
            doc.line(10, y, larguraPagina - 10, y);
            y += 10;

            // --- 3. PATRIMÔNIOS ENTREGUES ---
            doc.setFontSize(14);
            doc.setTextColor(50);
            doc.setFont("helvetica", "bold");
            doc.text("Patrimônios Entregues", 10, y);

            // Cabeçalho da Lista
            y += 10;
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text("CÓDIGO", 15, y);
            doc.text("TIPO/DESCRIÇÃO", 60, y);

            // Itens
            y += 6;
            doc.setFontSize(11);
            doc.setFont("helvetica", "normal");

            dados.itens.forEach((item) => {
                y += 4;
                doc.text(`${item.patrimonio_codigo}`, 15, y);
                doc.text(`${item.tipo_equipamento}`, 60, y);
            });

            // Linha Separadora
            y += 5;
            doc.setLineWidth(0.1);
            doc.setDrawColor(200);
            doc.line(10, y, larguraPagina - 10, y);
            y += 7;

            // --- 4. ASSINATURA ---
            doc.setFontSize(12);
            doc.setTextColor(50);
            doc.setFont("helvetica", "bold");
            doc.text("Assinatura do Recebedor:", 10, y);

            y += 6;
            doc.setDrawColor(150);
            doc.rect(10, y, 100, 40);

            if (dados.assinatura) {
                await new Promise((resolve) => {
                    const img = new Image();
                    img.onload = function () {
                        doc.addImage(img, 'PNG', 12, y + 2, 96, 36);
                        resolve();
                    };
                    img.onerror = resolve;
                    img.src = dados.assinatura;
                });
            } else {
                doc.setFontSize(10);
                doc.setTextColor(200);
                doc.text("Sem Assinatura Digital", 50, y + 20, null, null, "center");
            }

            y += 50;

            // --- 5. RODAPÉ ---
            doc.setFontSize(8);
            doc.setTextColor(150);
            doc.setFont("helvetica", "normal");
            doc.text("Este documento é uma via de protocolo gerada pelo sistema e possui validade legal.", larguraPagina / 2, y, null, null, "center");

            // --- 6. LOGO DA PREFEITURA ---
            const MARGEM = 15;
            const LOGO_WIDTH = 50; 
            const LOGO_HEIGHT = 50; 

            const xLogo = larguraPagina - LOGO_WIDTH - MARGEM;
            const yLogo = alturaPagina - LOGO_HEIGHT - MARGEM;

            if (LOGO_PREFEITURA_URL) {
                await new Promise((resolve) => {
                    const logo = new Image();
                    logo.crossOrigin = 'Anonymous';
                    logo.onload = function () {
                        doc.addImage(logo, 'PNG', xLogo, yLogo, LOGO_WIDTH, LOGO_HEIGHT);
                        resolve();
                    };
                    logo.onerror = function () {
                        console.warn('Falha ao carregar a logo.');
                        resolve();
                    };
                    logo.src = LOGO_PREFEITURA_URL;
                });
            }

            // Abrir PDF em nova aba (Blob URL)
            const pdfBlob = doc.output('blob');
            const pdfUrl = URL.createObjectURL(pdfBlob);
            
            // Substitui a página atual pelo PDF ou abre em iframe full screen
            window.location.href = pdfUrl; 
        }

        // Inicia a geração assim que carregar
        window.onload = gerarPDF;
    </script>
</body>
</html>
