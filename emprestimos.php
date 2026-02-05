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
    header("Location: login.php?redirect=emprestimos.php");
    exit;
}

$userId = $_SESSION['admin_id'];
$stmtFunc = $conn->prepare("SELECT funcao FROM usuarios_admin WHERE id = ?");
$stmtFunc->bind_param("i", $userId);
$stmtFunc->execute();
$userFunc = $stmtFunc->get_result()->fetch_assoc();
$is_admin = ($userFunc && trim($userFunc['funcao']) === 'Administrador');

/* --- ATUALIZAR STATUS DE ATRASADOS --- */
$conn->query("UPDATE emprestimos SET status = 'atrasado' WHERE status = 'ativo' AND data_previsao_devolucao < CURDATE()");

/* --- FILTRO --- */
$filtro = $_GET['status'] ?? 'todos';
$busca = limparTexto($_GET['busca'] ?? '');

$where = "1=1";
if ($filtro === 'ativo') $where .= " AND e.status = 'ativo'";
if ($filtro === 'devolvido') $where .= " AND e.status = 'devolvido'";
if ($filtro === 'atrasado') $where .= " AND e.status = 'atrasado'";

if ($busca) {
    $buscaEsc = $conn->real_escape_string($busca);
    $where .= " AND (e.responsavel_nome LIKE '%$buscaEsc%' OR e.responsavel_setor LIKE '%$buscaEsc%')";
}

/* --- BUSCAR EMPRÉSTIMOS --- */
$sql = "SELECT e.*, 
        (SELECT GROUP_CONCAT(patrimonio_codigo SEPARATOR ', ') FROM itens_emprestimo WHERE emprestimo_id = e.id) as patrimonios,
        (SELECT COUNT(*) FROM itens_emprestimo WHERE emprestimo_id = e.id) as qtd_itens
        FROM emprestimos e 
        WHERE $where 
        ORDER BY 
            CASE e.status 
                WHEN 'atrasado' THEN 1 
                WHEN 'ativo' THEN 2 
                ELSE 3 
            END, 
            e.data_emprestimo DESC";

$emprestimos = $conn->query($sql);

/* --- CONTADORES PARA TABS --- */
$contAtivos = $conn->query("SELECT COUNT(*) as c FROM emprestimos WHERE status = 'ativo'")->fetch_assoc()['c'];
$contAtrasados = $conn->query("SELECT COUNT(*) as c FROM emprestimos WHERE status = 'atrasado'")->fetch_assoc()['c'];
$contDevolvidos = $conn->query("SELECT COUNT(*) as c FROM emprestimos WHERE status = 'devolvido'")->fetch_assoc()['c'];
$contTotal = $contAtivos + $contAtrasados + $contDevolvidos;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empréstimos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>

<body class="bg-indigo-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0">
        <div>
            <a href="index.php" class="h-16 flex items-center px-6 border-b border-slate-100 hover:bg-gray-50 transition-colors">
                <svg class="w-6 h-6 text-indigo-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-bold text-slate-800 text-lg">Protocolos</span>
            </a>

            <nav class="mt-6 px-4 space-y-1">
                <?php if ($is_admin): ?>
                <a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </a>
                <?php endif; ?>

                <a href="protocolos.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Protocolos
                </a>

                <a href="emprestimos.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg">
                    <svg class="w-5 h-5 mr-3 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Empréstimos
                </a>

                <?php if ($is_admin): ?>
                <a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Usuários
                </a>
                <?php endif; ?>

                <a href="conta.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Conta
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-100">
            <a href="logout.php" class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 hover:text-red-600 transition">
                Sair
            </a>
        </div>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="flex-1 overflow-y-auto p-8">
        
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Empréstimos</h1>
                <p class="text-sm text-slate-500">Gerencie os empréstimos de equipamentos</p>
            </div>
            <a href="novo_emprestimo.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition font-medium flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Novo Empréstimo
            </a>
        </div>

        <!-- Tabs de Filtro -->
        <div class="flex gap-2 mb-6 flex-wrap">
            <a href="?status=todos" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filtro === 'todos' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                Todos (<?= $contTotal ?>)
            </a>
            <a href="?status=ativo" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filtro === 'ativo' ? 'bg-green-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                Ativos (<?= $contAtivos ?>)
            </a>
            <a href="?status=atrasado" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filtro === 'atrasado' ? 'bg-red-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                Atrasados (<?= $contAtrasados ?>)
            </a>
            <a href="?status=devolvido" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filtro === 'devolvido' ? 'bg-slate-600 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                Devolvidos (<?= $contDevolvidos ?>)
            </a>
        </div>

        <!-- Busca -->
        <form method="GET" class="mb-6">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filtro) ?>">
            <div class="flex gap-2">
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" 
                    placeholder="Buscar por nome ou setor..."
                    class="flex-1 max-w-md bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit" class="bg-white border border-slate-200 px-4 py-2 rounded-lg hover:bg-slate-50 transition">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>
            </div>
        </form>

        <!-- Tabela -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="text-xs text-indigo-700 uppercase bg-indigo-50/50">
                        <tr>
                            <th class="px-6 py-3">Responsável</th>
                            <th class="px-6 py-3">Setor</th>
                            <th class="px-6 py-3">Equipamentos</th>
                            <th class="px-6 py-3">Data</th>
                            <th class="px-6 py-3">Prazo</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($emprestimos->num_rows === 0): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                Nenhum empréstimo encontrado.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php while ($row = $emprestimos->fetch_assoc()): ?>
                        <tr class="bg-white border-b hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900"><?= htmlspecialchars($row['responsavel_nome']) ?></div>
                                <?php if ($row['responsavel_telefone']): ?>
                                <div class="text-xs text-slate-400"><?= htmlspecialchars($row['responsavel_telefone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?= htmlspecialchars($row['responsavel_setor'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">
                                    <?= $row['qtd_itens'] ?> item(s)
                                </span>
                                <div class="text-xs text-slate-400 mt-1 truncate max-w-[150px]" title="<?= htmlspecialchars($row['patrimonios'] ?? '') ?>">
                                    <?= htmlspecialchars($row['patrimonios'] ?? '') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <?= date('d/m/Y H:i', strtotime($row['data_emprestimo'])) ?>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <?php 
                                $prazo = $row['data_previsao_devolucao'];
                                $hoje = date('Y-m-d');
                                $atrasado = $prazo < $hoje && $row['status'] !== 'devolvido';
                                ?>
                                <span class="<?= $atrasado ? 'text-red-600 font-bold' : '' ?>">
                                    <?= date('d/m/Y', strtotime($prazo)) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                switch($row['status']) {
                                    case 'ativo':
                                        $statusClass = 'bg-green-100 text-green-700';
                                        $statusLabel = 'Ativo';
                                        break;
                                    case 'atrasado':
                                        $statusClass = 'bg-red-100 text-red-700';
                                        $statusLabel = 'Atrasado';
                                        break;
                                    case 'devolvido':
                                        $statusClass = 'bg-slate-100 text-slate-600';
                                        $statusLabel = 'Devolvido';
                                        break;
                                    default:
                                        $statusClass = 'bg-slate-100 text-slate-600';
                                        $statusLabel = $row['status'];
                                }
                                ?>
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="visualizar_emprestimo.php?id=<?= $row['id'] ?>" 
                                        class="text-indigo-600 hover:text-indigo-800 p-1" title="Ver Detalhes">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    <?php if ($row['status'] !== 'devolvido'): ?>
                                    <button onclick="confirmarDevolucao(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['responsavel_nome'])) ?>')" 
                                        class="text-green-600 hover:text-green-800 p-1" title="Registrar Devolução">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de Devolução -->
    <div id="modalDevolucao" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 50;">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirmar Devolução</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Confirmar devolução do empréstimo de <strong id="nomeResponsavel"></strong>?
                    </p>
                    <input type="hidden" id="idEmprestimo">
                </div>
                <div class="items-center px-4 py-3 space-y-2">
                    <button id="btnConfirmarDevolucao" onclick="processarDevolucao()" 
                        class="px-4 py-2 bg-green-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300">
                        Confirmar Devolução
                    </button>
                    <button onclick="fecharModal()" 
                        class="px-4 py-2 bg-white text-slate-700 text-base font-medium rounded-md w-full border border-slate-300 shadow-sm hover:bg-slate-50">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmarDevolucao(id, nome) {
            document.getElementById('idEmprestimo').value = id;
            document.getElementById('nomeResponsavel').innerText = nome;
            document.getElementById('modalDevolucao').classList.remove('hidden');
        }

        function fecharModal() {
            document.getElementById('modalDevolucao').classList.add('hidden');
        }

        async function processarDevolucao() {
            const id = document.getElementById('idEmprestimo').value;
            const btn = document.getElementById('btnConfirmarDevolucao');
            
            btn.innerText = 'Processando...';
            btn.disabled = true;

            try {
                const form = new FormData();
                form.append('id', id);

                const response = await fetch('devolver_emprestimo.php', {
                    method: 'POST',
                    body: form
                });

                const result = await response.json();

                if (result.success) {
                    alert('Devolução registrada com sucesso!');
                    location.reload();
                } else {
                    alert('Erro: ' + result.message);
                }
            } catch (error) {
                console.error(error);
                alert('Erro ao processar devolução.');
            } finally {
                btn.innerText = 'Confirmar Devolução';
                btn.disabled = false;
                fecharModal();
            }
        }
    </script>

</body>
</html>
