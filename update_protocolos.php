<?php
// Script para adicionar verificação de permissão em protocolos.php

$file = 'protocolos.php';
$content = file_get_contents($file);

// Encontrar o bloco de segurança e adicionar verificação de função
$search = "if (!isset(\$_SESSION['admin_logado'])) {
    header(\"Location: login.php\");
    exit;
}";

$replace = "if (!isset(\$_SESSION['admin_logado'])) {
    header(\"Location: login.php\");
    exit;
}

// Verificar se o usuário tem permissão (apenas Funcionários e Administradores)
if (!isset(\$_SESSION['funcao']) || !in_array(\$_SESSION['funcao'], ['Funcionário', 'Administrador'])) {
    header(\"Location: dashboard.php?erro=sem_permissao\");
    exit;
}";

$content = str_replace($search, $replace, $content);

file_put_contents($file, $content);
echo "Protocolos.php atualizado com verificação de permissão!\n";
?>
