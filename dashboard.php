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

/* --- VERIFICA SE É ADMINISTRADOR --- */
$id_usuario = intval($_SESSION['admin_id']);
$stmt = $conn->prepare("SELECT funcao FROM usuarios_admin WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user || trim($user['funcao']) !== 'Administrador') {
    echo "<script>alert('Acesso Negado: Apenas Administradores podem acessar o Dashboard.'); window.location.href='protocolos.php';</script>";
    exit;
}

/* --- FILTRO DE DATA --- */
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');

/* ===============================================
   QUERIES OTIMIZADAS (7 queries → 4 queries)
   =============================================== */

// 1. KPIs UNIFICADOS (4 queries → 1 query)
$sqlKPIs = "SELECT 
    COUNT(*) as total_periodo,
    SUM(DATE(data_criacao) = CURDATE()) as hoje,
    SUM(YEAR(data_criacao) = YEAR(NOW()) AND MONTH(data_criacao) = MONTH(NOW())) as mes,
    (SELECT COUNT(*) FROM protocolo_itens) as total_itens
FROM protocolos 
WHERE data_criacao BETWEEN ? AND ?";

$stmtKPIs = $conn->prepare($sqlKPIs);
$data_inicio_full = $data_inicio . ' 00:00:00';
$data_fim_full = $data_fim . ' 23:59:59';
$stmtKPIs->bind_param("ss", $data_inicio_full, $data_fim_full);
$stmtKPIs->execute();
$kpis = $stmtKPIs->get_result()->fetch_assoc();

$totalProtocolos = $kpis['total_periodo'] ?? 0;
$protocolosHoje = $kpis['hoje'] ?? 0;
$protocolosMes = $kpis['mes'] ?? 0;
$totalItens = $kpis['total_itens'] ?? 0;

// 2. Gráfico Evolução (com prepared statement)
$sqlEvolucao = "SELECT DATE(data_criacao) as dia, COUNT(*) as total 
                FROM protocolos 
                WHERE data_criacao BETWEEN ? AND ?
                GROUP BY DATE(data_criacao) 
                ORDER BY dia ASC";
$stmtEvolucao = $conn->prepare($sqlEvolucao);
$stmtEvolucao->bind_param("ss", $data_inicio_full, $data_fim_full);
$stmtEvolucao->execute();
$resEvolucao = $stmtEvolucao->get_result();

$evolucaoData = [];
$evolucaoLabels = [];
while($row = $resEvolucao->fetch_assoc()) {
    $evolucaoLabels[] = date('d/m', strtotime($row['dia']));
    $evolucaoData[] = $row['total'];
}

// 3. Gráfico Distribuição por Tipo (com prepared statement)
$sqlDist = "SELECT pi.tipo_equipamento, COUNT(*) as total 
            FROM protocolo_itens pi
            JOIN protocolos p ON pi.protocolo_id = p.id
            WHERE p.data_criacao BETWEEN ? AND ?
            GROUP BY pi.tipo_equipamento";
$stmtDist = $conn->prepare($sqlDist);
$stmtDist->bind_param("ss", $data_inicio_full, $data_fim_full);
$stmtDist->execute();
$resDist = $stmtDist->get_result();

$distLabels = [];
$distData = [];
while($row = $resDist->fetch_assoc()) {
    $distLabels[] = $row['tipo_equipamento'];
    $distData[] = $row['total'];
}

// 4. Últimos Protocolos (já otimizado com LIMIT)
$sqlRecentes = "SELECT id, nome_recebedor, data_criacao FROM protocolos ORDER BY data_criacao DESC LIMIT 5";
$resRecentes = $conn->query($sqlRecentes);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Protocolo TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-indigo-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0">
        <div>
            <a href="index.html" class="h-16 flex items-center px-6 border-b border-slate-100 hover:bg-gray-50 transition-colors">
                <svg class="w-6 h-6 text-indigo-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span class="font-bold text-slate-800 text-lg">Protocolos</span>
            </a>

            <nav class="mt-6 px-4 space-y-1">
                <a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium text-indigo-700 bg-indigo-50 rounded-lg">
                    <svg class="w-5 h-5 mr-3 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </a>
                <a href="protocolos.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Protocolos
                </a>
                <a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Usuários
                </a>
                <a href="conta.php" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Conta
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-slate-100">
            <a href="logout.php" class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 hover:text-red-600 transition-colors">
                <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs mr-3 font-bold">
                    A
                </div>
                Sair
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto p-8">
        
        <!-- Header & Filter -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <h1 class="text-2xl font-bold text-slate-800">Dashboard</h1>
            
            <form method="GET" class="flex items-center gap-2 bg-white p-2 rounded-lg shadow-sm border border-slate-200">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-500 uppercase">Período:</span>
                    <input type="date" name="data_inicio" value="<?= $data_inicio ?>" class="text-sm border-slate-200 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-slate-600">
                    <span class="text-slate-400">-</span>
                    <input type="date" name="data_fim" value="<?= $data_fim ?>" class="text-sm border-slate-200 rounded-md focus:ring-indigo-500 focus:border-indigo-500 text-slate-600">
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-3 py-1.5 rounded text-sm hover:bg-indigo-700 transition">Filtrar</button>
            </form>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Card 1 -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Protocolos (Período)</p>
                        <h3 class="text-3xl font-bold text-slate-800 mt-2"><?= $totalProtocolos ?></h3>
                    </div>
                    <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Hoje</p>
                        <h3 class="text-3xl font-bold text-slate-800 mt-2"><?= $protocolosHoje ?></h3>
                    </div>
                    <div class="p-2 bg-green-50 rounded-lg text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Mês Atual</p>
                        <h3 class="text-3xl font-bold text-slate-800 mt-2"><?= $protocolosMes ?></h3>
                    </div>
                    <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Total Itens Movimentados</p>
                        <h3 class="text-3xl font-bold text-slate-800 mt-2"><?= $totalItens ?></h3>
                    </div>
                    <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                </div>
            </div>

        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Evolution Chart -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 lg:col-span-2">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Evolução de Protocolos</h3>
                <div class="h-64">
                    <canvas id="evolucaoChart"></canvas>
                </div>
            </div>

            <!-- Distribution Chart -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Tipos de Equipamento</h3>
                <div class="h-64 flex justify-center">
                    <canvas id="distChart"></canvas>
                </div>
            </div>

        </div>

        <!-- Recent Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">Últimos Protocolos</h3>
                <a href="protocolos.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Recebedor</th>
                            <th class="px-6 py-3">Data</th>
                            <th class="px-6 py-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $resRecentes->fetch_assoc()): ?>
                        <tr class="bg-white border-b hover:bg-slate-50">
                            <td class="px-6 py-4 font-medium text-slate-900">#<?= $row['id'] ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($row['nome_recebedor']) ?></td>
                            <td class="px-6 py-4"><?= date('d/m/Y H:i', strtotime($row['data_criacao'])) ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="visualizar_pdf.php?id=<?= $row['id'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-medium">PDF</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        // Configuração dos Gráficos
        const evolucaoCtx = document.getElementById('evolucaoChart').getContext('2d');
        const distCtx = document.getElementById('distChart').getContext('2d');

        // Dados PHP para JS
        const evolucaoLabels = <?= json_encode($evolucaoLabels) ?>;
        const evolucaoData = <?= json_encode($evolucaoData) ?>;
        const distLabels = <?= json_encode($distLabels) ?>;
        const distData = <?= json_encode($distData) ?>;

        // Gráfico de Evolução (Linha)
        new Chart(evolucaoCtx, {
            type: 'line',
            data: {
                labels: evolucaoLabels,
                datasets: [{
                    label: 'Protocolos',
                    data: evolucaoData,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // Gráfico de Distribuição (Doughnut)
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: distLabels,
                datasets: [{
                    data: distData,
                    backgroundColor: [
                        '#4f46e5', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>

</body>
</html>
