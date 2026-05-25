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
    1.1 ALTERAÇÃO DE DADOS PESSOAIS
=========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_perfil'])) {
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $telefone      = trim($_POST['telefone'] ?? '');

    if (empty($nome_completo) || empty($email) || empty($telefone)) {
        $mensagem = "<span class='text-red-600 bg-red-100 p-2 rounded block mt-2'>Todos os campos são obrigatórios.</span>";
    } else {
        $upUser = $conn->prepare("UPDATE usuarios_admin SET nome_completo = ?, email = ?, telefone = ? WHERE id = ?");
        $upUser->bind_param("sssi", $nome_completo, $email, $telefone, $id_usuario);

        if ($upUser->execute()) {
            $mensagem = "<span class='text-green-600 bg-green-100 p-2 rounded block mt-2'>Dados atualizados com sucesso!</span>";
        } else {
            $mensagem = "<span class='text-red-600 bg-red-100 p-2 rounded block mt-2'>Erro ao atualizar dados.</span>";
        }
    }
}

/* ===========================================================
    2. BUSCAR INFORMAÇÕES DO USUÁRIO
=========================================================== */
$stmt = $conn->prepare("SELECT usuario, email, funcao, nome_completo, telefone FROM usuarios_admin WHERE id = ?");
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
<?php 
$pagina_ativa = 'conta'; 
$is_admin = ($dados['funcao'] === 'Administrador');
$dados_usuario = $dados;
include __DIR__ . '/utils/sidebar.php'; 
?>

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

                <p><strong class="text-slate-700">Cargo:</strong>
                    <span class="<?= $badgeCor ?> text-white text-xs font-bold px-3 py-1 rounded-full">
                    <?= htmlspecialchars($dados['funcao']) ?>
                    </span>
                </p>

            </div>
        </div>

        <!-- ===========================================================
            DADOS PESSOAIS
        ============================================================ -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Dados Pessoais</h2>
            <p class="text-sm text-slate-500 mb-6">Mantenha seus dados atualizados.</p>

            <form method="POST" action="">
                <input type="hidden" name="salvar_perfil" value="1">
                <div class="space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nome Completo <span class="text-red-500">*</span></label>
                        <input type="text" name="nome_completo" required
                               value="<?= htmlspecialchars($dados['nome_completo'] ?? '') ?>"
                               class="w-full bg-indigo-50 border border-indigo-200 text-sm rounded-md p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email para Contato <span class="text-red-500">*</span></label>
                        <input type="email" name="email" required
                               value="<?= htmlspecialchars($dados['email'] ?? '') ?>"
                               class="w-full bg-indigo-50 border border-indigo-200 text-sm rounded-md p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Telefone <span class="text-red-500">*</span></label>
                        <input type="text" name="telefone" required
                               value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>"
                               class="w-full bg-indigo-50 border border-indigo-200 text-sm rounded-md p-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">
                        Salvar Dados
                    </button>
                </div>
            </form>
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
