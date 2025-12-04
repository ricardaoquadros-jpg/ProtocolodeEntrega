-- ============================================
-- SCRIPT DE OTIMIZAÇÃO MySQL - ProtocoloTI
-- Execute este script no phpMyAdmin ou MySQL CLI
-- ============================================

-- IMPORTANTE: Faça backup antes de executar!
-- mysqldump -u root banco > backup_banco.sql

USE banco;

-- ============================================
-- 1. ÍNDICES PARA PERFORMANCE
-- ============================================

-- Índice para buscas por data (usado no dashboard e filtros)
ALTER TABLE protocolos 
ADD INDEX idx_data_criacao (data_criacao);

-- Índice para JOINs entre protocolos e itens
ALTER TABLE protocolo_itens 
ADD INDEX idx_protocolo_id (protocolo_id);

-- Índice para login rápido
ALTER TABLE usuarios_admin 
ADD INDEX idx_usuario (usuario);

-- Índice composto para busca de protocolos
ALTER TABLE protocolos 
ADD INDEX idx_busca (nome_recebedor(50), cpf_matricula);

-- ============================================
-- 2. VERIFICAR ÍNDICES CRIADOS
-- ============================================

SHOW INDEX FROM protocolos;
SHOW INDEX FROM protocolo_itens;
SHOW INDEX FROM usuarios_admin;

-- ============================================
-- 3. OTIMIZAR TABELAS (EXECUTAR OFFLINE)
-- ============================================

-- OPTIMIZE TABLE protocolos;
-- OPTIMIZE TABLE protocolo_itens;
-- OPTIMIZE TABLE usuarios_admin;

-- ============================================
-- RESULTADO ESPERADO:
-- - Consultas de dashboard: 5-10x mais rápidas
-- - Buscas por nome/CPF: 10-50x mais rápidas
-- - JOINs com itens: 5-20x mais rápidos
-- ============================================
