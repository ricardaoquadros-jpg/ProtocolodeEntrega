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

// Carrega configurações se existirem (para obter FORCE_HTTPS, ERROR_LOG_PATH, etc)
$config_file = null;
if (file_exists(__DIR__ . '/../config.php')) {
    $config_file = __DIR__ . '/../config.php';
} elseif (file_exists(__DIR__ . '/../../config.php')) {
    $config_file = __DIR__ . '/../../config.php';
}

if ($config_file) {
    require_once $config_file;
}

// Configura logs de erro de forma centralizada
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
$logPath = defined('ERROR_LOG_PATH') ? ERROR_LOG_PATH : __DIR__ . '/../logs_php_errors.log';
ini_set('error_log', $logPath);

// Redirecionamento HTTPS se configurado
if (defined('FORCE_HTTPS') && FORCE_HTTPS) {
    if ((!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') && 
        (empty($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https')) {
        header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
        exit;
    }
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
    
    // Content-Security-Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.tailwindcss.com cdn.jsdelivr.net cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data: i.imgur.com; connect-src 'self'; frame-ancestors 'none';");
    
    // HSTS (Strict-Transport-Security)
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
    
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

/**
 * Valida CSRF para requisições AJAX.
 * Aceita o token via campo POST (csrf_token) OU via header HTTP X-CSRF-Token,
 * permitindo proteger tanto envios FormData quanto fetch() com corpo JSON.
 */
function validarCSRFRequest() {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/* ============================================
   4. REGENERAR SESSÃO (chamar após login)
   ============================================ */

function regenerarSessao() {
    session_regenerate_id(true);
}

?>
