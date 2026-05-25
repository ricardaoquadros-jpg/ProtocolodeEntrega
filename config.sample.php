<?php
/**
 * TEMPLATE DE CONFIGURAÇÃO DO SISTEMA
 * 
 * Copie este arquivo para 'config.php' e preencha com suas credenciais.
 * O arquivo config.php NÃO deve ser versionado no Git.
 */

if (!defined('APP_RUNNING')) {
    http_response_code(403);
    exit('Acesso negado.');
}

/* ============================================
   BANCO DE DADOS
   ============================================ */
define('DB_HOST', 'seu_host_aqui');        // Ex: localhost, mysql.servidor.com
define('DB_USER', 'seu_usuario_aqui');     // Ex: protocolo_user
define('DB_PASS', 'sua_senha_aqui');       // Senha do banco
define('DB_NAME', 'seu_banco_aqui');       // Nome do banco de dados

/* ============================================
   CÓDIGO DE ACESSO PARA CADASTRO
   Código que novos funcionários precisam para criar conta
   ============================================ */
define('CODIGO_ACESSO_CADASTRO', 'SEU_CODIGO_SECRETO');

/* ============================================
   CONFIGURAÇÕES DE E-MAIL (SMTP)
   ============================================ */
define('SMTP_HOST', 'smtp.seuservidor.com');
define('SMTP_PORT', 25);
define('SMTP_AUTH', false);                 // true se precisar autenticação
define('SMTP_USER', '');                    // Usuário SMTP (se AUTH = true)
define('SMTP_PASS', '');                    // Senha SMTP (se AUTH = true)
define('EMAIL_FROM', 'noreply@seudominio.com');
define('EMAIL_FROM_NAME', 'Protocolo TI');

/* ============================================
   CONFIGURAÇÕES DO SISTEMA
   ============================================ */
define('DEBUG_MODE', false);                // NUNCA true em produção!
define('TIMEZONE', 'America/Sao_Paulo');

/* ============================================
   CONFIGURAÇÃO DE SEGURANÇA E ERROS
   ============================================ */
define('FORCE_HTTPS', true);                // Em produção mantenha true (força HTTPS)
define('MIGRATION_KEY', 'MUDE_ESTA_CHAVE'); // OBRIGATÓRIO trocar: migrate.php não roda com este valor
define('ERROR_LOG_PATH', __DIR__ . '/logs_php_errors.log'); // IDEAL: aponte para um caminho FORA do webroot

/* ============================================
   PROTEÇÃO CONTRA FORÇA BRUTA (LOGIN)
   ============================================ */
define('MAX_LOGIN_ATTEMPTS', 5);            // Tentativas por IP antes do bloqueio temporário
define('LOGIN_LOCKOUT_MINUTES', 15);        // Janela de bloqueio em minutos

/* ============================================
   URLs E CAMINHOS
   ============================================ */
define('BASE_URL', '/');                    // Caminho base da aplicação

?>
