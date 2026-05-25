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
            'transacao' => limparTexto($item['transacao'] ?? 'ENTREGA'),
            'equipamento' => limparTexto($item['equipamento'] ?? ''),
            'emprestimo_id' => isset($item['emprestimo_id']) ? intval($item['emprestimo_id']) : null
        ];
    }

    return $limpos;
}

/**
 * --- PROTEÇÃO CONTRA FORÇA BRUTA ---
 * Usa a tabela tentativas_login. Se a tabela não existir (prepare falha),
 * as funções degradam de forma segura (não bloqueiam o login legítimo).
 */
function loginBloqueado($conn, $ip, $maxTentativas = null, $janelaMinutos = null) {
    if ($maxTentativas === null) {
        $maxTentativas = defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 5;
    }
    if ($janelaMinutos === null) {
        $janelaMinutos = defined('LOGIN_LOCKOUT_MINUTES') ? LOGIN_LOCKOUT_MINUTES : 15;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM tentativas_login WHERE ip_endereco = ? AND tentativa_tempo > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("si", $ip, $janelaMinutos);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ((int) ($row['c'] ?? 0)) >= $maxTentativas;
}

function registrarTentativaLogin($conn, $ip, $usuario) {
    $stmt = $conn->prepare("INSERT INTO tentativas_login (ip_endereco, usuario) VALUES (?, ?)");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("ss", $ip, $usuario);
    $stmt->execute();
    $stmt->close();
}

function limparTentativasLogin($conn, $ip) {
    $stmt = $conn->prepare("DELETE FROM tentativas_login WHERE ip_endereco = ?");
    if (!$stmt) {
        return;
    }
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Obtém os dados do usuário logado diretamente do banco de dados
 */
function obterUsuarioLogado($conn, $userId) {
    $stmt = $conn->prepare("SELECT id, usuario, email, funcao, nome_completo, telefone FROM usuarios_admin WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Verifica se o usuário logado é Administrador (autoritativo via DB)
 */
function checarAcessoAdministrador($conn) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logado']) || !isset($_SESSION['admin_id'])) {
        return false;
    }
    $user = obterUsuarioLogado($conn, intval($_SESSION['admin_id']));
    return ($user && trim($user['funcao']) === 'Administrador');
}

/**
 * Verifica se o usuário logado é Funcionário ou Administrador (autoritativo via DB)
 */
function checarAcessoFuncionario($conn) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logado']) || !isset($_SESSION['admin_id'])) {
        return false;
    }
    $user = obterUsuarioLogado($conn, intval($_SESSION['admin_id']));
    if (!$user) {
        return false;
    }
    $funcao = trim($user['funcao']);
    return ($funcao === 'Administrador' || $funcao === 'Funcionário');
}

/**
 * Registra um evento na tabela de auditoria (LGPD)
 */
function registrarLogAuditoria($conn, $acao, $detalhes = '') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $usuario_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null;
    $usuario_nome = $_SESSION['usuario'] ?? null;
    
    if (!$usuario_nome && $usuario_id) {
        $user = obterUsuarioLogado($conn, $usuario_id);
        if ($user) {
            $usuario_nome = $user['usuario'];
        }
    }
    if (!$usuario_nome) {
        $usuario_nome = 'Desconhecido';
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    $stmt = $conn->prepare("INSERT INTO logs_auditoria (usuario_id, usuario_nome, acao, detalhes, ip_endereco) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $usuario_id, $usuario_nome, $acao, $detalhes, $ip);
        $stmt->execute();
        $stmt->close();
    }
}

?>
