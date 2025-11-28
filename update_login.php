<?php
// Script para atualizar login.php com suporte a funcao

$file = 'login.php';
$content = file_get_contents($file);

// 1. Adicionar funcao no SELECT
$content = str_replace(
    'SELECT id, senha_hash FROM usuarios_admin',
    'SELECT id, senha_hash, funcao FROM usuarios_admin',
    $content
);

// 2. Adicionar funcao na sessão
$content = str_replace(
    "\$_SESSION['admin_id'] = \$row['id'];",
    "\$_SESSION['admin_id'] = \$row['id'];\r\n                    \$_SESSION['funcao'] = \$row['funcao'];",
    $content
);

file_put_contents($file, $content);
echo "Login.php atualizado com sucesso!\n";
?>
