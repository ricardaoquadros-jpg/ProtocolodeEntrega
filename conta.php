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

/* --- BLOQUEIA NÃO-LOGADOS --- */
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php");
    exit;
}

/* --- CONEXÃO CENTRALIZADA --- */
if (!file_exists(__DIR__ . '/conexao.php')) {
    die("Erro: conexao.php não encontrado.");
}
require __DIR__ . '/conexao.php';

$id_usuario = intval($_SESSION['admin_id']);
$mensagem = "";

/* ===========================================================
    1. ALTERAÇÃO DE SENHA
=========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {

    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha  = $_POST['nova_senha'] ?? '';

    // Busca o hash atual
    $stmt = $conn->prepare("SELECT senha_hash FROM usuarios_admin WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    $dadosSenha = $res->fetch_assoc();

    if (!$dadosSenha) {
        $mensagem = "<span class='text-red-600 bg-red-100 p-2 rounded block mt-2'>Erro interno.</span>";
    } elseif (!password_verify($senha_atual, $dadosSenha['senha_hash'])) {

        $mensagem = "<span class='text-red-600 bg-red-100 p-2 rounded block mt-2'>A senha atual está incorreta.</span>";

    } else {

        $novoHash = password_hash($nova_senha, PASSWORD_DEFAULT);

        $up = $conn->prepare("UPDATE usuarios_admin SET senha_hash = ? WHERE id = ?");
        $up->bind_param("si", $novoHash, $id_usuario);

        if ($up->execute()) {
            $mensagem = "<span class='text-green-600 bg-green-100 p-2 rounded block mt-2'>Senha alterada com sucesso!</span>";
        } else {
            $mensagem = "<span class='text-red-600 bg-red-100 p-2 rounded block mt-2'>Erro ao atualizar.</span>";
        }
    }
}

/* ===========================================================
    2. BUSCAR INFORMAÇÕES DO USUÁRIO
=========================================================== */
$stmt = $conn->prepare("SELECT usuario, email, funcao FROM usuarios_admin WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resUser = $stmt->get_result();
$dados = $resUser->fetch_assoc();

if (!$dados) {
    die("Erro ao buscar dados do usuário.");
}

/* Badge de cargo */
$badgeCor = "bg-indigo-600";
if ($dados['funcao'] === "Administrador") $badgeCor = "bg-indigo-700";
if ($dados['funcao'] === "Funcionário")   $badgeCor = "bg-blue-600";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minha Conta - Protocolo TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>

<body class="bg-indigo-50 flex h-screen overflow-hidden">

<!-- ===========================================================
    MENU LATERAL PADRÃO
=========================================================== -->
<aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between">
    <div>
        <a href="protocolos.php" class="h-16 flex items-center px-6 border-b border-slate-100">
            <svg class="w-6 h-6 text-indigo-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span class="font-bold text-slate-800 text-lg">Protocolos</span>
        </a>

        <nav class="mt-6 px-4 space-y-1">
            <?php if ($dados['funcao'] === 'Administrador'): ?>
            <a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <?php endif; ?>

            <a href="protocolos.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Protocolos
            </a>

            <?php if ($dados['funcao'] === 'Administrador'): ?>
            <a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Usuários
            </a>
            <?php endif; ?>

            <a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg">
                <svg class="w-5 h-5 mr-3 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Conta
            </a>

        </nav>
    </div>

    <div class="p-4 border-t border-slate-100">
        <a href="logout.php" class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 hover:text-red-600">
            <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs mr-3 font-bold">
                <?= strtoupper(substr($dados['usuario'], 0, 1)) ?>
            </div>
            Sair
        </a>
    </div>
</aside>

<!-- ===========================================================
    CONTEÚDO PRINCIPAL
=========================================================== -->
<main class="flex-1 overflow-y-auto p-8">

    <h1 class="text-2xl font-bold text-slate-800 mb-8">Minha Conta</h1>

    <div class="max-w-3xl space-y-6">

        <!-- ===========================================================
            INFORMAÇÕES DO PERFIL
        ============================================================ -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Informações do Perfil</h2>
            <p class="text-sm text-slate-500 mb-6">Informações cadastradas para sua conta.</p>

            <div class="space-y-3 text-sm">

                <p><strong class="text-slate-700">Usuário:</strong>
                    <?= htmlspecialchars($dados['usuario']) ?>
                </p>

                <p><strong class="text-slate-700">Email:</strong>
                    <?= htmlspecialchars($dados['email'] ?: 'Não informado') ?>
                </p>

                <p><strong class="text-slate-700">Cargo:</strong>
                    <span class="<?= $badgeCor ?> text-white text-xs font-bold px-3 py-1 rounded-full">
                    <?= htmlspecialchars($dados['funcao']) ?>
                    </span>
                </p>

            </div>
        </div>

        <!-- ===========================================================
            ALTERAR SENHA
        ============================================================ -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Alterar Senha</h2>
            <p class="text-sm text-slate-500 mb-6">Atualize sua senha de acesso ao sistema.</p>

            <form method="POST" action="">
                <div class="space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Senha Atual</label>
                        <input type="password" name="senha_atual"
                               class="w-full bg-indigo-50 border border-indigo-200 text-sm rounded-md p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nova Senha</label>
                        <input type="password" name="nova_senha"
                               class="w-full bg-indigo-50 border border-indigo-200 text-sm rounded-md p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">
                        Alterar Senha
                    </button>

                    <?= $mensagem ?>
                </div>
            </form>
        </div>

    </div>
</main>

</body>
</html>
