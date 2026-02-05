<?php
define('APP_RUNNING', true);

require_once __DIR__ . '/utils/config_seguranca.php';
require_once __DIR__ . '/utils/seguranca.php';

session_start();
aplicarHeadersSeguranca();

// Verifica se o usuário está logado
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php?redirect=novo_emprestimo.php");
    exit;
}

// --- VERIFICAÇÃO DE PERFIL COMPLETO ---
require_once __DIR__ . '/conexao.php';
$id_usuario = intval($_SESSION['admin_id']);
$stmtProfile = $conn->prepare("SELECT nome_completo, email, telefone FROM usuarios_admin WHERE id = ?");
$stmtProfile->bind_param("i", $id_usuario);
$stmtProfile->execute();
$resProfile = $stmtProfile->get_result();
$userProfile = $resProfile->fetch_assoc();

if (!$userProfile || empty($userProfile['nome_completo']) || empty($userProfile['email']) || empty($userProfile['telefone'])) {
    echo "<script>
        alert('Para registrar empréstimos, você precisa completar seu perfil (Nome, Email e Telefone).');
        window.location.href = 'conta.php';
    </script>";
    exit;
}

// Data padrão de devolução (7 dias)
$dataPrevista = date('Y-m-d', strtotime('+7 days'));
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Empréstimo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fff7ed; /* orange-50 */
        }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .custom-input {
            padding-left: 3.2rem;
        }

        canvas {
            cursor: crosshair;
        }
    </style>
</head>

<body class="p-4 md:p-8 text-slate-800">

    <header class="max-w-5xl mx-auto flex justify-between items-center mb-6">
        <div class="flex items-center gap-2">
            <svg class="w-6 h-6 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                </path>
            </svg>
            <h1 class="text-xl font-bold text-slate-800">Empréstimo</h1>
        </div>
        <a href="index.php"
            class="bg-orange-50 text-orange-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-orange-100 flex items-center gap-2 border border-orange-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Voltar ao Menu
        </a>
    </header>

    <div class="max-w-5xl mx-auto space-y-6">

        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Dados do Responsável</h2>
            <p class="text-sm text-slate-500 mb-4">Registro de empréstimo de computadores, teclados, TVs e outros equipamentos.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nome Completo</label>
                    <div class="relative">
                        <svg class="w-5 h-5 input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                            </path>
                        </svg>
                        <input type="text" id="responsavel_nome" placeholder="Ex: João da Silva" class="w-full bg-orange-50 border border-orange-100 text-slate-900 
         text-sm rounded-md focus:ring-orange-500 focus:border-orange-500 
         block pl-12 pr-3 py-2 shadow-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- CPF -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">CPF</label>
                        <div class="relative">
                            <input type="text" id="responsavel_cpf" maxlength="14" placeholder="000.000.000-00" class="w-full bg-orange-50 border border-orange-100 text-slate-900 
                            text-sm rounded-md focus:ring-orange-500 focus:border-orange-500 
                            block p-3 shadow-sm" oninput="mascaraCPF(this)" onblur="verificarCPF(this)">
                        </div>
                    </div>

                    <!-- Telefone -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Telefone</label>
                        <div class="relative">
                            <svg class="w-5 h-5 input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                </path>
                            </svg>
                            <input type="text" id="responsavel_telefone" maxlength="15" placeholder="(51)99999-1234" class="w-full bg-orange-50 border border-orange-100 text-slate-900 
                        text-sm rounded-md focus:ring-orange-500 focus:border-orange-500 
                        block pl-12 pr-3 py-2 shadow-sm" oninput="mascaraTelefone(this)">
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <div class="relative">
                        <svg class="w-5 h-5 input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        <input type="email" id="responsavel_email" placeholder="exemplo@email.com" class="w-full bg-orange-50 border border-orange-100 text-slate-900 
        text-sm rounded-md focus:ring-orange-500 focus:border-orange-500 
        block pl-12 pr-3 py-2 shadow-sm">
                    </div>
                </div>

                <!-- Setor -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Setor/Departamento</label>
                    <div class="relative">
                        <svg class="w-5 h-5 input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                        <input type="text" id="responsavel_setor" placeholder="Ex: Secretaria de Educação, TI..." class="w-full bg-orange-50 border border-orange-100 text-slate-900 
        text-sm rounded-md focus:ring-orange-500 focus:border-orange-500 
        block pl-12 pr-3 py-2 shadow-sm">
                    </div>
                </div>

                <!-- Prazo e Observações (Extra fields for Loan) -->
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Previsão de Devolução</label>
                        <input type="date" id="data_previsao" value="<?= $dataPrevista ?>" class="w-full bg-orange-50 border border-orange-100 text-slate-900 
                            text-sm rounded-md focus:ring-orange-500 focus:border-orange-500 
                            block p-3 shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Observações</label>
                        <input type="text" id="observacoes" placeholder="Opcional" class="w-full bg-orange-50 border border-orange-100 text-slate-900 
                            text-sm rounded-md focus:ring-orange-500 focus:border-orange-500 
                            block p-3 shadow-sm">
                    </div>
                </div>

                <!-- EQUIPAMENTOS -->
                <div class="pt-4 border-t border-slate-100">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-md font-bold text-slate-800">Equipamentos</h3>
                        <button type="button" onclick="adicionarEquipamento()"
                            class="text-orange-600 hover:text-orange-800 text-sm font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Adicionar Item
                        </button>
                    </div>

                    <div id="lista-equipamentos" class="space-y-4">
                        <!-- Itens via JS -->
                    </div>
                </div>

                <!-- ASSINATURA -->
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-md font-bold text-slate-800 mb-2">Assinatura do Recebedor</h3>
                    <div class=" relative border-2 border-dashed border-orange-200 bg-orange-50 rounded-lg h-48 flex items-center justify-center">
                        <div class="absolute pointer-events-none text-orange-300 font-medium select-none">Assine aqui</div>
                        <canvas id="signature-pad" class="w-full h-full absolute top-0 left-0 rounded-lg"></canvas>
                        <button onclick="limparAssinatura()"
                            class="absolute bottom-2 right-2 text-xs text-orange-500 hover:text-orange-700 flex items-center gap-1 bg-white px-2 py-1 rounded shadow-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            Limpar
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-4 pb-12">
                     <button onclick="salvarEmprestimo()"
                        class="bg-orange-700 hover:bg-orange-800 text-white font-medium rounded-md text-sm px-6 py-2.5 shadow-md flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                        Salvar e Gerar PDF
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        // Funções de Utilidade
        function limpar(str) { return str.replace(/[<>]/g, "").trim(); }

        // MÁSCARAS
        function mascaraCPF(input) {
            let v = input.value.replace(/\D/g, "").slice(0, 11);
            if (v.length >= 3) v = v.replace(/(\d{3})(\d)/, "$1.$2");
            if (v.length >= 6) v = v.replace(/(\d{3})(\d)/, "$1.$2");
            if (v.length >= 9) v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            input.value = v;
        }

        function mascaraTelefone(input) {
            let v = input.value.replace(/\D/g, "").slice(0, 11);
            if (v.length > 2) v = v.replace(/^(\d{2})(\d)/, "($1)$2");
            if (v.length > 7) v = v.replace(/^(\(\d{2}\))(\d{5})(\d)/, "$1$2-$3");
            input.value = v;
        }

        function mascaraPatrimonio(input) {
            let v = input.value.replace(/\D/g, "").slice(0, 6);
            input.value = v;
        }

        function validarCPF(cpf) {
            cpf = cpf.replace(/[^\d]+/g, '');
            if (cpf == '') return false;
            // Elimina CPFs invalidos conhecidos
            if (cpf.length != 11 ||
                cpf == "00000000000" ||
                cpf == "11111111111" ||
                cpf == "22222222222" ||
                cpf == "33333333333" ||
                cpf == "44444444444" ||
                cpf == "55555555555" ||
                cpf == "66666666666" ||
                cpf == "77777777777" ||
                cpf == "88888888888" ||
                cpf == "99999999999")
                return false;
            // Valida 1o digito
            add = 0;
            for (i = 0; i < 9; i++)
                add += parseInt(cpf.charAt(i)) * (10 - i);
            rev = 11 - (add % 11);
            if (rev == 10 || rev == 11)
                rev = 0;
            if (rev != parseInt(cpf.charAt(9)))
            return false;
            // Valida 2o digito
            add = 0;
            for (i = 0; i < 10; i++)
                add += parseInt(cpf.charAt(i)) * (11 - i);
            rev = 11 - (add % 11);
            if (rev == 10 || rev == 11)
                rev = 0;
            if (rev != parseInt(cpf.charAt(10)))
                return false;
            return true;
        }

        function verificarCPF(input) {
            const cpf = input.value;
            if (cpf.replace(/\D/g, '').length === 11) {
                if (!validarCPF(cpf)) {
                    alert('CPF inválido!');
                    input.value = '';
                    input.focus();
                }
            }
        }

        // SIGNATURE PAD
        const canvas = document.getElementById('signature-pad');
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
        }
        window.onresize = resizeCanvas;
        resizeCanvas();

        const signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgba(255, 255, 255, 0)' });
        function limparAssinatura() { signaturePad.clear(); }

        // EQUIPAMENTOS
        function adicionarEquipamento() {
            const container = document.getElementById('lista-equipamentos');
            const div = document.createElement('div');
            // Changed to match Protocol UI style but with 2 columns: Code (6) and Type (6). Desc Removed.
            div.className = 'patrimonio-item grid grid-cols-1 md:grid-cols-12 gap-4 items-end pt-2 border-t border-orange-50 relative';
            div.innerHTML = `
                <div class="md:col-span-6">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Código do Patrimônio</label>
                    <div class="relative">
                        <svg class="w-5 h-5 input-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <input type="text" name="patrimonio_cod" placeholder="Novo Patrimônio" maxlength="6" oninput="mascaraPatrimonio(this)" class="w-full bg-orange-50 border border-orange-100 text-slate-900 text-sm rounded-md focus:ring-orange-500 focus:border-orange-500 block pl-12 pr-3 py-2 shadow-sm">
                    </div>
                </div>
                <div class="md:col-span-5">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Equipamento</label>
                    <select name="equipamento_tipo" class="w-full bg-orange-50 border border-orange-100 text-slate-900 text-sm rounded-md focus:ring-orange-500 focus:border-orange-500 block p-2.5 shadow-sm">
                        <option value="Notebook">Notebook</option>
                        <option value="Monitor">Monitor</option>
                        <option value="Computador">Computador</option>
                        <option value="Periferico">Periférico</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>
                <div class="md:col-span-1 flex justify-center pb-1">
                    <button type="button" onclick="this.closest('.patrimonio-item').remove()" class="text-red-400 hover:text-red-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            `;
            container.appendChild(div);
        }
        adicionarEquipamento(); // Add initial item

        // SALVAR
        async function salvarEmprestimo() {
            const btn = document.querySelector('button[onclick="salvarEmprestimo()"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = "Salvando...";

            const nome = document.getElementById('responsavel_nome').value.trim();
            const cpf = document.getElementById('responsavel_cpf').value.replace(/\D/g, "");
            const telefone = document.getElementById('responsavel_telefone').value.replace(/\D/g, "");
            const email = document.getElementById('responsavel_email').value.trim();
            const setor = document.getElementById('responsavel_setor').value.trim();
            const dataPrevisao = document.getElementById('data_previsao').value;
            const observacoes = document.getElementById('observacoes').value.trim();
            const assinatura = signaturePad.toDataURL(); // Base64

            const itens = [];
            document.querySelectorAll('.patrimonio-item').forEach(item => {
                const pat = item.querySelector('[name="patrimonio_cod"]').value.trim();
                const tipo = item.querySelector('[name="equipamento_tipo"]').value;
                if (pat) itens.push({ patrimonio: pat, tipo: tipo });
            });

            if (!nome || !cpf || !telefone || !dataPrevisao || itens.length === 0) {
                alert('Preencha os campos obrigatórios e adicione pelo menos um item.');
                btn.disabled = false;
                btn.innerHTML = originalText;
                return;
            }

            const payload = {
                responsavel_nome: nome,
                responsavel_cpf: cpf,
                responsavel_telefone: telefone,
                responsavel_email: email,
                responsavel_setor: setor,
                data_previsao: dataPrevisao,
                observacoes: observacoes,
                assinatura: assinatura,
                itens: itens
            };

            try {
                const res = await fetch('salvar_emprestimo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (result.success) {
                    alert('Empréstimo registrado! Gerando PDF...');
                    await gerarPDF(payload, result.id);
                    window.location.href = 'emprestimos.php'; // Redirect after PDF
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (err) {
                console.error(err);
                alert('Erro de conexão.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        // GERAR PDF
        async function gerarPDF(dados, idEmprestimo) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const w = doc.internal.pageSize.getWidth();
            let y = 15;
            const orange = [234, 88, 12]; // Orange-600

            // Cabeçalho
            doc.setFontSize(18);
            doc.setTextColor(...orange);
            doc.setFont("helvetica", "bold");
            doc.text("COMPROVANTE DE EMPRÉSTIMO", w / 2, y, { align: "center" });
            y += 8;

            doc.setFontSize(10);
            doc.setTextColor(150);
            doc.setFont("helvetica", "normal");
            doc.text(`ID: #${idEmprestimo} | Data: ${new Date().toLocaleDateString('pt-BR')}`, w / 2, y, { align: "center" });
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
            doc.text(`Nome: ${dados.responsavel_nome}`, 10, y); y += 6;
            doc.text(`CPF: ${dados.responsavel_cpf}`, 10, y); y += 6;
            doc.text(`Telefone: ${dados.responsavel_telefone}`, 10, y); y += 6;
            doc.text(`Setor: ${dados.responsavel_setor}`, 10, y); y += 10;

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
            doc.text(`Previsão de Devolução: ${new Date(dados.data_previsao).toLocaleDateString('pt-BR')}`, 10, y); y += 6;
            if (dados.observacoes) {
                doc.text(`Obs: ${dados.observacoes}`, 10, y); y += 6;
            }
            y += 6;

            // Itens
            doc.setFontSize(12);
            doc.setFont("helvetica", "bold");
            doc.text("Equipamentos", 10, y);
            y += 8;
            doc.setFontSize(10);
            doc.setFont("helvetica", "normal");
            
            dados.itens.forEach(item => {
                doc.text(`- [${item.patrimonio}] ${item.tipo}`, 15, y);
                y += 6;
            });

            y += 10;
            doc.line(10, y, w - 10, y);
            y += 10;

            // Assinatura
            if (dados.assinatura) {
                doc.setFontSize(12);
                doc.setFont("helvetica", "bold");
                doc.text("Assinatura do Recebedor:", 10, y);
                y += 5;
                doc.rect(10, y, 100, 40);
                doc.addImage(dados.assinatura, 'PNG', 12, y + 2, 96, 36);
                y += 50;
            }

            // Rodape
            doc.setFontSize(8);
            doc.setTextColor(150);
            doc.text("Documento gerado automaticamente.", w / 2, y, { align: "center" });

            doc.save(`emprestimo_${idEmprestimo}.pdf`);
        }
    </script>
</body>
</html>
