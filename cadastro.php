<?php
// CONFIGURAÇÃO DO BANCO
$host = 'localhost';
$db   = 'banco';
$user = 'root';
$pass = '';

$mensagem = '';
$tipo_msg = ''; // 'erro' ou 'sucesso'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }

    $usuario = $conn->real_escape_string($_POST['usuario']);
    $senha   = $_POST['senha'];

    // 1. Verifica se usuário já existe
    $check = $conn->query("SELECT id FROM usuarios_admin WHERE usuario = '$usuario'");

    if ($check->num_rows > 0) {
        $mensagem = "Este nome de usuário já está em uso.";
        $tipo_msg = 'erro';
    } else {
        // 2. Cria o Hash da senha e define cargo padrão
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        $cargo_padrao = 'Usuário'; // Segurança: Sempre entra com nível baixo

        $stmt = $conn->prepare("INSERT INTO usuarios_admin (usuario, senha_hash, funcao) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $usuario, $senha_hash, $cargo_padrao);

        if ($stmt->execute()) {
            $mensagem = "Conta criada com sucesso! Redirecionando...";
            $tipo_msg = 'sucesso';
            // Redireciona para o login após 2 segundos
            header("refresh:2;url=login.php");
        } else {
            $mensagem = "Erro ao criar conta: " . $conn->error;
            $tipo_msg = 'erro';
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Nova Conta</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #e0e7ff; } /* Fundo Lilás Claro */
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md border border-indigo-50 relative">
        
        <div class="flex justify-center mb-4">
            <div class="bg-indigo-50 p-4 rounded-full">
                <svg class="w-8 h-8 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-center text-slate-800 mb-1">Criar Nova Conta</h1>
        <p class="text-center text-slate-500 text-sm mb-8">Preencha os campos para criar seu acesso.</p>

        <?php if($mensagem): ?>
            <div class="<?php echo ($tipo_msg == 'sucesso') ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400'; ?> border px-4 py-3 rounded relative mb-4 text-sm text-center">
                <?php echo $mensagem; ?>
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
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    Criar Conta
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="login.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Já tem uma conta? Faça login</a>
        </div>

    </div>

</body>
</html>