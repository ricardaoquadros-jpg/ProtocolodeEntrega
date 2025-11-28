<?php
session_start();
define('APP_RUNNING', true);

/* --- SEGURANÇA E LOGS --- */
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
    echo "<script>alert('Acesso Negado: Apenas Administradores.'); window.location.href='dashboard.php';</script>";
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
            <a href="index.html" class="h-16 flex items-center px-6 border-b border-slate-100 hover:bg-gray-50 transition-colors">
                <svg class="w-6 h-6 text-indigo-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-bold text-slate-800 text-lg">Protocolos</span>
            </a>

            <nav class="mt-6 px-4 space-y-1">
                <a href="protocolos.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
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
                                <?= htmlspecialchars($row['email']); ?>
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

                            <td class="px-6 py-4 text-right">
                                <select onchange="alterarCargo(<?= $row['id']; ?>, this.value)"
                                        class="bg-indigo-50 border border-indigo-100 text-slate-700 text-xs rounded-md p-2 shadow-sm">
                                    <option <?= $row['funcao']=='Administrador' ? 'selected' : '' ?>>Administrador</option>
                                    <option <?= $row['funcao']=='Funcionário' ? 'selected' : '' ?>>Funcionário</option>
                                    <option <?= $row['funcao']=='Usuário' ? 'selected' : '' ?>>Usuário</option>
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
    </script>

</body>
</html>
