<?php
define('APP_RUNNING', true);

/* --- CONFIGURAÇÃO DE SEGURANÇA --- */
require_once __DIR__ . '/utils/config_seguranca.php';

session_start();
aplicarHeadersSeguranca();

/* --- LOGS DE ERRO --- */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs_php_errors.log');

require_once __DIR__ . '/utils/seguranca.php';

/* --- BANCO DE DADOS --- */
require __DIR__ . '/conexao.php';

/* --- VERIFICAR LOGIN --- */
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php");
    exit;
}

/* --- BUSCAR DADOS DO EMPRÉSTIMO --- */
$emprestimoId = (int)($_GET['id'] ?? 0);

if ($emprestimoId <= 0) {
    header("Location: emprestimos.php");
    exit;
}

// Buscar empréstimo com dados do criador e quem devolveu
$sql = "SELECT e.*, 
        uc.nome_completo as criador_nome, uc.email as criador_email,
        ud.nome_completo as devolvedor_nome
        FROM emprestimos e
        LEFT JOIN usuarios_admin uc ON e.criado_por_id = uc.id
        LEFT JOIN usuarios_admin ud ON e.devolvido_por_id = ud.id
        WHERE e.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $emprestimoId);
$stmt->execute();
$emprestimo = $stmt->get_result()->fetch_assoc();

if (!$emprestimo) {
    header("Location: emprestimos.php");
    exit;
}

// Buscar itens do empréstimo
$sqlItens = "SELECT * FROM itens_emprestimo WHERE emprestimo_id = ?";
$stmtItens = $conn->prepare($sqlItens);
$stmtItens->bind_param("i", $emprestimoId);
$stmtItens->execute();
$itens = $stmtItens->get_result()->fetch_all(MYSQLI_ASSOC);

// Status config
$statusConfig = [
    'ativo' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Ativo', 'icon' => '📦'],
    'atrasado' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'label' => 'Atrasado', 'icon' => '⚠️'],
    'devolvido' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'label' => 'Devolvido', 'icon' => '✅']
];
$status = $statusConfig[$emprestimo['status']] ?? $statusConfig['ativo'];

// Verificar função do usuário
$userId = $_SESSION['admin_id'];
$stmtFunc = $conn->prepare("SELECT funcao FROM usuarios_admin WHERE id = ?");
$stmtFunc->bind_param("i", $userId);
$stmtFunc->execute();
$userFunc = $stmtFunc->get_result()->fetch_assoc();
$is_admin = ($userFunc && trim($userFunc['funcao']) === 'Administrador');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Empréstimo #<?= $emprestimoId ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>

<body class="bg-orange-50 flex h-screen overflow-hidden">

    <?php $pagina_ativa = 'emprestimos'; include __DIR__ . '/utils/sidebar.php'; ?>

    <!-- Conteúdo Principal -->
    <main class="flex-1 overflow-y-auto p-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <a href="emprestimos.php" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Empréstimo #<?= $emprestimoId ?></h1>
                    <p class="text-sm text-slate-500">Detalhes do empréstimo de equipamentos</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                 <button onclick="gerarPDF()" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Baixar PDF
                </button>
                <span class="<?= $status['bg'] ?> <?= $status['text'] ?> px-4 py-2 rounded-full text-sm font-bold">
                    <?= $status['icon'] ?> <?= $status['label'] ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Coluna Principal -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Dados do Responsável -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-amber-50 px-6 py-4 border-b border-amber-100">
                        <h2 class="text-lg font-bold text-amber-900 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Responsável pelo Empréstimo
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase">Nome</label>
                                <p class="text-lg font-medium text-slate-800"><?= htmlspecialchars($emprestimo['responsavel_nome']) ?></p>
                            </div>
                            
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase">CPF</label>
                                <p class="text-lg text-slate-700"><?= htmlspecialchars($emprestimo['responsavel_cpf'] ?: '-') ?></p>
                            </div>
                            
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase">Telefone</label>
                                <p class="text-lg text-slate-700"><?= htmlspecialchars($emprestimo['responsavel_telefone'] ?: '-') ?></p>
                            </div>
                            
                            <div>
                                <label class="text-xs font-bold text-slate-400 uppercase">Email</label>
                                <p class="text-lg text-slate-700"><?= htmlspecialchars($emprestimo['responsavel_email'] ?: '-') ?></p>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="text-xs font-bold text-slate-400 uppercase">Setor/Departamento</label>
                                <p class="text-lg text-slate-700"><?= htmlspecialchars($emprestimo['responsavel_setor'] ?: '-') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Equipamentos -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            Equipamentos Emprestados
                            <span class="bg-amber-100 text-amber-700 text-xs font-bold px-2 py-1 rounded-full"><?= count($itens) ?> item(s)</span>
                        </h2>
                    </div>
                    
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($itens as $item): ?>
                        <div class="p-4 flex items-center gap-4 hover:bg-slate-50 transition">
                            <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($item['patrimonio_codigo']) ?></p>
                                <p class="text-sm text-slate-500"><?= htmlspecialchars($item['equipamento_tipo']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                 <!-- Assinatura -->
                <?php if (!empty($emprestimo['assinatura_base64'])): ?>
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-800">Assinatura do Recebedor</h2>
                    </div>
                    <div class="p-6 flex justify-center bg-gray-50">
                        <img src="<?= $emprestimo['assinatura_base64'] ?>" alt="Assinatura" class="max-h-32 border border-gray-200 rounded bg-white">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Observações -->
                <?php if ($emprestimo['observacoes']): ?>
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <h3 class="text-sm font-bold text-slate-400 uppercase mb-2">Observações</h3>
                    <p class="text-slate-700"><?= nl2br(htmlspecialchars($emprestimo['observacoes'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Coluna Lateral -->
            <div class="space-y-6">
                
                <!-- Datas -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <h3 class="text-sm font-bold text-slate-400 uppercase mb-4">Informações do Empréstimo</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs text-slate-400">Data do Empréstimo</label>
                            <p class="text-lg font-medium text-slate-800">
                                <?= date('d/m/Y', strtotime($emprestimo['data_emprestimo'])) ?>
                            </p>
                            <p class="text-xs text-slate-400"><?= date('H:i', strtotime($emprestimo['data_emprestimo'])) ?></p>
                        </div>
                        
                        <div>
                            <label class="text-xs text-slate-400">Prazo de Devolução</label>
                            <p class="text-lg font-bold <?= $emprestimo['status'] === 'atrasado' ? 'text-red-600' : 'text-amber-600' ?>">
                                <?= date('d/m/Y', strtotime($emprestimo['data_previsao_devolucao'])) ?>
                            </p>
                            <?php 
                            $diasRestantes = floor((strtotime($emprestimo['data_previsao_devolucao']) - time()) / 86400);
                            if ($emprestimo['status'] !== 'devolvido'): 
                            ?>
                            <p class="text-xs <?= $diasRestantes < 0 ? 'text-red-500' : 'text-slate-400' ?>">
                                <?= $diasRestantes < 0 ? abs($diasRestantes) . ' dia(s) de atraso' : $diasRestantes . ' dia(s) restantes' ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($emprestimo['data_devolucao']): ?>
                        <div>
                            <label class="text-xs text-slate-400">Data da Devolução</label>
                            <p class="text-lg font-medium text-green-600">
                                <?= date('d/m/Y H:i', strtotime($emprestimo['data_devolucao'])) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quem registrou -->
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <h3 class="text-sm font-bold text-slate-400 uppercase mb-4">Registro</h3>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-slate-400">Emprestado por</label>
                            <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($emprestimo['criador_nome'] ?? 'Não identificado') ?></p>
                        </div>
                        
                        <?php if ($emprestimo['devolvedor_nome']): ?>
                        <div>
                            <label class="text-xs text-slate-400">Devolvido para</label>
                            <p class="text-sm font-medium text-slate-700"><?= htmlspecialchars($emprestimo['devolvedor_nome']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ações -->
                <?php if ($emprestimo['status'] !== 'devolvido'): ?>
                <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                    <button onclick="confirmarDevolucao()" 
                        class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition font-bold flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Registrar Devolução
                    </button>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </main>

    <script>
        const dadosEmprestimo = <?= json_encode($emprestimo) ?>;
        const itensEmprestimo = <?= json_encode($itens) ?>;

        async function confirmarDevolucao() {
            if (!confirm('Confirmar a devolução deste empréstimo?')) return;

            try {
                const form = new FormData();
                form.append('id', <?= $emprestimoId ?>);

                const response = await fetch('devolver_emprestimo.php', {
                    method: 'POST',
                    body: form
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Devolução registrada com sucesso!');
                    location.reload();
                } else {
                    alert('❌ Erro: ' + result.message);
                }
            } catch (error) {
                console.error(error);
                alert('❌ Erro ao processar devolução.');
            }
        }

        async function gerarPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const w = doc.internal.pageSize.getWidth();
            let y = 15;
            const orange = [234, 88, 12]; // Orange RGB

            // Cabeçalho
            doc.setFontSize(18);
            doc.setTextColor(...orange);
            doc.setFont("helvetica", "bold");
            doc.text("COMPROVANTE DE EMPRÉSTIMO", w / 2, y, { align: "center" });
            y += 8;

            doc.setFontSize(10);
            doc.setTextColor(150);
            doc.setFont("helvetica", "normal");
            doc.text(`ID: #${dadosEmprestimo.id} | Data: ${new Date(dadosEmprestimo.data_emprestimo).toLocaleDateString('pt-BR')}`, w / 2, y, { align: "center" });
            y += 10;
            doc.setDrawColor(...orange);
            doc.setLineWidth(0.8);
            doc.line(10, y, w - 10, y);
            y += 10;

            // Dados Responsável
            doc.setFontSize(14);
            doc.setTextColor(50);
            doc.setFont("helvetica", "bold");
            doc.text("Dados do Responsável", 10, y);
            y += 8;

            doc.setFontSize(11);
            doc.setTextColor(30);
            doc.setFont("helvetica", "normal");
            doc.text(`Nome: ${dadosEmprestimo.responsavel_nome}`, 10, y); y += 6;
            doc.text(`CPF: ${dadosEmprestimo.responsavel_cpf || '-'}`, 10, y); y += 6;
            doc.text(`Telefone: ${dadosEmprestimo.responsavel_telefone || '-'}`, 10, y); y += 6;
            doc.text(`Setor: ${dadosEmprestimo.responsavel_setor || '-'}`, 10, y); y += 10;

            doc.setDrawColor(200);
            doc.setLineWidth(0.1);
            doc.line(10, y, w - 10, y);
            y += 10;

            // Dados Empréstimo
            doc.setFontSize(14);
            doc.setTextColor(50);
            doc.setFont("helvetica", "bold");
            doc.text("Detalhes do Empréstimo", 10, y);
            y += 8;

            doc.setFontSize(11);
            doc.setTextColor(30);
            doc.setFont("helvetica", "normal");
            doc.text(`Previsão de Devolução: ${new Date(dadosEmprestimo.data_previsao_devolucao).toLocaleDateString('pt-BR')}`, 10, y); y += 6;
            if (dadosEmprestimo.observacoes) {
                doc.text(`Obs: ${dadosEmprestimo.observacoes}`, 10, y); y += 6;
            }
            y += 6;

            // Itens
            doc.setFontSize(12);
            doc.setFont("helvetica", "bold");
            doc.text("Equipamentos", 10, y);
            y += 8;
            doc.setFontSize(10);
            doc.setFont("helvetica", "normal");
            
            itensEmprestimo.forEach(item => {
                doc.text(`- [${item.patrimonio_codigo}] ${item.equipamento_tipo}`, 15, y);
                y += 6;
            });

            y += 10;
            doc.line(10, y, w - 10, y);
            y += 10;

            // Assinatura
            if (dadosEmprestimo.assinatura_base64) {
                doc.setFontSize(12);
                doc.setFont("helvetica", "bold");
                doc.text("Assinatura do Recebedor:", 10, y);
                y += 5;
                doc.rect(10, y, 100, 40);
                doc.addImage(dadosEmprestimo.assinatura_base64, 'PNG', 12, y + 2, 96, 36);
                y += 50;
            }

            // Rodape
            doc.setFontSize(8);
            doc.setTextColor(150);
            doc.text("Documento gerado automaticamente.", w / 2, y, { align: "center" });

            doc.save(`emprestimo_${dadosEmprestimo.id}.pdf`);
        }
    </script>

</body>
</html>
