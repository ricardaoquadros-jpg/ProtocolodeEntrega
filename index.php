<?php
define('APP_RUNNING', true);

require_once __DIR__ . '/utils/config_seguranca.php';
require_once __DIR__ . '/utils/seguranca.php';
require_once __DIR__ . '/conexao.php';

session_start();

// Enforce login on the landing page
// Enforce login on the landing page
if (!isset($_SESSION['admin_logado'])) {
    header("Location: login.php");
    exit;
}

// Fetch user role
$id_usuario = intval($_SESSION['admin_id']);
$stmt = $conn->prepare("SELECT funcao FROM usuarios_admin WHERE id = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$is_admin = ($user && trim($user['funcao']) === 'Administrador');

$is_logged_in = true;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protocolo de Entrega - Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md border border-slate-200">
        
        <div class="flex justify-center mb-6">
            <div class="bg-indigo-50 p-4 rounded-full">
                <svg class="w-10 h-10 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-bold text-center text-slate-800 mb-2">Protocolo de Entrega</h1>
        <p class="text-center text-slate-500 text-sm mb-8">Selecione uma opção para continuar</p>

        <div class="space-y-4">
            <a href="novo_protocolo.php" class="block w-full">
                <div class="flex items-center p-4 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition-colors group cursor-pointer">
                    <div class="bg-white p-2 rounded-md shadow-sm group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-md font-bold text-indigo-900">Protocolo</h3>
                        <p class="text-xs text-indigo-600">Registro de entrega de computadores, periféricos e outros ativos.</p>
                    </div>
                    <svg class="w-5 h-5 text-indigo-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            <a href="novo_emprestimo.php" class="block w-full">
                <div class="flex items-center p-4 bg-amber-50 border border-amber-100 rounded-lg hover:bg-amber-100 transition-colors group cursor-pointer">
                    <div class="bg-white p-2 rounded-md shadow-sm group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-md font-bold text-amber-900">Empréstimo</h3>
                        <p class="text-xs text-amber-600">Registro de empréstimo de computadores, teclados, TVs e outros equipamentos.</p>
                    </div>
                    <svg class="w-5 h-5 text-amber-400 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>

            <?php if ($is_admin): ?>
            <a href="dashboard.php" class="block w-full">
                <div class="flex items-center p-4 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors group cursor-pointer shadow-sm">
                    <div class="bg-indigo-50 p-2 rounded-md group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-md font-bold text-slate-700">Área Restrita</h3>
                        <p class="text-xs text-slate-500">Gestão e Consultas</p>
                    </div>
                    <svg class="w-5 h-5 text-slate-300 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>
            <?php else: ?>
            <a href="protocolos.php" class="block w-full">
                <div class="flex items-center p-4 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors group cursor-pointer shadow-sm">
                    <div class="bg-indigo-50 p-2 rounded-md group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-md font-bold text-slate-700">Consultar Protocolos</h3>
                        <p class="text-xs text-slate-500">Histórico de Entregas</p>
                    </div>
                    <svg class="w-5 h-5 text-slate-300 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>
            <?php endif; ?>
        </div>

        <div class="mt-8 text-center border-t border-slate-100 pt-4">
            <p class="text-xs text-slate-400 mb-2">Você está logado.</p>
            <a href="logout.php" class="text-sm text-red-500 hover:text-red-700 font-medium flex justify-center items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Sair
            </a>
        </div>

    </div>

</body>
</html>