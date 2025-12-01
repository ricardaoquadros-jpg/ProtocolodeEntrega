<?php
session_start();

// 1. Segurança
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php");
    exit;
}

// 2. Ativa permissões para carregar conexao.php
define('APP_RUNNING', true);

// 3. Carrega a conexão (SEM REPETIR)
require __DIR__ . "/conexao.php";

// 4. Termo de busca
$where_clause = "";
$term = "";

if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $term = $conn->real_escape_string($_GET['busca']);
    $where_clause = "
        WHERE p.nome_recebedor LIKE '%$term%' 
        OR p.cpf_matricula LIKE '%$term%'
        OR pi.patrimonio_codigo LIKE '%$term%' 
        OR p.email LIKE '%$term%'
    ";
}

// 5. Query principal
$sql = "
    SELECT 
        p.id, p.nome_recebedor, p.cpf_matricula, p.telefone, p.email,
        p.assinatura_base64, p.data_criacao,
        pi.patrimonio_codigo, pi.tipo_equipamento
    FROM protocolos p
    LEFT JOIN protocolo_itens pi ON p.id = pi.protocolo_id
    $where_clause
    ORDER BY p.data_criacao DESC
";

$result = $conn->query($sql);

// 6. Agrupamento
$protocolos = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];

    if (!isset($protocolos[$id])) {
        $protocolos[$id] = [
            'info' => [
                'nome' => $row['nome_recebedor'],
                'cpf' => $row['cpf_matricula'],
                'telefone' => $row['telefone'],
                'email' => $row['email'],
                'data' => $row['data_criacao'],
                'assinatura' => $row['assinatura_base64']
            ],
            'itens' => []
        ];
    }

    if (!empty($row['patrimonio_codigo'])) {
        $protocolos[$id]['itens'][] = [
            'codigo' => $row['patrimonio_codigo'],
            'tipo' => $row['tipo_equipamento']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protocolos - Sistema</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-indigo-50 flex h-screen overflow-hidden">

    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0">
        <div>
            <a href="index.html" class="h-16 flex items-center px-6 border-b border-slate-100 hover:bg-gray-50 transition-colors">
                <svg class="w-6 h-6 text-indigo-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span class="font-bold text-slate-800 text-lg">Protocolos</span>
            </a>
            <nav class="mt-6 px-4 space-y-1">
                <a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg">
                    <svg class="w-5 h-5 mr-3 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Protocolos
                </a>
                <a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Usuários
                </a>
                <a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Conta
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-slate-100">
            <a href="logout.php" class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 hover:text-red-600 transition-colors">Sair</a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto p-8">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Protocolos</h1>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                <h2 class="text-lg font-bold text-slate-800">Filtrar e Pesquisar</h2>
            </div>
            <p class="text-sm text-slate-500 mb-4">Use os campos abaixo para encontrar protocolos específicos.</p>

            <form method="GET" action="" class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Pesquisar</label>
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <input type="text" name="busca" value="<?php echo htmlspecialchars($term); ?>" placeholder="Nome, CPF/matrícula ou patrimônio..." class="w-full bg-indigo-50 border border-indigo-100 text-slate-700 text-sm rounded-md focus:ring-indigo-500 focus:border-indigo-500 block pl-9 p-2.5 shadow-sm outline-none">
                    </div>
                </div>
                <button type="submit" class="hidden">Buscar</button>
            </form>
        </div>

        <div class="space-y-6">
            
            <?php if (empty($protocolos)): ?>
                <div class="text-center py-10 text-slate-500 bg-white rounded-lg border border-slate-200 border-dashed">
                    Nenhum protocolo encontrado.
                </div>
            <?php else: ?>

                <?php foreach ($protocolos as $id => $proto): ?>
                    <div class="bg-white rounded-lg shadow-sm border border-indigo-100 overflow-hidden">
                        <div class="bg-indigo-50/50 px-6 py-3 border-b border-indigo-100 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700">
                                Protocolo #<?php echo $id; ?> - <?php echo date('d/m/Y', strtotime($proto['info']['data'])); ?>
                            </h3>
                            <span class="text-xs text-indigo-400 font-mono"><?php echo date('H:i:s', strtotime($proto['info']['data'])); ?></span>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                            
                            <div class="space-y-2 text-sm">
                                <p>
                                    <strong class="text-slate-800">Recebedor:</strong> 
                                    <span class="text-slate-600"><?php echo htmlspecialchars($proto['info']['nome']); ?></span>
                                </p>
                                <p>
                                    <strong class="text-slate-800">CPF/Matrícula:</strong> 
                                    <span class="text-slate-600"><?php echo htmlspecialchars($proto['info']['cpf']); ?></span>
                                </p>
                                <p>
                                    <strong class="text-slate-800">Telefone:</strong> 
                                    <span class="text-slate-600"><?php echo htmlspecialchars($proto['info']['telefone']); ?></span>
                                </p>
                                <p>
                                    <strong class="text-slate-800">Email:</strong> 
                                    <span class="text-slate-600"><?php echo htmlspecialchars($proto['info']['email']); ?></span>
                                </p>
                                <p>
                                    <strong class="text-slate-800">Data do Registro:</strong> 
                                    <span class="text-slate-600"><?php echo date('d/m/Y, H:i:s', strtotime($proto['info']['data'])); ?></span>
                                </p>
                                <p class="mt-2">
                                    <a href="visualizar_pdf.php?id=<?php echo $id; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        Visualizar PDF
                                    </a>
                                </p>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <strong class="text-slate-800 text-sm block mb-1">Bens Entregues:</strong>
                                    <ul class="list-disc list-inside text-sm text-slate-600 space-y-1">
                                        <?php foreach ($proto['itens'] as $item): ?>
                                            <li>
                                                <span class="uppercase font-semibold text-xs text-indigo-600 bg-indigo-50 px-1 rounded"><?php echo htmlspecialchars($item['tipo']); ?></span>
                                                : <?php echo htmlspecialchars($item['codigo']); ?>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if(empty($proto['itens'])) echo "<li>Nenhum item listado.</li>"; ?>
                                    </ul>
                                </div>

                                <div>
                                    <strong class="text-slate-800 text-sm block mb-2">Assinatura:</strong>
                                    <div class="border border-slate-200 rounded-lg p-2 bg-white inline-block w-full h-32 flex items-center justify-center overflow-hidden">
                                        <?php if ($proto['info']['assinatura']): ?>
                                            <img src="<?php echo $proto['info']['assinatura']; ?>" alt="Assinatura" class="max-h-full max-w-full object-contain">
                                        <?php else: ?>
                                            <span class="text-xs text-slate-300">Sem assinatura</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </main>

</body>
</html>