<?php
// Script para corrigir o link da logo em protocolos.php

$file = 'protocolos.php';
$content = file_get_contents($file);

// Substituir a div da logo por um link
$search = '<div class="h-16 flex items-center px-6 border-b border-slate-100">';
$replace = '<a href="index.html" class="h-16 flex items-center px-6 border-b border-slate-100 hover:bg-gray-50 transition-colors">';

$content = str_replace($search, $replace, $content);

// Substituir o fechamento da div por fechamento de link (apenas a primeira ocorrência após a abertura)
// Como str_replace substitui todas, precisamos ser mais específicos ou usar preg_replace com limit
// Vamos achar a posição da string substituída e procurar o próximo </div>

$pos = strpos($content, $replace);
if ($pos !== false) {
    $closeDivPos = strpos($content, '</div>', $pos);
    if ($closeDivPos !== false) {
        $content = substr_replace($content, '</a>', $closeDivPos, 6);
    }
}

file_put_contents($file, $content);
echo "Link da logo atualizado em protocolos.php!\n";
?>
