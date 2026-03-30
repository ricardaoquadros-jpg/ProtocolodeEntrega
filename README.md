# 🏛️ Protocolo de Entrega – Sistema de Atendimento da Prefeitura de Guaíba

![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![PHPMailer](https://img.shields.io/badge/PHPMailer-FF6C37?style=for-the-badge&logo=gmail&logoColor=white)
![Gemini 3 Pro](https://img.shields.io/badge/Gemini_3_Pro-AUTO?style=for-the-badge&logo=google&logoColor=white)
![Antigravity IDE](https://img.shields.io/badge/Antigravity_IDE-000000?style=for-the-badge&logo=googlechrome&logoColor=white)
![Google AI Studio](https://img.shields.io/badge/Google_AI_Studio-4285F4?style=for-the-badge&logo=google&logoColor=white)

---

Este repositório contém o sistema **Protocolo de Entrega**, desenvolvido para a Prefeitura de Guaíba com o objetivo de registrar atendimentos, gerar comprovantes oficiais e organizar toda a entrega de itens aos cidadãos. O sistema permite que o atendido insira seus dados, gere um protocolo em PDF e receba automaticamente uma cópia por e-mail.

O projeto foi criado inteiramente utilizando **Google AI Studio**, **Gemini 3 Pro** e desenvolvido/refinado na **IDE Antigravity**, garantindo agilidade, padronização e uma construção totalmente orientada a prompts.

---

## 🎯 Objetivo do Sistema

- Registrar informações de cidadãos atendidos  
- Gerar um **protocolo oficial de entrega** em PDF  
- Enviar automaticamente uma cópia do protocolo por e-mail  
- Registrar todos os protocolos no banco MySQL  
- Permitir que administradores monitorem atendimentos, datas, itens entregues e usuários responsáveis  

---

## 🚀 Funcionalidades Principais

### 📝 **Área do Cidadão**
- Inserção de:
  - Nome completo  
  - CPF ou matrícula  
  - E-mail  
  - Lista de itens entregues  
- Captura de **assinatura digital** via canvas (Base64)  
- Geração automática do PDF com todos os dados  
- Envio do PDF ao e-mail informado via **PHPMailer**  
- Registro completo no banco de dados  

---

### 🖥️ **Área Administrativa**
- Login para usuários autorizados  
- Visualização de todos os protocolos registrados  
- Listagem com:
  - Nome do atendido  
  - Data e hora  
  - Usuário responsável  
  - Itens entregues  
- Ferramentas de auditoria e monitoramento interno  

---

## 🛠 Tecnologias Utilizadas

### **Frontend**
- HTML5  
- CSS3  
- JavaScript  
- Canvas Base64 para assinatura digital  
- Geração de PDF via JavaScript  

### **Backend**
- PHP  
- MySQL  
- PHPMailer  
- API interna para gravação de registros  

### **Ferramentas de Desenvolvimento**
- Google AI Studio  
- Gemini 3 Pro  
- Antigravity IDE  
- Git & GitHub  

---

## 📁 Estrutura Geral do Projeto
```bash
/
├── index.php                  # Página inicial (menu principal)
├── login.php                  # Autenticação de usuários
├── cadastro.php               # Cadastro de novos usuários
├── logout.php                 # Encerramento de sessão
├── config.php                 # Configurações (banco, SMTP, etc.)
├── config.sample.php          # Template de configuração (para deploy)
├── conexao.php                # Conexão com banco MySQL
├── conexao.sample.php         # Template de conexão
│
├── novo_protocolo.php         # Formulário de novo protocolo
├── protocolos.php             # Listagem de protocolos (com paginação)
├── visualizar_pdf.php         # Visualização/geração de PDF
├── salvar.php                 # API: salvar protocolo
├── enviar_email.php           # API: enviar PDF por email
│
├── novo_emprestimo.php        # Formulário de empréstimo
├── emprestimos.php            # Listagem de empréstimos (com paginação)
├── salvar_emprestimo.php      # API: salvar empréstimo
├── devolver_emprestimo.php    # API: registrar devolução
├── buscar_emprestimos_ativos.php  # API: empréstimos ativos
│
├── dashboard.php              # Dashboard administrativo (gráficos/KPIs)
├── usuarios.php               # Gerenciamento de usuários (admin)
├── conta.php                  # Perfil do usuário logado
│
├── migrate.php                # Script de migração de banco
├── .htaccess                  # Regras de segurança e rewrite
├── composer.json              # Dependência: PHPMailer
│
├── utils/
│   ├── config_seguranca.php   # Headers e configuração de sessão
│   ├── seguranca.php          # Sanitização e CSRF
│   └── sidebar.php            # Componente lateral reutilizável
│
├── sql/
│   └── optimize_indices.sql   # Script de otimização MySQL
│
├── vendor/                    # Dependências (PHPMailer via Composer)
├── DEPLOY.md                  # Guia completo de implantação
└── README.md                  # Este arquivo
```
---

## 🔐 Segurança

- **Senhas** criptografadas com `password_hash` / `password_verify`  
- **Prepared statements** (parametrizados) em todas as queries  
- **Headers de segurança** (X-Content-Type-Options, X-Frame-Options, XSS Protection)
- **CSRF tokens** em formulários e ações AJAX  
- **Sessões seguras** (httponly, strict mode, regeneração de ID)  
- **Sanitização centralizada** de entradas  
- **`.htaccess`** com bloqueio de arquivos sensíveis  
- **Separação** entre área pública e administrativa  
- **Controle de cargo** (Administrador / Funcionário / Usuário)  

---

## 🧩 Arquitetura do Sistema

```mermaid
flowchart LR

    subgraph User["Cidadão"]
        A1["Preenche Formulário"]
        A2["Assina no Canvas"]
        A3["Envia Dados"]
    end

    subgraph Frontend["Frontend (HTML, CSS, JS)"]
        F1["Captura dos Dados"]
        F2["Assinatura Digital Base64"]
        F3["Geração do PDF (JavaScript)"]
    end

    subgraph Backend["Backend PHP"]
        B1["API Registrar Protocolo"]
        B2["API Gerar Log"]
        B3["Enviar PDF com PHPMailer"]
    end

    subgraph Database["Banco MySQL"]
        D1["Tabela Protocolos"]
        D2["Tabela Usuários"]
    end

    subgraph Admin["Painel Administrativo"]
        P1["Login do Funcionário"]
        P2["Dashboard"]
        P3["Monitoramento de Protocolos"]
    end

    %% Fluxo principal
    A1 --> F1
    A2 --> F2
    F1 --> B1
    F2 --> B1
    B1 --> F3
    F3 --> B3
    B1 --> D1

    %% Fluxo administrativo
    P1 --> P2
    P2 --> D1
    P3 --> D1

    %% Email
    B3 --> EMail["Envio do PDF para o Cidadão"]
```

## 🧑‍💻 Autor

**Ricardo Quadros**  
- Estudante de Engenharia da Computação na UERGS  
- Técnico em Informática na Dr. Solon Tavares 
- Estagiário de Tecnologia e Informação – Prefeitura de Guaíba  
- Guaíba, RS – Brasil

---

## 📫 Contato

- GitHub: https://github.com/ricardaoquadros-jpg  
- Email: ricardaoquadros@gmail.com
- Linkedin: https://www.linkedin.com/in/ricardopquadros/
