<?php
session_start();

// 1. SEGURANÇA: Verifica se está logado. Se não, chuta para o login.
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php");
    exit;
}

// CONFIGURAÇÃO DO BANCO
$host = 'localhost';
$db   = 'banco'; // Coloque o nome do seu banco
$user = 'root';       // Coloque seu usuário
$pass = '';         // Coloque sua senha

$conn = new mysqli($host, $user, $pass, $db);
$id_usuario = $_SESSION['admin_id'];
$mensagem = '';

// 2. LÓGICA DE TROCA DE SENHA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'])) {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha  = $_POST['nova_senha'];

    // Busca a senha atual (hash) no banco
    $stmt = $conn->prepare("SELECT senha_hash FROM usuarios_admin WHERE id = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    $usuario_dados = $res->fetch_assoc();

    if (password_verify($senha_atual, $usuario_dados['senha_hash'])) {
        // Senha correta, vamos atualizar
        $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE usuarios_admin SET senha_hash = ? WHERE id = ?");
        $update->bind_param("si", $novo_hash, $id_usuario);
        
        if ($update->execute()) {
            $mensagem = "<span class='text-green-600 bg-green-100 p-2 rounded block mt-2'>Senha alterada com sucesso!</span>";
        } else {
            $mensagem = "<span class='text-red-600 bg-red-100 p-2 rounded block mt-2'>Erro ao atualizar.</span>";
        }
    } else {
        $mensagem = "<span class='text-red-600 bg-red-100 p-2 rounded block mt-2'>A senha atual está incorreta.</span>";
    }
}

// 3. BUSCAR DADOS DO USUÁRIO PARA EXIBIR NA TELA
$stmt = $conn->prepare("SELECT usuario, email, funcao FROM usuarios_admin WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$dados = $result->fetch_assoc();

// Define a cor da "badge" baseada na função
$badge_color = "bg-indigo-600"; // Padrão
if($dados['funcao'] == 'Administrador') $badge_color = "bg-indigo-700";
if($dados['funcao'] == 'Funcionário') $badge_color = "bg-blue-500";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - Protocolos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-indigo-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between">
        <div>
            <div class="h-16 flex items-center px-6 border-b border-slate-100">
                <svg class="w-6 h-6 text-indigo-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span class="font-bold text-slate-800 text-lg">Protocolos</span>
            </div>

            <nav class="mt-6 px-4 space-y-1">
                <a href="protocolos.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Protocolos
                </a>
                <a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Usuários
                </a>
                <a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg">
                    <svg class="w-5 h-5 mr-3 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Conta
                </a>
            </nav>
        </div>

        <div class="p-4 border-t border-slate-100">
            <a href="logout.php" class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 hover:text-red-600 transition-colors">
                <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs mr-3 font-bold">
                    <?php echo strtoupper(substr($dados['usuario'], 0, 1)); ?>
                </div>
                Sair
            </a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-2xl font-bold text-slate-800 mb-8">Conta</h1>

        <div class="max-w-3xl space-y-6">

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-800 mb-1">Informações do Perfil</h2>
                <p class="text-sm text-slate-500 mb-6">Seus dados de usuário cadastrados no sistema.</p>

                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span class="text-slate-700 font-medium"><?php echo htmlspecialchars($dados['usuario']); ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v9a2 2 0 002 2z"></path></svg>
                        <span class="text-slate-700"><?php echo htmlspecialchars($dados['email'] ?: 'Sem email cadastrado'); ?></span>
                    </div>
                    <div class="flex items-center gap-3 mt-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="<?php echo $badge_color; ?> text-white text-xs font-bold px-3 py-1 rounded-full">
                            <?php echo htmlspecialchars($dados['funcao']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-800 mb-1">Gerenciamento de Senha</h2>
                <p class="text-sm text-slate-500 mb-6">Altere sua senha de acesso.</p>

                <form method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Senha Atual</label>
                            <div class="relative">
                                <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                <input type="password" name="senha_atual" placeholder="********" class="w-full bg-indigo-50/50 border border-indigo-100 text-slate-900 text-sm rounded-md focus:ring-indigo-500 focus:border-indigo-500 block pl-10 p-2.5 shadow-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nova Senha</label>
                            <div class="relative">
                                <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                <input type="password" name="nova_senha" placeholder="********" class="w-full bg-indigo-50/50 border border-indigo-100 text-slate-900 text-sm rounded-md focus:ring-indigo-500 focus:border-indigo-500 block pl-10 p-2.5 shadow-sm">
                            </div>
                        </div>

                        <button type="submit" class="text-white bg-indigo-400 hover:bg-indigo-500 font-medium rounded-md text-sm px-4 py-2 focus:outline-none transition-colors">
                            Alterar Senha
                        </button>

                        <?php echo $mensagem; ?>
                        
                        <p class="text-xs text-slate-400 mt-4 pt-4 border-t border-slate-100">
                            Funcionalidade de alteração de senha em construção (Simulado via PHP).
                        </p>
                    </div>
                </form>
            </div>

        </div>
    </main>

</body>
</html>