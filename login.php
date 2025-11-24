<?php
session_start();

// CONFIGURAÇÃO DO BANCO (Igual ao arquivo salvar.php)
$host = 'localhost';
$db   = 'banco';
$user = 'root';
$pass = '';

$erro = '';

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }

    $usuario = $conn->real_escape_string($_POST['usuario']);
    $senha_digitada = $_POST['senha'];

    // Busca o usuário no banco
    $sql = "SELECT id, senha_hash FROM usuarios_admin WHERE usuario = '$usuario'";
    $result = $conn->query($sql);

    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verifica se a senha bate com o hash
        if (password_verify($senha_digitada, $row['senha_hash'])) {
            // Login Sucesso: Cria a sessão
            $_SESSION['admin_logado'] = true;
            $_SESSION['admin_id'] = $row['id'];
            
            // Redireciona para o Painel (Vamos criar isso depois)
            header("Location: dashboard.php"); 
            exit;
        } else {
            $erro = "Senha incorreta.";
        }
    } else {
        $erro = "Usuário não encontrado.";
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #e0e7ff; } /* Fundo lilás claro */
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md border border-indigo-50">
        
        <div class="flex justify-center mb-4">
            <div class="bg-indigo-50 p-3 rounded-full">
                <svg class="w-6 h-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-center text-slate-800 mb-1">Acesso à Área Restrita</h1>
        <p class="text-center text-slate-500 text-sm mb-8">Faça login para acessar o painel.</p>

        <?php if($erro): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 text-sm text-center">
                <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Usuário</label>
                    <input type="text" name="usuario" required placeholder="Ex: admin" 
                        class="w-full bg-indigo-50 border border-indigo-200 text-slate-900 text-sm rounded-md focus:ring-indigo-500 focus:border-indigo-500 block p-3 shadow-sm outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
                    <input type="password" name="senha" required placeholder="********" 
                        class="w-full bg-indigo-50 border border-indigo-200 text-slate-900 text-sm rounded-md focus:ring-indigo-500 focus:border-indigo-500 block p-3 shadow-sm outline-none transition-all">
                </div>

                <button type="submit" class="w-full text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:outline-none focus:ring-indigo-300 font-medium rounded-md text-sm px-5 py-3 text-center flex items-center justify-center gap-2 mt-2 shadow-md transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    Entrar
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="cadastro.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
    Não tem uma conta? Crie uma
</a>

    </div>

    </body>
</html>