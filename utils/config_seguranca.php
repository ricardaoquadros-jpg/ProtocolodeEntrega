<?php
/**
 * CONFIGURAÇÃO DE SEGURANÇA CENTRALIZADA
 * Include ANTES de session_start() em todos os arquivos
 */

// Evita acesso direto
if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/* ============================================
   1. CONFIGURAÇÃO DE SESSÃO SEGURA
   ============================================ */

// Cookies HTTP-only (previne XSS)
ini_set('session.cookie_httponly', 1);

// Modo estrito de sessão (previne session fixation)
ini_set('session.use_strict_mode', 1);

// Apenas cookies para sessão (não URL)
ini_set('session.use_only_cookies', 1);

// Se HTTPS estiver ativo, usar cookies seguros
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

/* ============================================
   2. HEADERS DE SEGURANÇA
   ============================================ */

function aplicarHeadersSeguranca() {
    // Previne MIME-type sniffing
    header("X-Content-Type-Options: nosniff");
    
    // Previne clickjacking
    header("X-Frame-Options: SAMEORIGIN");
    
    // Ativa proteção XSS do navegador
    header("X-XSS-Protection: 1; mode=block");
    
    // Política de referrer
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

/* ============================================
   3. PROTEÇÃO CSRF
   ============================================ */

function gerarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function inputCSRF() {
    $token = gerarTokenCSRF();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function validarCSRF() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/* ============================================
   4. REGENERAR SESSÃO (chamar após login)
   ============================================ */

function regenerarSessao() {
    session_regenerate_id(true);
}

?>
