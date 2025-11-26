<?php
session_start();
define('APP_RUNNING', true);

/* --- SEGURANÇA E LOGS --- */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs_php_errors.log');

/* --- BLOQUEIA ACESSO DE NÃO LOGADOS --- */
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php");
    exit;
}

/* --- VALIDA ID --- */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID inválido.");
}

$id_protocolo = intval($_GET['id']);

/* --- CONEXÃO CENTRAL --- */
if (!file_exists(__DIR__ . '/conexao.php')) {
    die("Erro: conexao.php não encontrado.");
}
require __DIR__ . '/conexao.php';

/* ===========================================================
    1. BUSCAR PROTOCOLO
=========================================================== */
$stmt = $conn->prepare("
    SELECT nome_recebedor, cpf_matricula, telefone, email, assinatura_base64, data_criacao
    FROM protocolos WHERE id = ?
");
$stmt->bind_param("i", $id_protocolo);
$stmt->execute();
$res = $stmt->get_result();
$proto = $res->fetch_assoc();

if (!$proto) {
    die("Protocolo não encontrado.");
}

/* ===========================================================
    2. BUSCAR ITENS
=========================================================== */
$stmt2 = $conn->prepare("
    SELECT patrimonio_codigo, tipo_equipamento
    FROM protocolo_itens
    WHERE protocolo_id = ?
");
$stmt2->bind_param("i", $id_protocolo);
$stmt2->execute();
$res_itens = $stmt2->get_result();

$itens = [];
while ($row = $res_itens->fetch_assoc()) {
    $itens[] = $row;
}

/* ===========================================================
    3. PREPARAR DADOS PARA JS
=========================================================== */
$data_hora = date('d/m/Y H:i:s', strtotime($proto['data_criacao']));

$dados_js = [
    "nome"       => $proto["nome_recebedor"],
    "cpf"        => $proto["cpf_matricula"],
    "telefone"   => $proto["telefone"],
    "email"      => $proto["email"],
    "assinatura" => $proto["assinatura_base64"],
    "itens"      => $itens
];

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Protocolo #<?= $id_protocolo ?></title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        body {
            font-family: sans-serif;
            text-align: center;
            padding-top: 60px;
            color: #444;
        }
        .loading {
            font-size: 20px;
        }
    </style>
</head>
<body>

<div class="loading">Gerando PDF, aguarde...</div>

<script>
    const dados = <?= json_encode($dados_js); ?>;
    const dataHora = "<?= $data_hora ?>";
    const idProtocolo = "<?= $id_protocolo ?>";
    const LOGO_PREFEITURA_URL = "https://i.imgur.com/Hi25PGf.jpeg";

    async function gerarPDF() {

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        const larguraPagina = doc.internal.pageSize.getWidth();
        const alturaPagina = doc.internal.pageSize.getHeight();
        const corIndigo = [67, 56, 202];

        let y = 15;

        /* ===========================================================
            CABEÇALHO
        ============================================================ */
        doc.setFontSize(18);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(...corIndigo);
        doc.text("PROTOCOLO DE ENTREGA E RECEBIMENTO", larguraPagina/2, y, {align: "center"});

        y += 8;

        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.setTextColor(120);
        doc.text(`ID: #${idProtocolo} | Data: ${dataHora}`, larguraPagina/2, y, {align: "center"});

        y += 10;
        doc.setLineWidth(0.8);
        doc.setDrawColor(...corIndigo);
        doc.line(10, y, larguraPagina - 10, y);
        y += 10;

        /* ===========================================================
            DADOS DO RECEBEDOR
        ============================================================ */
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(40);
        doc.text("Dados do Recebedor", 10, y);
        y += 10;

        doc.setFontSize(11);
        doc.setFont("helvetica", "normal");

        doc.text(`Nome: ${dados.nome}`, 10, y); y += 8;
        doc.text(`CPF/Matrícula: ${dados.cpf}`, 10, y); y += 8;
        doc.text(`Telefone: ${dados.telefone}`, 10, y); y += 8;
        doc.text(`Email: ${dados.email || "Não informado"}`, 10, y); y += 10;

        doc.setLineWidth(0.1);
        doc.setDrawColor(180);
        doc.line(10, y, larguraPagina - 10, y);
        y += 10;

        /* ===========================================================
            ITENS ENTREGUES
        ============================================================ */
        doc.setFontSize(14);
        doc.setFont("helvetica", "bold");
        doc.setTextColor(40);
        doc.text("Patrimônios Entregues", 10, y);
        y += 10;

        doc.setFontSize(10);
        doc.setTextColor(100);
        doc.text("CÓDIGO", 15, y);
        doc.text("TIPO/DESCRIÇÃO", 60, y);

        y += 6;
        doc.setFontSize(11);
        doc.setTextColor(30);

        dados.itens.forEach(it => {
            doc.text(it.patrimonio_codigo, 15, y);
            doc.text(it.tipo_equipamento, 60, y);
            y += 6;
        });

        y += 5;
        doc.setLineWidth(0.1);
        doc.setDrawColor(180);
        doc.line(10, y, larguraPagina - 10, y);
        y += 10;

        /* ===========================================================
            ASSINATURA
        ============================================================ */
        doc.setFontSize(12);
        doc.setFont("helvetica", "bold");
        doc.text("Assinatura do Recebedor:", 10, y);

        y += 6;
        doc.setDrawColor(150);
        doc.rect(10, y, 100, 40);

        if (dados.assinatura) {
            await new Promise(res => {
                const img = new Image();
                img.onload = () => {
                    doc.addImage(img, "PNG", 12, y+2, 96, 36);
                    res();
                };
                img.onerror = res;
                img.src = dados.assinatura;
            });
        }

        y += 50;

        /* ===========================================================
            RODAPÉ
        ============================================================ */
        doc.setFontSize(8);
        doc.setTextColor(120);
        doc.text("Documento gerado automaticamente pelo sistema Protocolo TI.", larguraPagina/2, y, {align: "center"});

        /* ===========================================================
            LOGO
        ============================================================ */
        const LOGO_WIDTH = 45;
        const LOGO_HEIGHT = 45;
        const xLogo = larguraPagina - LOGO_WIDTH - 15;
        const yLogo = alturaPagina - LOGO_HEIGHT - 15;

        await new Promise(res => {
            const logo = new Image();
            logo.onload = () => {
                doc.addImage(logo, "PNG", xLogo, yLogo, LOGO_WIDTH, LOGO_HEIGHT);
                res();
            };
            logo.onerror = res;
            logo.src = LOGO_PREFEITURA_URL;
        });

        /* ===========================================================
            MOSTRAR PDF
        ============================================================ */
        const blob = doc.output("blob");
        const url = URL.createObjectURL(blob);

        window.location.href = url;
    }

    window.onload = gerarPDF;
</script>

</body>
</html>
