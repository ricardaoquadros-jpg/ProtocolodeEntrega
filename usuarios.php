<?php
session_start();

// CONFIGURAÇÃO DO BANCO
$host = 'localhost';
$db   = 'banco';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

// 1. SEGURANÇA BÁSICA: Verifica se está logado
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php");
    exit;
}

// 2. SEGURANÇA AVANÇADA: Verifica se é ADMINISTRADOR
$id_usuario_logado = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT funcao FROM usuarios_admin WHERE id = ?");
$stmt->bind_param("i", $id_usuario_logado);
$stmt->execute();
$res_auth = $stmt->get_result();
$usuario_auth = $res_auth->fetch_assoc();

// Se NÃO for Administrador, redireciona de volta pro Dashboard
// Utiliza trim() para garantir que a comparação ignore espaços em branco
if (trim($usuario_auth['funcao']) !== 'Administrador') {
    echo "<script>alert('Acesso Negado: Apenas Administradores.'); window.location.href='dashboard.php';</script>";
    exit;
}

// 3. LÓGICA DE ATUALIZAÇÃO (AJAX) - CORREÇÃO DE JSON APLICADA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'atualizar_cargo') {
    
    // LINHA CRUCIAL ADICIONADA: Diz ao navegador que a resposta é JSON
    header('Content-Type: application/json'); 
    
    $id_alvo = $_POST['id'];
    $novo_cargo = $_POST['cargo'];
    
    // Validação básica para evitar valores inválidos no banco
    if (!in_array($novo_cargo, ['Administrador', 'Funcionário', 'Usuário'])) {
        echo json_encode(['sucesso' => false, 'msg' => 'Cargo inválido.']);
        exit;
    }

    // Proteção: Não permitir que o admin mude o próprio cargo para travar a si mesmo
    if($id_alvo == $id_usuario_logado) {
        echo json_encode(['sucesso' => false, 'msg' => 'Você não pode alterar seu próprio cargo.']);
        exit;
    }

    $update = $conn->prepare("UPDATE usuarios_admin SET funcao = ? WHERE id = ?");
    $update->bind_param("si", $novo_cargo, $id_alvo);
    
    if($update->execute()) {
        echo json_encode(['sucesso' => true]);
    } else {
        // Incluir o erro do MySQL para debug
        echo json_encode(['sucesso' => false, 'msg' => 'Falha no banco de dados: ' . $conn->error]); 
    }
    
    exit; // Para o script aqui se for requisição AJAX
}

// 4. BUSCAR LISTA DE USUÁRIOS
$lista = $conn->query("SELECT * FROM usuarios_admin ORDER BY usuario ASC");
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

   <aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0">
    <div>
        <a href="index.html" class="h-16 flex items-center px-6 border-b border-slate-100 transition-colors hover:bg-slate-50">
            <svg class="w-6 h-6 text-indigo-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span class="font-bold text-slate-800 text-lg">Protocolos</span>
        </a>
        <nav class="mt-6 px-4 space-y-1">
                <a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Protocolos
                </a>
                <a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg">
                    <svg class="w-5 h-5 mr-3 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Usuários
                </a>
                <a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Conta
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-slate-100">
            <a href="logout.php" class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 hover:text-red-600 transition-colors">
                Sair
            </a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Gerenciamento de Usuários</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200">
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-800">Usuários do Sistema</h2>
                <p class="text-sm text-slate-500">Visualize e gerencie os cargos dos usuários cadastrados.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="text-xs text-indigo-700 uppercase bg-indigo-50/50">
                        <tr>
                            <th scope="col" class="px-6 py-3">Usuário</th>
                            <th scope="col" class="px-6 py-3">Email</th>
                            <th scope="col" class="px-6 py-3">Cargo Atual</th>
                            <th scope="col" class="px-6 py-3 text-right">Alterar Cargo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $lista->fetch_assoc()): ?>
                            <tr class="bg-white border-b hover:bg-slate-50 transition-colors">
                                <th scope="row" class="px-6 py-4 font-medium text-slate-900 whitespace-nowrap">
                                    <?php echo htmlspecialchars($row['usuario']); ?>
                                </th>
                                <td class="px-6 py-4">
                                    <?php echo htmlspecialchars($row['email']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if($row['funcao'] == 'Administrador'): ?>
                                        <span id="badge-<?php echo $row['id']; ?>" class="bg-indigo-700 text-white text-xs font-bold px-3 py-1 rounded-full">
                                            Administrador
                                        </span>
                                    <?php else: ?>
                                        <span id="badge-<?php echo $row['id']; ?>" class="border border-slate-300 text-slate-600 text-xs font-bold px-3 py-1 rounded-full">
                                            <?php echo htmlspecialchars($row['funcao']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <select onchange="alterarCargo(<?php echo $row['id']; ?>, this.value)" 
                                            class="bg-indigo-50 border border-indigo-100 text-slate-700 text-xs rounded-md focus:ring-indigo-500 focus:border-indigo-500 p-2 shadow-sm outline-none">
                                        <option value="Administrador" <?php echo ($row['funcao'] == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="Funcionário" <?php echo ($row['funcao'] == 'Funcionário') ? 'selected' : ''; ?>>Funcionário</option>
                                        <option value="Usuário" <?php echo ($row['funcao'] == 'Usuário') ? 'selected' : ''; ?>>Usuário</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        async function alterarCargo(idUsuario, novoCargo) {
            // Confirmação para evitar mudanças acidentais
            if (!confirm(`Tem certeza que deseja alterar o cargo do usuário ID ${idUsuario} para ${novoCargo}?`)) {
                // Se cancelar, restaura o valor original do select
                location.reload(); 
                return;
            }

            // Dados a enviar
            const formData = new FormData();
            formData.append('acao', 'atualizar_cargo');
            formData.append('id', idUsuario);
            formData.append('cargo', novoCargo);

            try {
                // Envia para o mesmo arquivo (PHP no topo trata isso)
                const response = await fetch('usuarios.php', {
                    method: 'POST',
                    body: formData
                });

                // Tenta ler o JSON
                const result = await response.json();

                if (result.sucesso) {
                    // Atualiza a visualização do Badge sem recarregar a tela
                    const badge = document.getElementById('badge-' + idUsuario);
                    
                    if (novoCargo === 'Administrador') {
                        badge.className = "bg-indigo-700 text-white text-xs font-bold px-3 py-1 rounded-full";
                    } else {
                        badge.className = "border border-slate-300 text-slate-600 text-xs font-bold px-3 py-1 rounded-full";
                    }
                    badge.innerText = novoCargo;

                    // Feedback sutil
                    console.log('Cargo atualizado com sucesso!');
                } else {
                    alert('Erro: ' + (result.msg || 'Não foi possível atualizar.'));
                    location.reload(); // Recarrega para voltar ao estado anterior e mostrar o erro
                }

            } catch (error) {
                console.error('Erro de conexão ou JSON inválido:', error);
                alert('Erro de conexão ou resposta inválida do servidor. Verifique o console.');
                location.reload(); 
            }
        }
    </script>
</body>
</html>