<?php

// Evita acesso direto
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/**
 * Sanitiza string simples (nome, texto curto etc)
 */
function limparTexto($str) {
    return trim(htmlspecialchars($str, ENT_QUOTES, 'UTF-8'));
}

/**
 * Sanitiza CPF, matrícula, códigos numéricos
 */
function limparNumero($num) {
    return preg_replace('/[^0-9]/', '', $num);
}

/**
 * Sanitiza telefone (aceita apenas números)
 */
function limparTelefone($tel) {
    return preg_replace('/[^0-9]/', '', $tel);
}

/**
 * Sanitiza e valida email
 */
function limparEmail($email) {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Sanitiza array de itens enviados do formulário
 */
function limparItens($itens) {
    $limpos = [];

    foreach ($itens as $item) {
        $limpos[] = [
            'patrimonio' => limparTexto($item['patrimonio'] ?? ''),
            'equipamento' => limparTexto($item['equipamento'] ?? '')
        ];
    }

    return $limpos;
}

?>
