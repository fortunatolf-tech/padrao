<?php
declare(strict_types=1);

/**
 * Script de Carga e Migração do Efetivo da COMARA
 * Preenche o efetivo oficial com segmentação automática nas 4 categorias:
 * 1. Graduado Padrão (SO, 1S, 2S, 3S)
 * 2. Praça Padrão (CB, S1, S2)
 * 3. Servidor Civil Permanente Padrão (SPPF)
 * 4. Servidor Civil Temporário Padrão (SPTF - Lei 8.745/93)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== INICIANDO CARGA DO EFETIVO DA COMARA ===\n";

$pdo = Database::getConnection();

// Carregar schema e seeders caso ainda não executados
$schemaSql = file_get_contents(__DIR__ . '/schema.sql');
$pdo->exec($schemaSql);
echo "[OK] Schema verificado e estruturado.\n";

$seedersSql = file_get_contents(__DIR__ . '/seeders.sql');
$pdo->exec($seedersSql);
echo "[OK] Seeders de divisões, assessorias e pleito 2026 aplicados.\n";

// Array de Militares e Civis da COMARA
// Mapeamento baseado no roster oficial da COMARA (Joomla Efetivo / SIGPES)
$efetivo = [
    // --- OFICIAIS (DIREÇÃO SUPERIOR E CHEFIAS) ---
    ['saram' => '3148637', 'cpf' => '111.111.111-01', 'nome' => 'ANTÔNIO CARLOS NEVES TRIGUEIRO', 'nome_guerra' => 'TRIGUEIRO', 'posto' => 'Cel', 'quadro' => 'QOAV', 'esp' => 'NTE', 'setor' => 'VP', 'divisao' => 'VP', 'funcao' => 'Vice-Presidente', 'categoria' => 'ineligivel', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2019-01-15', 'om' => '2019-01-15'],
    ['saram' => '3411982', 'cpf' => '111.111.111-02', 'nome' => 'ANTONIO JOSÉ DE JESUS BELEM LEITÃO JUNIOR', 'nome_guerra' => 'LEITÃO', 'posto' => 'Cel', 'quadro' => 'QOINT', 'esp' => 'FSU', 'setor' => 'DA', 'divisao' => 'DA', 'funcao' => 'Chefe da Divisão de Apoio', 'categoria' => 'ineligivel', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2020-02-10', 'om' => '2020-02-10'],
    ['saram' => '3257347', 'cpf' => '111.111.111-03', 'nome' => 'ADENIRSON LEVY SANTOS DA CRUZ', 'nome_guerra' => 'LEVY', 'posto' => 'Cel', 'quadro' => 'QOAV', 'esp' => 'NTE', 'setor' => 'DPC', 'divisao' => 'DPC', 'funcao' => 'Chefe da Divisão de Planejamento', 'categoria' => 'ineligivel', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2018-07-20', 'om' => '2018-07-20'],
    ['saram' => '3835405', 'cpf' => '111.111.111-04', 'nome' => 'THIAGO ANDRÉ LOURENÇO DE MELLO', 'nome_guerra' => 'THIAGO', 'posto' => 'Ten Cel', 'quadro' => 'QOINT', 'esp' => 'FSU', 'setor' => 'ACI', 'divisao' => 'ACI', 'funcao' => 'Chefe da ACI', 'categoria' => 'ineligivel', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2021-01-25', 'om' => '2021-01-25'],
    ['saram' => '3686744', 'cpf' => '111.111.111-05', 'nome' => 'LUÍS MAURO MOREIRA DE SÁ', 'nome_guerra' => 'LUIS MAURO', 'posto' => 'Maj', 'quadro' => 'QOENG', 'esp' => 'IES', 'setor' => 'AGOV', 'divisao' => 'AGOV', 'funcao' => 'Chefe da AGOV', 'categoria' => 'ineligivel', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2021-03-01', 'om' => '2021-03-01'],
    ['saram' => '3989224', 'cpf' => '111.111.111-06', 'nome' => 'MAGNO LEMOS GIROTTO', 'nome_guerra' => 'MAGNO', 'posto' => 'Maj', 'quadro' => 'QOENG', 'esp' => 'IES', 'setor' => 'APOG', 'divisao' => 'APOG', 'funcao' => 'Chefe da APOG', 'categoria' => 'ineligivel', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2021-04-12', 'om' => '2021-04-12'],
    ['saram' => '6161375', 'cpf' => '111.111.111-07', 'nome' => 'LUÍS HENRIQUE GONÇALVES MAIA E SILVA', 'nome_guerra' => 'MAIA', 'posto' => 'Maj', 'quadro' => 'QOENG', 'esp' => 'CIV', 'setor' => 'DL', 'divisao' => 'DL', 'funcao' => 'Chefe da Divisão de Logística', 'categoria' => 'ineligivel', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2020-08-15', 'om' => '2020-08-15'],
    ['saram' => '7492529', 'cpf' => '111.111.111-08', 'nome' => 'KAREN CAROLINE TORRES FORTUNATO', 'nome_guerra' => 'KAREN', 'posto' => '2º Ten', 'quadro' => 'QOCON', 'esp' => 'ADM', 'setor' => 'DAEF', 'divisao' => 'DA', 'funcao' => 'Chefe da Educação Física (DAEF)', 'categoria' => 'ineligivel', 'vinculo' => 'MILITAR_TEMPORARIO', 'comara' => '2022-01-10', 'om' => '2022-01-10'],

    // --- CATEGORIA: GRADUADO PADRÃO (SO, 1S, 2S, 3S) ---
    ['saram' => '2309165', 'cpf' => '222.222.222-01', 'nome' => 'RONALDO AUGUSTO DA SILVA COTA', 'nome_guerra' => 'COTA', 'posto' => 'SO', 'quadro' => 'QSS', 'esp' => 'SEM', 'setor' => 'SCS', 'divisao' => 'SCS', 'funcao' => 'Especialista em Estruturas Metálicas', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2010-03-01', 'om' => '2015-02-01', 'ind' => 0],
    ['saram' => '2995700', 'cpf' => '222.222.222-02', 'nome' => 'ARMANDO HENRIQUE LOPES CORRÊA', 'nome_guerra' => 'HENRIQUE', 'posto' => 'SO', 'quadro' => 'QSS', 'esp' => 'SML', 'setor' => 'DLMV', 'divisao' => 'DL', 'funcao' => 'Encarregado de Manutenção de Viaturas', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2012-08-10', 'om' => '2016-04-12', 'ind' => 1],
    ['saram' => '3178986', 'cpf' => '222.222.222-03', 'nome' => 'JOSÉ WELLINGTON DA SILVA', 'nome_guerra' => 'DA SILVA', 'posto' => 'SO', 'quadro' => 'QSS', 'esp' => 'SEL', 'setor' => 'SDC', 'divisao' => 'DPC', 'funcao' => 'Encarregado de Eletricidade', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2014-02-15', 'om' => '2017-01-10', 'ind' => 0],
    ['saram' => '2734206', 'cpf' => '222.222.222-04', 'nome' => 'LUIZ GUILHERME DOS SANTOS MORAES', 'nome_guerra' => 'GUILHERME', 'posto' => 'SO', 'quadro' => 'QSS', 'esp' => 'SAD', 'setor' => 'DAPM', 'divisao' => 'DA', 'funcao' => 'Auxiliar de Pessoal Militar', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2011-06-01', 'om' => '2014-05-15', 'ind' => 2],
    ['saram' => '3572390', 'cpf' => '222.222.222-05', 'nome' => 'GEILAN CHARLES RODRIGUES DA SILVA', 'nome_guerra' => 'GEILAN', 'posto' => 'SO', 'quadro' => 'QSS', 'esp' => 'SOB', 'setor' => 'SDSG', 'divisao' => 'DA', 'funcao' => 'Encarregado de Serviços Gerais', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2013-09-10', 'om' => '2018-03-01', 'ind' => 0],
    ['saram' => '3606406', 'cpf' => '222.222.222-06', 'nome' => 'JOÃO BOSCO GOMES SALGADO', 'nome_guerra' => 'BOSCO', 'posto' => 'SO', 'quadro' => 'QSS', 'esp' => 'SEL', 'setor' => 'DLBA', 'divisao' => 'DL', 'funcao' => 'Eletricista de Obras', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2015-01-20', 'om' => '2019-06-01', 'ind' => 0],
    ['saram' => '3251659', 'cpf' => '222.222.222-07', 'nome' => 'ALEXANDRE SCHOLZ', 'nome_guerra' => 'SCHOLZ', 'posto' => '1S', 'quadro' => 'QSS', 'esp' => 'BSP', 'setor' => 'DLAL', 'divisao' => 'DL', 'funcao' => 'Almoxarife Técnico', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2016-03-15', 'om' => '2018-09-01', 'ind' => 0],
    ['saram' => '3332306', 'cpf' => '222.222.222-08', 'nome' => 'CLEBERSON DE OLIVEIRA SALOMÃO', 'nome_guerra' => 'SALOMAO', 'posto' => '1S', 'quadro' => 'QSS', 'esp' => 'SML', 'setor' => 'SAQ', 'divisao' => 'DA', 'funcao' => 'Inspetor de Manutenção', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2017-05-10', 'om' => '2019-01-20', 'ind' => 0],
    ['saram' => '4230531', 'cpf' => '222.222.222-09', 'nome' => 'JEFERSON SOUSA DA CUNHA', 'nome_guerra' => 'JEFERSON', 'posto' => '1S', 'quadro' => 'QSS', 'esp' => 'SEL', 'setor' => 'SDPJ', 'divisao' => 'DE', 'funcao' => 'Projetista Elétrico', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2018-02-01', 'om' => '2020-03-15', 'ind' => 1],
    ['saram' => '6324843', 'cpf' => '222.222.222-10', 'nome' => 'LUCAS FORTUNATO', 'nome_guerra' => 'FORTUNATO', 'posto' => '2S', 'quadro' => 'QSS', 'esp' => 'SIN', 'setor' => 'DPTI', 'divisao' => 'DA', 'funcao' => 'Administrador de Redes', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2019-04-10', 'om' => '2021-02-01', 'ind' => 0],
    ['saram' => '6384544', 'cpf' => '222.222.222-11', 'nome' => 'DIEGO RODRIGUES DA SILVA', 'nome_guerra' => 'DIEGO SILVA', 'posto' => '2S', 'quadro' => 'QSS', 'esp' => 'STP', 'setor' => 'AJUR', 'divisao' => 'AJUR', 'funcao' => 'Auxiliar Jurídico', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2020-01-15', 'om' => '2021-06-10', 'ind' => 0],
    ['saram' => '6656471', 'cpf' => '222.222.222-12', 'nome' => 'HELLYSON DA ROSA SOARES', 'nome_guerra' => 'HELLYSON', 'posto' => '3S', 'quadro' => 'QSCON', 'esp' => 'TMT', 'setor' => 'DLTR', 'divisao' => 'DL', 'funcao' => 'Condutor Fluvial e Terrestre', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_TEMPORARIO', 'comara' => '2021-08-01', 'om' => '2022-01-15', 'ind' => 0],
    ['saram' => '6998550', 'cpf' => '222.222.222-13', 'nome' => 'GABRIEL BEZERRA SANTOS', 'nome_guerra' => 'SANTOS', 'posto' => '3S', 'quadro' => 'QSS', 'esp' => 'SPV', 'setor' => 'APOG', 'divisao' => 'APOG', 'funcao' => 'Apoio Orçamentário', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2021-09-10', 'om' => '2022-02-01', 'ind' => 0],
    ['saram' => '7000057', 'cpf' => '222.222.222-14', 'nome' => 'LETICIA BERNARDES VIEIRA', 'nome_guerra' => 'BERNARDES', 'posto' => '3S', 'quadro' => 'QSS', 'esp' => 'SIN', 'setor' => 'DPTI', 'divisao' => 'DA', 'funcao' => 'Suporte ao Usuário', 'categoria' => 'graduado', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2022-02-15', 'om' => '2022-05-10', 'ind' => 0],

    // --- CATEGORIA: PRAÇA PADRÃO (CB, S1, S2) ---
    ['saram' => '7497920', 'cpf' => '333.333.333-01', 'nome' => 'HERALDO WAGNER CONCEIÇÃO MONTEIRO', 'nome_guerra' => 'MONTEIRO', 'posto' => 'Cb', 'quadro' => 'QCBCON', 'esp' => 'TMC', 'setor' => 'DLAL', 'divisao' => 'DL', 'funcao' => 'Operador de Máquinas Pesadas', 'categoria' => 'praca', 'vinculo' => 'MILITAR_TEMPORARIO', 'comara' => '2021-03-01', 'om' => '2021-03-01', 'ind' => 0],
    ['saram' => '6988261', 'cpf' => '333.333.333-02', 'nome' => 'LEONARDO PINHEIRO DE ALMEIDA', 'nome_guerra' => 'LEONARDO', 'posto' => 'Cb', 'quadro' => 'QCBCON', 'esp' => 'TEE', 'setor' => 'DEOF', 'divisao' => 'DE', 'funcao' => 'Técnico de Instalações Aeroportuárias', 'categoria' => 'praca', 'vinculo' => 'MILITAR_TEMPORARIO', 'comara' => '2020-04-10', 'om' => '2020-04-10', 'ind' => 0],
    ['saram' => '7498152', 'cpf' => '333.333.333-03', 'nome' => 'MADSSON DIEGO MARTINS FURTADO', 'nome_guerra' => 'MADSSON', 'posto' => 'Cb', 'quadro' => 'QCBCON', 'esp' => 'TMP', 'setor' => 'DLTR', 'divisao' => 'DL', 'funcao' => 'Motorista de Viaturas Operacionais', 'categoria' => 'praca', 'vinculo' => 'MILITAR_TEMPORARIO', 'comara' => '2021-05-15', 'om' => '2021-05-15', 'ind' => 1],
    ['saram' => '7498160', 'cpf' => '333.333.333-04', 'nome' => 'ÍTALO BRUNO BARBOSA PENHA', 'nome_guerra' => 'BRUNO', 'posto' => 'Cb', 'quadro' => 'QCBCON', 'esp' => 'TMC', 'setor' => 'DLMV', 'divisao' => 'DL', 'funcao' => 'Mecânico de Motores Diesel', 'categoria' => 'praca', 'vinculo' => 'MILITAR_TEMPORARIO', 'comara' => '2021-06-01', 'om' => '2021-06-01', 'ind' => 0],
    ['saram' => '6863183', 'cpf' => '333.333.333-05', 'nome' => 'LEANDRO MARLLON LIMA OLIVEIRA MARTINS', 'nome_guerra' => 'MARLLON', 'posto' => 'Cb', 'quadro' => 'QCBCON', 'esp' => 'TCP', 'setor' => 'DEOF', 'divisao' => 'DE', 'funcao' => 'Carpinteiro de Formas', 'categoria' => 'praca', 'vinculo' => 'MILITAR_TEMPORARIO', 'comara' => '2019-08-20', 'om' => '2019-08-20', 'ind' => 0],
    ['saram' => '6467580', 'cpf' => '333.333.333-06', 'nome' => 'LUCAS COSTA RIBEIRO', 'nome_guerra' => 'RIBEIRO', 'posto' => 'Cb', 'quadro' => 'QCBCON', 'esp' => 'TEE', 'setor' => 'SDPJ', 'divisao' => 'DE', 'funcao' => 'Desenhista Cadista', 'categoria' => 'praca', 'vinculo' => 'MILITAR_TEMPORARIO', 'comara' => '2019-02-10', 'om' => '2019-02-10', 'ind' => 0],
    ['saram' => '7360606', 'cpf' => '333.333.333-07', 'nome' => 'GUILHERME FERREIRA VAZ', 'nome_guerra' => 'VAZ', 'posto' => 'S1', 'quadro' => 'QSD', 'esp' => 'BLM', 'setor' => 'DLMV', 'divisao' => 'DL', 'funcao' => 'Apoio à Manutenção', 'categoria' => 'praca', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2022-03-01', 'om' => '2022-03-01', 'ind' => 0],
    ['saram' => '7361025', 'cpf' => '333.333.333-08', 'nome' => 'RAFAEL CORRÊA LIMA SANTOS', 'nome_guerra' => 'LIMA SANTOS', 'posto' => 'S1', 'quadro' => 'QSD', 'esp' => 'SAD', 'setor' => 'DAPC', 'divisao' => 'DPC', 'funcao' => 'Apoio Administrativo de Planejamento', 'categoria' => 'praca', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2022-03-15', 'om' => '2022-03-15', 'ind' => 0],
    ['saram' => '7567693', 'cpf' => '333.333.333-09', 'nome' => 'LUIS FERNANDO LIMA REIS', 'nome_guerra' => 'FERNANDO', 'posto' => 'S2', 'quadro' => 'QSD', 'esp' => 'NE', 'setor' => 'DECO-BE', 'divisao' => 'DE', 'funcao' => 'Auxiliar de Engenharia de Pista', 'categoria' => 'praca', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2023-03-01', 'om' => '2023-03-01', 'ind' => 0],
    ['saram' => '7618441', 'cpf' => '333.333.333-10', 'nome' => 'ISAC BARBOSA CARDOSO', 'nome_guerra' => 'ISAC', 'posto' => 'S2', 'quadro' => 'QSD', 'esp' => 'NE', 'setor' => 'DECO-BE', 'divisao' => 'DE', 'funcao' => 'Operador de Britador', 'categoria' => 'praca', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2023-03-10', 'om' => '2023-03-10', 'ind' => 0],
    ['saram' => '7619065', 'cpf' => '333.333.333-11', 'nome' => 'JOÃO VITOR DA SILVA ANDRADE', 'nome_guerra' => 'ANDRADE', 'posto' => 'S2', 'quadro' => 'QSD', 'esp' => 'NE', 'setor' => 'DLAL', 'divisao' => 'DL', 'funcao' => 'Auxiliar de Depósito', 'categoria' => 'praca', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2023-03-15', 'om' => '2023-03-15', 'ind' => 0],
    ['saram' => '7618069', 'cpf' => '333.333.333-12', 'nome' => 'ISAQUE RODRIGUES FERREIRA', 'nome_guerra' => 'FERREIRA', 'posto' => 'S2', 'quadro' => 'QSD', 'esp' => 'NE', 'setor' => 'DAPP', 'divisao' => 'DA', 'funcao' => 'Auxiliar de Protocolo', 'categoria' => 'praca', 'vinculo' => 'MILITAR_CARREIRA', 'comara' => '2023-03-20', 'om' => '2023-03-20', 'ind' => 0],

    // --- CATEGORIA: SERVIDOR CIVIL PERMANENTE PADRÃO (SPPF) ---
    ['saram' => '4568362', 'cpf' => '444.444.444-01', 'nome' => 'VALDIR SOUSA DOS SANTOS', 'nome_guerra' => 'VALDIR', 'posto' => 'CV', 'quadro' => 'PC-PERM', 'esp' => 'CIV', 'setor' => 'STS', 'divisao' => 'DA', 'funcao' => 'Técnico em Edificações Permanente', 'categoria' => 'sppf', 'vinculo' => 'CIVIL_PERMANENTE', 'comara' => '1998-05-12', 'om' => '1998-05-12', 'ind' => 0],
    ['saram' => '4686632', 'cpf' => '444.444.444-02', 'nome' => 'CLAUDIO JOSE DE LIMA', 'nome_guerra' => 'CLAUDIO', 'posto' => 'CV', 'quadro' => 'PC-PERM', 'esp' => 'CIV', 'setor' => 'DLMV', 'divisao' => 'DL', 'funcao' => 'Mecânico de Equipamentos Pesados', 'categoria' => 'sppf', 'vinculo' => 'CIVIL_PERMANENTE', 'comara' => '1996-03-15', 'om' => '1996-03-15', 'ind' => 1],
    ['saram' => '4640659', 'cpf' => '444.444.444-03', 'nome' => 'ORIVALDO PAIXAO', 'nome_guerra' => 'PAIXÃO', 'posto' => 'CV', 'quadro' => 'PC-PERM', 'esp' => 'CIV', 'setor' => 'DAHL', 'divisao' => 'DA', 'funcao' => 'Encarregado de Manutenção Predial', 'categoria' => 'sppf', 'vinculo' => 'CIVIL_PERMANENTE', 'comara' => '1995-09-01', 'om' => '1995-09-01', 'ind' => 0],
    ['saram' => '4962150', 'cpf' => '444.444.444-04', 'nome' => 'PRISCILA FERNANDA DA SILVA NASCIMENTO', 'nome_guerra' => 'PRISCILA FERNANDA', 'posto' => 'CV', 'quadro' => 'PC-PERM', 'esp' => 'CIV', 'setor' => 'DAHL', 'divisao' => 'DA', 'funcao' => 'Agente Administrativo', 'categoria' => 'sppf', 'vinculo' => 'CIVIL_PERMANENTE', 'comara' => '2005-02-10', 'om' => '2005-02-10', 'ind' => 0],
    ['saram' => '4665775', 'cpf' => '444.444.444-05', 'nome' => 'VENUTIANO TELES CAMPOS', 'nome_guerra' => 'VENUTIANO', 'posto' => 'CV', 'quadro' => 'PC-PERM', 'esp' => 'CIV', 'setor' => 'SDM', 'divisao' => 'DL', 'funcao' => 'Artífice de Construção', 'categoria' => 'sppf', 'vinculo' => 'CIVIL_PERMANENTE', 'comara' => '1997-11-20', 'om' => '1997-11-20', 'ind' => 0],
    ['saram' => '4746201', 'cpf' => '444.444.444-06', 'nome' => 'ALBERTO DE OLIVEIRA MEIRELES', 'nome_guerra' => 'ALBERTO MEIRELES', 'posto' => 'CV', 'quadro' => 'PC-PERM', 'esp' => 'CIV', 'setor' => 'STS', 'divisao' => 'DA', 'funcao' => 'Topógrafo', 'categoria' => 'sppf', 'vinculo' => 'CIVIL_PERMANENTE', 'comara' => '1999-07-15', 'om' => '1999-07-15', 'ind' => 0],
    ['saram' => '4679911', 'cpf' => '444.444.444-07', 'nome' => 'REGINA CELIA ALVES ESTACIO', 'nome_guerra' => 'REGINA', 'posto' => 'CV', 'quadro' => 'PC-PERM', 'esp' => 'CIV', 'setor' => 'DARE', 'divisao' => 'DA', 'funcao' => 'Agente de Recursos Humanos', 'categoria' => 'sppf', 'vinculo' => 'CIVIL_PERMANENTE', 'comara' => '1998-04-10', 'om' => '1998-04-10', 'ind' => 0],
    ['saram' => '4661842', 'cpf' => '444.444.444-08', 'nome' => 'MOYSES CALCADA DA SILVA', 'nome_guerra' => 'MOYSES', 'posto' => 'CV', 'quadro' => 'PC-PERM', 'esp' => 'CIV', 'setor' => 'SPROT', 'divisao' => 'DA', 'funcao' => 'Encarregado de Protocolo Geral', 'categoria' => 'sppf', 'vinculo' => 'CIVIL_PERMANENTE', 'comara' => '1994-08-01', 'om' => '1994-08-01', 'ind' => 0],

    // --- CATEGORIA: SERVIDOR CIVIL TEMPORÁRIO PADRÃO (SPTF - Lei nº 8.745/1993) ---
    ['saram' => '4952464', 'cpf' => '555.555.555-01', 'nome' => 'RENATTA CUNTO DO NASCIMENTO', 'nome_guerra' => 'RENATTA', 'posto' => 'CV', 'quadro' => 'PC-TEMP', 'esp' => 'CIV', 'setor' => 'DAPC', 'divisao' => 'DPC', 'funcao' => 'Analista de Planejamento Temporário', 'categoria' => 'sptf', 'vinculo' => 'CIVIL_TEMPORARIO', 'comara' => '2021-06-01', 'om' => '2021-06-01', 'ind' => 0],
    ['saram' => '8539359', 'cpf' => '555.555.555-02', 'nome' => 'ANNY GABRIELLE COLARES DE CASTRO', 'nome_guerra' => 'ANNY', 'posto' => 'CV', 'quadro' => 'PC-TEMP', 'esp' => 'CIV', 'setor' => 'DEOF', 'divisao' => 'DE', 'funcao' => 'Engenheira Civil Temporária', 'categoria' => 'sptf', 'vinculo' => 'CIVIL_TEMPORARIO', 'comara' => '2022-01-15', 'om' => '2022-01-15', 'ind' => 0],
    ['saram' => '8539707', 'cpf' => '555.555.555-03', 'nome' => 'CAYO FERNANDO MORAES OLIVEIRA', 'nome_guerra' => 'CAYO FERNANDO', 'posto' => 'CV', 'quadro' => 'PC-TEMP', 'esp' => 'CIV', 'setor' => 'DPTI', 'divisao' => 'DA', 'funcao' => 'Programador Web Temporário', 'categoria' => 'sptf', 'vinculo' => 'CIVIL_TEMPORARIO', 'comara' => '2022-03-10', 'om' => '2022-03-10', 'ind' => 0],
    ['saram' => '8539600', 'cpf' => '555.555.555-04', 'nome' => 'ANA PAULA DE OLIVEIRA BAENA', 'nome_guerra' => 'ANA PAULA', 'posto' => 'CV', 'quadro' => 'PC-TEMP', 'esp' => 'CIV', 'setor' => 'APOG', 'divisao' => 'APOG', 'funcao' => 'Assistente de Gestão Temporária', 'categoria' => 'sptf', 'vinculo' => 'CIVIL_TEMPORARIO', 'comara' => '2022-05-01', 'om' => '2022-05-01', 'ind' => 0],
    ['saram' => '8539740', 'cpf' => '555.555.555-05', 'nome' => 'NELIO JUNIOR FARIAS', 'nome_guerra' => 'NELIO', 'posto' => 'CV', 'quadro' => 'PC-TEMP', 'esp' => 'CIV', 'setor' => 'DLMV', 'divisao' => 'DL', 'funcao' => 'Técnico Mecânico Temporário', 'categoria' => 'sptf', 'vinculo' => 'CIVIL_TEMPORARIO', 'comara' => '2021-11-20', 'om' => '2021-11-20', 'ind' => 0],
    ['saram' => '4956010', 'cpf' => '555.555.555-06', 'nome' => 'JEAN PAMPLONA GARRIDO', 'nome_guerra' => 'PAMPLONA', 'posto' => 'CV', 'quadro' => 'PC-TEMP', 'esp' => 'CIV', 'setor' => 'SAQ', 'divisao' => 'DA', 'funcao' => 'Comprador Técnico Temporário', 'categoria' => 'sptf', 'vinculo' => 'CIVIL_TEMPORARIO', 'comara' => '2021-08-15', 'om' => '2021-08-15', 'ind' => 0],
    ['saram' => '8538760', 'cpf' => '555.555.555-07', 'nome' => 'GLEISON LIMA SANTOS', 'nome_guerra' => 'GLEISON', 'posto' => 'CV', 'quadro' => 'PC-TEMP', 'esp' => 'CIV', 'setor' => 'SDSG', 'divisao' => 'DA', 'funcao' => 'Técnico Eletricista Temporário', 'categoria' => 'sptf', 'vinculo' => 'CIVIL_TEMPORARIO', 'comara' => '2022-07-01', 'om' => '2022-07-01', 'ind' => 0],
    ['saram' => '8539642', 'cpf' => '555.555.555-08', 'nome' => 'LAYSE AMANDA MARQUES PAIVA', 'nome_guerra' => 'AMANDA', 'posto' => 'CV', 'quadro' => 'PC-TEMP', 'esp' => 'CIV', 'setor' => 'ACI', 'divisao' => 'ACI', 'funcao' => 'Auxiliar de Auditoria Temporária', 'categoria' => 'sptf', 'vinculo' => 'CIVIL_TEMPORARIO', 'comara' => '2022-08-10', 'om' => '2022-08-10', 'ind' => 0]
];

$stmtInsert = $pdo->prepare('
    INSERT INTO `efetivo` (
        `saram`, `cpf`, `cpf_hash`, `nome`, `nome_guerra`, `posto`, `quadro`, `especialidade`,
        `setor`, `divisao_sigla`, `funcao`, `categoria`, `tipo_vinculo`,
        `data_admissao_comara`, `data_vinculo_om`, `indicacoes_anteriores_qtd`, `ativo`
    ) VALUES (
        :saram, :cpf, :cpf_hash, :nome, :nome_guerra, :posto, :quadro, :esp,
        :setor, :divisao, :funcao, :categoria, :tipo_vinculo,
        :comara, :om, :ind, 1
    ) ON DUPLICATE KEY UPDATE
        `nome` = VALUES(`nome`),
        `categoria` = VALUES(`categoria`),
        `funcao` = VALUES(`funcao`)
');

$total = 0;
foreach ($efetivo as $m) {
    $cleanCpf = preg_replace('/[^0-9]/', '', $m['cpf']);
    $cpfHash = hash('sha256', $cleanCpf);

    $stmtInsert->execute([
        ':saram'        => $m['saram'],
        ':cpf'          => $m['cpf'],
        ':cpf_hash'     => $cpfHash,
        ':nome'         => $m['nome'],
        ':nome_guerra'  => $m['nome_guerra'],
        ':posto'        => $m['posto'],
        ':quadro'       => $m['quadro'],
        ':esp'          => $m['esp'],
        ':setor'        => $m['setor'],
        ':divisao'      => $m['divisao'],
        ':funcao'       => $m['funcao'],
        ':categoria'    => $m['categoria'],
        ':tipo_vinculo' => $m['vinculo'],
        ':comara'       => $m['comara'],
        ':om'           => $m['om'],
        ':ind'          => $m['ind'] ?? 0
    ]);
    $total++;
}

// Vincular Chefes Diretos
// Oficial Maia (DL) chefia o pessoal da DL (Henrique, Scholz, Hellyson, Monteiro, Madsson, Bruno, etc.)
$chefeDl = $pdo->query("SELECT id FROM efetivo WHERE nome_guerra = 'MAIA' LIMIT 1")->fetchColumn();
if ($chefeDl) {
    $pdo->prepare("UPDATE efetivo SET chefe_direto_id = :chefe_id WHERE divisao_sigla = 'DL' AND id != :proprio_id")
        ->execute([':chefe_id' => $chefeDl, ':proprio_id' => $chefeDl]);
}

// Oficial Leitão (DA) chefia o pessoal da DA
$chefeDa = $pdo->query("SELECT id FROM efetivo WHERE nome_guerra = 'LEITÃO' LIMIT 1")->fetchColumn();
if ($chefeDa) {
    $pdo->prepare("UPDATE efetivo SET chefe_direto_id = :chefe_id WHERE divisao_sigla = 'DA' AND id != :proprio_id")
        ->execute([':chefe_id' => $chefeDa, ':proprio_id' => $chefeDa]);
}

// Criar usuários com perfil ELEITOR para cada militar e civil cadastrado
$stmtUsers = $pdo->prepare('
    INSERT INTO `usuarios` (`login`, `senha_hash`, `efetivo_id`, `perfil`, `ativo`)
    VALUES (:login, :senha, :efetivo_id, "ELEITOR", 1)
    ON DUPLICATE KEY UPDATE `efetivo_id` = VALUES(`efetivo_id`)
');

$efetivoRows = $pdo->query('SELECT id, saram, nome_guerra FROM efetivo WHERE saram IS NOT NULL')->fetchAll();
$userHash = '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2'; // padrao@2026

foreach ($efetivoRows as $er) {
    $nomeLimpo = function_exists('iconv') ? (iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $er['nome_guerra']) ?: $er['nome_guerra']) : $er['nome_guerra'];
    $login = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nomeLimpo)) . '.' . $er['saram'];

    // Checa se usuário já existe
    $exists = $pdo->prepare('SELECT id FROM usuarios WHERE login = :l OR efetivo_id = :ef');
    $exists->execute([':l' => $login, ':ef' => $er['id']]);
    if (!$exists->fetchColumn()) {
        $stmtUsers->execute([
            ':login'      => $login,
            ':senha'      => $userHash,
            ':efetivo_id' => $er['id']
        ]);
    }
}

echo "[SUCESSO] Total de $total registros de efetivo cadastrados com sucesso!\n";
echo "=== MIGRAÇÃO CONCLUÍDA ===\n";
