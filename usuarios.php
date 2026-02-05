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

/* --- CONEXÃO CENTRAL --- */
if (!file_exists(__DIR__ . '/conexao.php')) {
    die("Erro: conexao.php não encontrado.");
}
require __DIR__ . '/conexao.php';

/* ----------------------------------------------------
   1. SEGURANÇA DE AUTENTICAÇÃO
---------------------------------------------------- */
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php");
    exit;
}

$id_usuario_logado = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT funcao FROM usuarios_admin WHERE id = ?");
$stmt->bind_param("i", $id_usuario_logado);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user || trim($user['funcao']) !== 'Administrador') {
    echo "<script>alert('Acesso Negado: Apenas Administradores.'); window.location.href='protocolos.php';</script>";
    exit;
}

/* ----------------------------------------------------
   2. REQUISIÇÃO AJAX — ALTERAR CARGO
---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar_cargo') {

    header('Content-Type: application/json');

    $id_alvo   = (int) ($_POST['id'] ?? 0);
    $novo_cargo = limparTexto($_POST['cargo'] ?? '');

    $cargos_validos = ['Administrador', 'Funcionário', 'Usuário'];

    if (!in_array($novo_cargo, $cargos_validos)) {
        echo json_encode(['sucesso' => false, 'msg' => 'Cargo inválido.']);
        exit;
    }

    if ($id_alvo === $id_usuario_logado) {
        echo json_encode(['sucesso' => false, 'msg' => 'Você não pode alterar seu próprio cargo.']);
        exit;
    }

    $upd = $conn->prepare("UPDATE usuarios_admin SET funcao = ? WHERE id = ?");
    $upd->bind_param("si", $novo_cargo, $id_alvo);

    if ($upd->execute()) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['sucesso' => false, 'msg' => "Erro no banco: " . $conn->error]);
    }
    exit;
}

/* ----------------------------------------------------
   2.1. REQUISIÇÃO AJAX — EXCLUIR USUÁRIO
---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir_usuario') {
    header('Content-Type: application/json');

    $id_alvo = (int) ($_POST['id'] ?? 0);

    if ($id_alvo === $id_usuario_logado) {
        echo json_encode(['sucesso' => false, 'msg' => 'Você não pode excluir sua própria conta.']);
        exit;
    }

    $del = $conn->prepare("DELETE FROM usuarios_admin WHERE id = ?");
    $del->bind_param("i", $id_alvo);

    if ($del->execute()) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['sucesso' => false, 'msg' => "Erro ao excluir: " . $conn->error]);
    }
    exit;
}

/* ----------------------------------------------------
   2.2. REQUISIÇÃO AJAX — ALTERAR SENHA
---------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'alterar_senha') {
    header('Content-Type: application/json');

    $id_alvo = (int) ($_POST['id'] ?? 0);
    $nova_senha = $_POST['nova_senha'] ?? '';

    if (empty($nova_senha)) {
        echo json_encode(['sucesso' => false, 'msg' => 'A nova senha não pode ser vazia.']);
        exit;
    }

    $hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    $upd = $conn->prepare("UPDATE usuarios_admin SET senha_hash = ? WHERE id = ?");
    $upd->bind_param("si", $hash, $id_alvo);

    if ($upd->execute()) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['sucesso' => false, 'msg' => "Erro ao alterar senha: " . $conn->error]);
    }
    exit;
}

/* ----------------------------------------------------
   3. LISTA DE USUÁRIOS
---------------------------------------------------- */
$lista = $conn->query("SELECT id, usuario, email, funcao FROM usuarios_admin ORDER BY usuario ASC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários</title>
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
                <?php if ($user['funcao'] === 'Administrador'): ?>
                <a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </a>
                <?php endif; ?>

                <a href="protocolos.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Protocolos
                </a>

                <a href="emprestimos.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Empréstimos
                </a>

                <?php if ($user['funcao'] === 'Administrador'): ?>
                <a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg">
                    <svg class="w-5 h-5 mr-3 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
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

    <!-- Conteúdo -->
    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-2xl font-bold text-slate-800 mb-8">Gerenciamento de Usuários</h1>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200">
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-800">Usuários do Sistema</h2>
                <p class="text-sm text-slate-500">Gerencie cargos e permissões.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="text-xs text-indigo-700 uppercase bg-indigo-50/50">
                        <tr>
                            <th class="px-6 py-3">Usuário</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Cargo</th>
                            <th class="px-6 py-3 text-right">Ação</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $lista->fetch_assoc()): ?>
                        <tr class="bg-white border-b hover:bg-slate-50 transition">
                            
                            <td class="px-6 py-4 font-medium text-slate-900">
                                <?= htmlspecialchars($row['usuario']); ?>
                            </td>

                            <td class="px-6 py-4">
                                <?= htmlspecialchars($row['email'] ?? ''); ?>
                            </td>

                            <td class="px-6 py-4">
                                <span id="badge-<?= $row['id']; ?>"
                                    class="<?= $row['funcao'] === 'Administrador'
                                            ? 'bg-indigo-700 text-white'
                                            : 'border border-slate-300 text-slate-700' ?>
                                        text-xs font-bold px-3 py-1 rounded-full">
                                    <?= htmlspecialchars($row['funcao']); ?>
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                                <select onchange="alterarCargo(<?= $row['id']; ?>, this.value)"
                                        class="bg-indigo-50 border border-indigo-100 text-slate-700 text-xs rounded-md p-2 shadow-sm mr-2">
                                    <option <?= $row['funcao']=='Administrador' ? 'selected' : '' ?>>Administrador</option>
                                    <option <?= $row['funcao']=='Funcionário' ? 'selected' : '' ?>>Funcionário</option>
                                    <option <?= $row['funcao']=='Usuário' ? 'selected' : '' ?>>Usuário</option>
                                </select>

                                <button onclick="abrirModalSenha(<?= $row['id']; ?>, '<?= htmlspecialchars($row['usuario']); ?>')" 
                                        class="text-indigo-600 hover:text-indigo-900 p-1" title="Alterar Senha">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 15-5 5 5 5v3l3 3h3a3 3 0 013 3v2h2v-2h2v-2h2a2 2 0 012-2m-6 0a2 2 0 10-4 0 2 2 0 004 0z"></path></svg>
                                </button>

                                <button onclick="confirmarExclusao(<?= $row['id']; ?>)" 
                                        class="text-red-600 hover:text-red-900 p-1" title="Excluir Usuário">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </td>

                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Alterar Senha -->
    <div id="modalSenha" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 50;">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Alterar Senha</h3>
                <div class="mt-2 text-left">
                    <p class="text-sm text-gray-500 mb-4">Nova senha para <strong id="nomeUsuarioModal"></strong>:</p>
                    <input type="hidden" id="idUsuarioModal">
                    <input type="password" id="novaSenhaInput" class="w-full bg-slate-50 border border-slate-300 rounded p-2 text-sm" placeholder="Nova Senha">
                </div>
                <div class="items-center px-4 py-3 mt-4 space-y-2">
                    <button id="btnSalvarSenha" onclick="salvarNovaSenha()" class="px-4 py-2 bg-indigo-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        Salvar
                    </button>
                    <button onclick="fecharModalSemha()" class="px-4 py-2 bg-white text-slate-700 text-base font-medium rounded-md w-full border border-slate-300 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function alterarCargo(idUsuario, novoCargo) {

            if (!confirm(`Alterar cargo do usuário #${idUsuario} para ${novoCargo}?`)) {
                location.reload();
                return;
            }

            const form = new FormData();
            form.append('acao', 'atualizar_cargo');
            form.append('id', idUsuario);
            form.append('cargo', novoCargo);

            try {
                const response = await fetch('usuarios.php', { method: 'POST', body: form });
                const data = await response.json();

                if (data.sucesso) {
                    const badge = document.getElementById(`badge-${idUsuario}`);
                    if (novoCargo === 'Administrador') {
                        badge.className = "bg-indigo-700 text-white text-xs font-bold px-3 py-1 rounded-full";
                    } else {
                        badge.className = "border border-slate-300 text-slate-700 text-xs font-bold px-3 py-1 rounded-full";
                    }
                    badge.innerText = novoCargo;
                } else {
                    alert("Erro: " + data.msg);
                    location.reload();
                }

            } catch (e) {
                console.error(e);
                alert("Erro ao processar a solicitação.");
                location.reload();
            }
        }

        async function confirmarExclusao(idUsuario) {
            if (!confirm(`Tem certeza que deseja EXCLUIR o usuário #${idUsuario}? Esta ação não pode ser desfeita.`)) {
                return;
            }

            const form = new FormData();
            form.append('acao', 'excluir_usuario');
            form.append('id', idUsuario);

            try {
                const response = await fetch('usuarios.php', { method: 'POST', body: form });
                const data = await response.json();

                if (data.sucesso) {
                    alert('Usuário excluído com sucesso.');
                    location.reload();
                } else {
                    alert("Erro: " + data.msg);
                }
            } catch (e) {
                console.error(e);
                alert("Erro ao processar a solicitação.");
            }
        }

        function abrirModalSenha(id, nome) {
            document.getElementById('idUsuarioModal').value = id;
            document.getElementById('nomeUsuarioModal').innerText = nome;
            document.getElementById('novaSenhaInput').value = '';
            document.getElementById('modalSenha').classList.remove('hidden');
        }

        function fecharModalSemha() {
            document.getElementById('modalSenha').classList.add('hidden');
        }

        async function salvarNovaSenha() {
            const id = document.getElementById('idUsuarioModal').value;
            const senha = document.getElementById('novaSenhaInput').value;

            if (!senha) {
                alert('A senha não pode ser vazia.');
                return;
            }

            const form = new FormData();
            form.append('acao', 'alterar_senha');
            form.append('id', id);
            form.append('nova_senha', senha);

            const btn = document.getElementById('btnSalvarSenha');
            const originalText = btn.innerText;
            btn.innerText = 'Salvando...';
            btn.disabled = true;

            try {
                const response = await fetch('usuarios.php', { method: 'POST', body: form });
                const data = await response.json();

                if (data.sucesso) {
                    alert('Senha alterada com sucesso!');
                    fecharModalSemha();
                } else {
                    alert("Erro: " + data.msg);
                }
            } catch (e) {
                console.error(e);
                alert("Erro ao processar a solicitação.");
            } finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        }
    </script>

</body>
</html>
