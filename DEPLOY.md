# Guia de Deploy - Sistema de Protocolos

## Requisitos do Servidor

| Item | Requisito Mínimo |
|------|------------------|
| PHP | 8.0 ou superior |
| MySQL/MariaDB | 5.7 ou superior |
| Servidor Web | Apache 2.4+ com mod_rewrite |
| Extensões PHP | mysqli, mbstring, openssl, fileinfo |

## Instalação Passo a Passo

### 1. Preparar Arquivos

```bash
# Copiar todos os arquivos para o servidor
# (exceto _dev/, logs, e arquivos de configuração local)
```

### 2. Configurar Banco de Dados

1. Crie um banco de dados MySQL:
```sql
CREATE DATABASE protocolo_ti CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'protocolo_user'@'localhost' IDENTIFIED BY 'sua_senha_segura';
GRANT ALL PRIVILEGES ON protocolo_ti.* TO 'protocolo_user'@'localhost';
FLUSH PRIVILEGES;
```

2. Importe a estrutura inicial:
```bash
mysql -u protocolo_user -p protocolo_ti < banco.sql
```

### 3. Configurar Aplicação

1. Copie o template de configuração:
```bash
cp config.sample.php config.php
```

2. Edite `config.php` com suas configurações:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'protocolo_user');
define('DB_PASS', 'sua_senha_segura');
define('DB_NAME', 'protocolo_ti');

define('CODIGO_ACESSO_CADASTRO', 'SEU_CODIGO_SECRETO');

define('SMTP_HOST', 'smtp.seuservidor.com');
// ... outras configurações
```

### 4. Executar Migrações

Acesse no navegador:
```
https://seudominio.com/migrate.php?key=MIGRAR_PROTOCOLO_2024
```

> ⚠️ **IMPORTANTE**: Após executar, delete ou renomeie o arquivo `migrate.php`!

### 5. Configurar HTTPS

Edite o arquivo `.htaccess` e descomente as linhas:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 6. Permissões de Arquivos

```bash
# Arquivos: 644
find . -type f -exec chmod 644 {} \;

# Diretórios: 755
find . -type d -exec chmod 755 {} \;

# Proteger arquivos sensíveis
chmod 600 config.php
```

## Primeiro Acesso

1. Acesse `https://seudominio.com/login.php`
2. Clique em "Criar conta"
3. Use o código de acesso configurado
4. Faça login com a conta criada
5. Na página de Usuários, promova sua conta para "Administrador"

## Checklist Pós-Deploy

- [ ] HTTPS funcionando
- [ ] Login/Logout funcionando
- [ ] Cadastro com código de acesso funcionando
- [ ] Criação de protocolo funcionando
- [ ] Geração de PDF funcionando
- [ ] Envio de e-mail funcionando (teste interno)
- [ ] Dashboard mostrando dados
- [ ] migrate.php removido ou protegido

## Troubleshooting

### Erro de conexão com banco
- Verifique credenciais em `config.php`
- Verifique se o MySQL está rodando
- Verifique permissões do usuário

### PDF não gera
- Verifique se JavaScript está habilitado
- Verifique console do navegador (F12)

### E-mail não envia
- Verifique configurações SMTP em `config.php`
- Verifique logs do PHP (`logs_php_errors.log`)
- Teste conectividade com servidor SMTP

## Suporte

Em caso de problemas, verifique:
1. Logs de erro: `logs_php_errors.log`
2. Logs do Apache: `/var/log/apache2/error.log`
3. Logs do MySQL: verifique configuração do servidor
