<?php
/**
 * SIDEBAR LATERAL REUTILIZÁVEL
 * 
 * Incluir em todas as páginas internas do sistema.
 * Requer as variáveis:
 *   $pagina_ativa - nome da página atual (ex: 'dashboard', 'protocolos', 'emprestimos', 'usuarios', 'conta')
 *   $is_admin  - boolean se o usuário é administrador
 *   $dados_usuario - array com dados do usuário (opcional, para exibir inicial)
 */

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

$pagina_ativa = $pagina_ativa ?? '';
$is_admin = $is_admin ?? false;
$dados_usuario = $dados_usuario ?? null;
?>

<aside class="w-64 bg-white border-r border-slate-200 flex flex-col justify-between shrink-0">
    <div>
        <a href="index.php" class="h-16 flex items-center px-6 border-b border-slate-100 hover:bg-gray-50 transition-colors">
            <svg class="w-6 h-6 text-indigo-700 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span class="font-bold text-slate-800 text-lg">Protocolos</span>
        </a>

        <nav class="mt-6 px-4 space-y-1">
            <?php if ($is_admin): ?>
            <a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?= $pagina_ativa === 'dashboard' ? 'text-indigo-700 bg-indigo-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?> rounded-lg">
                <svg class="w-5 h-5 mr-3 <?= $pagina_ativa === 'dashboard' ? 'text-indigo-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <?php endif; ?>

            <a href="protocolos.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?= $pagina_ativa === 'protocolos' ? 'text-indigo-700 bg-indigo-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?> rounded-lg">
                <svg class="w-5 h-5 mr-3 <?= $pagina_ativa === 'protocolos' ? 'text-indigo-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Protocolos
            </a>

            <a href="emprestimos.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?= $pagina_ativa === 'emprestimos' ? 'text-indigo-700 bg-indigo-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?> rounded-lg">
                <svg class="w-5 h-5 mr-3 <?= $pagina_ativa === 'emprestimos' ? 'text-indigo-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                Empréstimos
            </a>

            <?php if ($is_admin): ?>
            <a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?= $pagina_ativa === 'usuarios' ? 'text-indigo-700 bg-indigo-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?> rounded-lg">
                <svg class="w-5 h-5 mr-3 <?= $pagina_ativa === 'usuarios' ? 'text-indigo-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Usuários
            </a>
            <?php endif; ?>

            <a href="conta.php" class="flex items-center px-4 py-2.5 text-sm font-medium <?= $pagina_ativa === 'conta' ? 'text-indigo-700 bg-indigo-50' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' ?> rounded-lg">
                <svg class="w-5 h-5 mr-3 <?= $pagina_ativa === 'conta' ? 'text-indigo-700' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Conta
            </a>
        </nav>
    </div>

    <div class="p-4 border-t border-slate-100">
        <a href="logout.php" class="flex items-center px-4 py-2 text-sm font-medium text-slate-600 hover:text-red-600 transition-colors">
            <?php if ($dados_usuario && isset($dados_usuario['usuario'])): ?>
            <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center text-xs mr-3 font-bold">
                <?= strtoupper(substr($dados_usuario['usuario'], 0, 1)) ?>
            </div>
            <?php endif; ?>
            Sair
        </a>
    </div>
</aside>
