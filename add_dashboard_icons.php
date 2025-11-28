<?php
// Script para adicionar ícones ao menu do dashboard.php

$file = 'dashboard.php';
$content = file_get_contents($file);

// Adicionar ícone de protocolos
$content = str_replace(
    '<a href="protocolos.php" class="flex items-center px-4 py-2.5 text-sm font-medium 
            text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                Protocolos
            </a>',
    '<a href="protocolos.php" class="flex items-center px-4 py-2.5 text-sm font-medium 
            text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Protocolos
            </a>',
    $content
);

// Adicionar ícone de usuários
$content = str_replace(
    '<a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium 
            text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                Usuários
            </a>',
    '<a href="usuarios.php" class="flex items-center px-4 py-2.5 text-sm font-medium 
            text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                Usuários
            </a>',
    $content
);

// Adicionar ícone de conta (ativo)
$content = str_replace(
    '<a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium 
            text-indigo-700 bg-indigo-50 rounded-lg">
                Conta
            </a>',
    '<a href="dashboard.php" class="flex items-center px-4 py-2.5 text-sm font-medium 
            text-indigo-700 bg-indigo-50 rounded-lg">
                <svg class="w-5 h-5 mr-3 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                Conta
            </a>',
    $content
);

file_put_contents($file, $content);
echo "Ícones adicionados ao dashboard.php com sucesso!\n";
?>
