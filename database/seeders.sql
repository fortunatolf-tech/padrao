-- =============================================================================
-- DADOS INICIAIS (SEEDERS) - SISTEMA DE VOTAÇÃO DOS PADRÕES COMARA
-- =============================================================================

SET NAMES utf8mb4;

-- 1. CADASTRO DAS DIVISÕES E ASSESSORIAS REGIMENTAIS (ROCA 21-55)
INSERT INTO `divisoes_assessorias` (`sigla`, `nome`, `tipo`, `ativo`) VALUES
('DPC',  'Divisão de Planejamento e Coordenação',            'DIVISAO',    1),
('DA',   'Divisão de Apoio',                                 'DIVISAO',    1),
('DL',   'Divisão de Logística',                             'DIVISAO',    1),
('DE',   'Divisão de Engenharia',                            'DIVISAO',    1),
('DS',   'Divisão de Suprimento',                            'DIVISAO',    1),
('AINT', 'Assessoria de Inteligência',                       'ASSESSORIA', 1),
('ACI',  'Assessoria de Controle Interno',                   'ASSESSORIA', 1),
('AGOV', 'Assessoria de Governança',                         'ASSESSORIA', 1),
('APOG', 'Assessoria de Planejamento e Orçamento de Gestão', 'ASSESSORIA', 1),
('SCS',  'Seção de Comunicação Social',                      'ASSESSORIA', 1),
('AJUR', 'Assessoria Jurídica',                              'ASSESSORIA', 1),
('SIJ',  'Seção de Investigação e Justiça',                  'ASSESSORIA', 1)
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `tipo` = VALUES(`tipo`);

-- 2. CRIAÇÃO DO CICLO ELEITORAL DE REFERÊNCIA (OUTUBRO / NOVEMBRO 2026)
INSERT INTO `pleitos` (
    `ano`, `titulo`, `descricao`, `fase_atual`, `status`,
    `fase1_inicio`, `fase1_fim`,
    `fase2_inicio`, `fase2_fim`,
    `fase3_inicio`, `fase3_fim`,
    `fase4_inicio`, `fase4_fim`,
    `fase5_inicio`, `fase5_fim`
) VALUES (
    2026,
    'Eleição dos Padrões COMARA 2026',
    'Processo anual para escolha dos Padrões Militar e Civil da Comissão de Aeroportos da Região Amazônica.',
    1,
    'em_andamento',
    '2026-10-01 08:00:00', '2026-10-15 18:00:00', -- Fase 1: Chefes Diretos & TACF
    '2026-10-16 08:00:00', '2026-10-23 18:00:00', -- Fase 2: Chefes Divisão/Assessoria (Top 6)
    '2026-10-24 08:00:00', '2026-11-05 18:00:00', -- Fase 3: Votação Geral do Efetivo
    '2026-11-06 08:00:00', '2026-11-12 18:00:00', -- Fase 4: Validação pela Direção Superior
    '2026-11-13 08:00:00', '2026-11-20 18:00:00'  -- Fase 5: Homologação e Placa
) ON DUPLICATE KEY UPDATE `titulo` = VALUES(`titulo`);

-- 3. USUÁRIOS PADRÃO DO SISTEMA (Senhas temporárias com hash bcrypt)
-- Senha padrão para contas iniciais: 'padrao@2026' -> $2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2
-- O sistema permite login com essas contas locais caso o Active Directory esteja inacessível ou em testes.

INSERT INTO `usuarios` (`login`, `senha_hash`, `perfil`, `orgao_chefe_sigla`, `ativo`) VALUES
-- Administrador Geral (TI / Suporte Técnico)
('admin', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'ADMIN', NULL, 1),

-- Presidente da COMARA
('presidente', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'PRESIDENTE', NULL, 1),

-- Direção Superior (Oficiais Superiores)
('oficial.superior', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'DIRECAO_SUPERIOR', NULL, 1),

-- Seção de Educação Física (Exclusivo TACF)
('ed.fisica', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'ED_FISICA', 'DA', 1),

-- Chefes das 5 Divisões
('chefe.dpc', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'DPC', 1),
('chefe.da',  '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'DA', 1),
('chefe.dl',  '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'DL', 1),
('chefe.de',  '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'DE', 1),
('chefe.ds',  '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'DS', 1),

-- Chefes das 7 Assessorias
('chefe.aint', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'AINT', 1),
('chefe.aci',  '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'ACI', 1),
('chefe.agov', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'AGOV', 1),
('chefe.apog', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'APOG', 1),
('chefe.scs',  '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'SCS', 1),
('chefe.ajur', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'AJUR', 1),
('chefe.sij',  '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIV_ASSESS', 'SIJ', 1),

-- Chefe Direto exemplo
('chefe.direto', '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2', 'CHEFE_DIRETO', 'DL', 1)
ON DUPLICATE KEY UPDATE `perfil` = VALUES(`perfil`);
