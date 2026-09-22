<?php
declare(strict_types=1);

/**
 * Script de Carga e Cadastro Integral dos 443 Militares e Civis da COMARA
 * Extraídos do Sistema Oficial de Gerenciamento de Efetivo da COMARA (Joomla)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== INICIANDO CADASTRO INTEGRAL DOS 443 REGISTROS DO EFETIVO COMARA ===\n";

$jsonPath = __DIR__ . '/../scratch_todos_militares.json';
if (!file_exists($jsonPath)) {
    die("ERRO: scratch_todos_militares.json não localizado.\n");
}

$raw = json_decode(file_get_contents($jsonPath), true);
echo "Registros carregados do JSON: " . count($raw) . "\n";

// Adicionar registro 197 (Delville Souza Figueiredo) que ficou na divisão de página
$has197 = false;
foreach ($raw as $r) {
    if ($r['saram'] === '7592122' || str_contains($r['nome'], 'DELVILLE')) {
        $has197 = true;
        break;
    }
}

if (!$has197) {
    $raw[] = [
        'page'          => 19,
        'nome'          => 'DELVILLE SOUZA FIGUEIREDO',
        'nome_guerra'   => 'DELVILLE',
        'saram'         => '7592122',
        'posto'         => 'Cb',
        'quadro'        => 'QCBCON',
        'especialidade' => 'SAD',
        'setor'         => 'SDPJ',
        'telefone'      => '',
        'categoria'     => 'praca',
        'tipo_vinculo'  => 'MILITAR_CARREIRA',
        'joomla_id'     => '765'
    ];
}

echo "Total consolidado para processamento: " . count($raw) . " registros.\n";

// Helper para gerar CPF válido determinístico por SARAM/ID
function gerarCpfValido(int $seed): string {
    $s = sprintf('%08d', abs($seed) % 100000000);
    $n = '1' . substr($s, 0, 8); // 9 dígitos
    // D1
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += (int)$n[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $d1 = ($resto < 2) ? 0 : 11 - $resto;
    $n .= $d1;

    // D2
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += (int)$n[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $d2 = ($resto < 2) ? 0 : 11 - $resto;
    $n .= $d2;

    return sprintf('%s.%s.%s-%s', substr($n, 0, 3), substr($n, 3, 3), substr($n, 6, 3), substr($n, 9, 2));
}

// Helper para extrair sigla da Divisão a partir do setor
function extrairDivisaoSigla(?string $setor): string {
    if (!$setor) return 'DA';
    $s = strtoupper($setor);

    if (str_contains($s, 'DPC') || str_contains($s, 'APOG') || str_contains($s, 'DAP')) return 'DPC';
    if (str_contains($s, 'ACI')) return 'ACI';
    if (str_contains($s, 'AGOV')) return 'AGOV';
    if (str_contains($s, 'AINT')) return 'AINT';
    if (str_contains($s, 'SCS')) return 'SCS';
    if (str_contains($s, 'AJUR')) return 'AJUR';
    if (str_contains($s, 'SIJ')) return 'SIJ';
    if (str_contains($s, 'DL') || str_contains($s, 'SDP') || str_contains($s, 'SLOG')) return 'DL';
    if (str_contains($s, 'DE') || str_contains($s, 'DED') || str_contains($s, 'DEP') || str_contains($s, 'DECO')) return 'DE';
    if (str_contains($s, 'DS') || str_contains($s, 'SDM') || str_contains($s, 'SDS') || str_contains($s, 'STS')) return 'DS';
    if (str_contains($s, 'DA') || str_contains($s, 'DAEF') || str_contains($s, 'DAPM') || str_contains($s, 'DACD')) return 'DA';
    if (str_contains($s, 'VP') || str_contains($s, 'SEC-VP')) return 'DPC';

    return 'DA';
}

$pdo = Database::getConnection();

// Prepara inserção em efetivo
$stmtIns = $pdo->prepare('
    INSERT INTO `efetivo` (
        `saram`, `cpf`, `cpf_hash`, `nome`, `nome_guerra`, `posto`,
        `quadro`, `especialidade`, `setor`, `divisao_sigla`, `funcao`,
        `categoria`, `tipo_vinculo`, `data_admissao_comara`, `data_vinculo_om`,
        `indicacoes_anteriores_qtd`, `telefone`, `ativo`
    ) VALUES (
        :saram, :cpf, :cpf_hash, :nome, :nome_guerra, :posto,
        :quadro, :esp, :setor, :divisao, :funcao,
        :categoria, :vinculo, :dt_comara, :dt_om,
        :ind_ant, :tel, 1
    ) ON DUPLICATE KEY UPDATE
        `nome`                      = VALUES(`nome`),
        `nome_guerra`               = VALUES(`nome_guerra`),
        `posto`                     = VALUES(`posto`),
        `quadro`                    = VALUES(`quadro`),
        `especialidade`             = VALUES(`especialidade`),
        `setor`                     = VALUES(`setor`),
        `divisao_sigla`             = VALUES(`divisao_sigla`),
        `categoria`                 = VALUES(`categoria`),
        `tipo_vinculo`              = VALUES(`tipo_vinculo`),
        `telefone`                  = VALUES(`telefone`),
        `ativo`                     = 1
');

// Senha padrão hash bcrypt para todos os eleitores (padrao@2026)
$senhaHashPadrao = '$2y$10$gPrMeW./CLdOAcOJugqUm.gqSNN67btnx.cMdacEcXcxayLbDWzL2';

$stmtUser = $pdo->prepare('
    INSERT INTO `usuarios` (`login`, `senha_hash`, `efetivo_id`, `perfil`, `ativo`)
    VALUES (:login, :senha, :ef_id, :perfil, 1)
    ON DUPLICATE KEY UPDATE
        `efetivo_id` = VALUES(`efetivo_id`),
        `ativo`      = 1
');

$totalInseridos = 0;
$csvData = [
    ["SARAM", "CPF", "NOME", "NOME_GUERRA", "POSTO", "QUADRO", "ESPECIALIDADE", "SETOR", "DIVISAO", "CATEGORIA", "VINCULO", "TELEFONE", "JOOMLA_ID"]
];

foreach ($raw as $idx => $r) {
    $saram = trim($r['saram'] ?? '');
    if (!$saram) {
        $saram = sprintf('9%06d', $idx + 1);
    }

    $nome = trim(preg_replace('/\s+/', ' ', $r['nome'] ?? ''));
    $nomeGuerra = trim($r['nome_guerra'] ?? '');
    if (!$nomeGuerra) {
        $partes = explode(' ', $nome);
        $nomeGuerra = end($partes);
    }

    // Normalização de Posto
    $posto = trim($r['posto'] ?? '');
    $quadro = trim($r['quadro'] ?? '');
    if ($posto === '' && $quadro === 'QSS') {
        $posto = 'SO';
    } elseif ($posto === '$2' || $posto === 'S 2') {
        $posto = 'S2';
    } elseif (str_contains($posto, '1') && str_contains(strtoupper($posto), 'TEN')) {
        $posto = '1º Ten';
    } elseif (str_contains($posto, '2') && str_contains(strtoupper($posto), 'TEN')) {
        $posto = '2º Ten';
    }

    // Categorização oficial
    $cat = $r['categoria'] ?? 'ineligivel';
    $vinculo = $r['tipo_vinculo'] ?? 'MILITAR_CARREIRA';

    if (in_array($posto, ['Cel', 'Ten Cel', 'Maj', 'Cap', '1º Ten', '2º Ten'], true)) {
        $cat = CAT_INELIGIVEL;
        $vinculo = 'MILITAR_CARREIRA';
    } elseif (in_array($posto, ['SO', '1S', '2S', '3S'], true)) {
        $cat = CAT_GRADUADO;
        $vinculo = 'MILITAR_CARREIRA';
    } elseif (in_array($posto, ['Cb', 'S1', 'S2'], true)) {
        $cat = CAT_PRACA;
        $vinculo = 'MILITAR_CARREIRA';
    } elseif ($posto === 'CV') {
        if (str_starts_with($saram, '85')) {
            $cat = CAT_SPTF;
            $vinculo = 'CIVIL_TEMPORARIO';
        } else {
            $cat = CAT_SPPF;
            $vinculo = 'CIVIL_PERMANENTE';
        }
    }

    $setor = trim($r['setor'] ?? '');
    $divisao = extrairDivisaoSigla($setor);
    $esp = trim($r['especialidade'] ?? '');
    $tel = trim($r['telefone'] ?? '');
    $joomlaId = (int)($r['joomla_id'] ?? ($idx + 1));

    $cpf = gerarCpfValido((int)$saram ?: $joomlaId);
    $cpfHash = hash('sha256', preg_replace('/\D/', '', $cpf));

    // Datas simuladas realistas de antiguidade
    $anoBase = ($cat === CAT_SPTF) ? 2022 : (2015 + ($idx % 8));
    $mes = 1 + ($idx % 12);
    $dia = 1 + ($idx % 28);
    $dtComara = sprintf('%04d-%02d-%02d', $anoBase, $mes, $dia);
    $dtOm = $dtComara;

    $stmtIns->execute([
        ':saram'      => $saram,
        ':cpf'        => $cpf,
        ':cpf_hash'   => $cpfHash,
        ':nome'       => $nome,
        ':nome_guerra'=> $nomeGuerra,
        ':posto'      => $posto,
        ':quadro'     => $quadro,
        ':esp'        => $esp,
        ':setor'      => $setor,
        ':divisao'    => $divisao,
        ':funcao'     => $r['funcao'] ?? ($posto . ' - ' . $setor),
        ':categoria'  => $cat,
        ':vinculo'    => $vinculo,
        ':dt_comara'  => $dtComara,
        ':dt_om'      => $dtOm,
        ':ind_ant'    => ($idx % 7 === 0) ? 1 : 0, // Alguns já com indicação anterior para testar desempate
        ':tel'        => $tel
    ]);

    // Obtém ID inserido
    $stmtFind = $pdo->prepare('SELECT id FROM efetivo WHERE saram = :s LIMIT 1');
    $stmtFind->execute([':s' => $saram]);
    $efId = (int)$stmtFind->fetchColumn();

    // Cria conta de eleitor correspondente
    $stmtUser->execute([
        ':login'  => $saram,
        ':senha'  => $senhaHashPadrao,
        ':ef_id'  => $efId,
        ':perfil' => 'ELEITOR'
    ]);

    $csvData[] = [
        $saram, $cpf, $nome, $nomeGuerra, $posto, $quadro, $esp, $setor, $divisao, $cat, $vinculo, $tel, (string)$joomlaId
    ];

    $totalInseridos++;
}

echo "[OK] {$totalInseridos} militares e servidores civis cadastrados com sucesso!\n";

// 2. VINCULAÇÃO DAS CHEFIAS DE DIVISÃO E ASSESSORIA
echo "\n--- Configurando Chefias Regimentais Oficiais ---\n";

$chefesDivisao = [
    'DPC'  => '3257347', // Cel Levy
    'DA'   => '3411982', // Cel Leitão
    'DL'   => '6161375', // Maj Maia
    'DE'   => '3686744', // Maj Luís Mauro
    'DS'   => '4452976', // Maj Gustavo
    'ACI'  => '3835405', // Ten Cel Thiago
    'AGOV' => '3686744', // Maj Luís Mauro
    'APOG' => '3989224', // Maj Magno
    'AINT' => '3257347', // Cel Levy
    'SCS'  => '2309165', // SO Cota
    'AJUR' => '7385145', // 1º Ten Vivianne
    'SIJ'  => '6018165'  // 1S Pacheco
];

foreach ($chefesDivisao as $sigla => $saramChefe) {
    $stmtC = $pdo->prepare('SELECT id FROM efetivo WHERE saram = :s LIMIT 1');
    $stmtC->execute([':s' => $saramChefe]);
    $cid = $stmtC->fetchColumn();
    if ($cid) {
        $cid = (int)$cid;
        // Atualiza tabela de divisões
        $pdo->prepare('UPDATE divisoes_assessorias SET chefe_usuario_id = (SELECT id FROM usuarios WHERE efetivo_id = :cid LIMIT 1) WHERE sigla = :sigla')
            ->execute([':cid' => $cid, ':sigla' => $sigla]);

        // Atualiza perfil do usuário para CHEFE_DIV_ASSESS
        $pdo->prepare('UPDATE usuarios SET perfil = "CHEFE_DIV_ASSESS", orgao_chefe_sigla = :sigla WHERE efetivo_id = :cid')
            ->execute([':sigla' => $sigla, ':cid' => $cid]);

        // Vincula todos os subordinados dessa divisão a este chefe
        $pdo->prepare('UPDATE efetivo SET chefe_direto_id = :chefe WHERE divisao_sigla = :sigla AND id != :chefe')
            ->execute([':chefe' => $cid, ':sigla' => $sigla]);

        echo "  [CHEFE] Divisão/Assessoria {$sigla} vinculada ao SARAM {$saramChefe} (ID Efetivo: {$cid})\n";
    }
}

// 3. ATRIBUIÇÃO DA SEÇÃO DE EDUCAÇÃO FÍSICA (DAEF / TACF)
$saramEdFisica = '7492529'; // 2º Ten Karen
$stmtEd = $pdo->prepare('SELECT id FROM efetivo WHERE saram = :s LIMIT 1');
$stmtEd->execute([':s' => $saramEdFisica]);
$edId = $stmtEd->fetchColumn();
if ($edId) {
    $pdo->prepare('UPDATE usuarios SET perfil = "ED_FISICA", orgao_chefe_sigla = "DA" WHERE efetivo_id = :id')
        ->execute([':id' => (int)$edId]);
    echo "  [TACF] Perfil ED_FISICA atribuído à Ten Karen (SARAM {$saramEdFisica})\n";
}

// 4. ATRIBUIÇÃO DA DIREÇÃO SUPERIOR (Oficiais Superiores)
$saramsOficiaisSuperiores = ['3148637', '3411982', '3257347', '3835405', '3686744', '3989224', '6161375'];
foreach ($saramsOficiaisSuperiores as $sOs) {
    $stmtOs = $pdo->prepare('SELECT id FROM efetivo WHERE saram = :s LIMIT 1');
    $stmtOs->execute([':s' => $sOs]);
    $osId = $stmtOs->fetchColumn();
    if ($osId) {
        $pdo->prepare('UPDATE usuarios SET perfil = "DIRECAO_SUPERIOR" WHERE efetivo_id = :id AND perfil NOT IN ("ADMIN", "PRESIDENTE", "CHEFE_DIV_ASSESS")')
            ->execute([':id' => (int)$osId]);
    }
}
echo "  [DIREÇÃO SUPERIOR] Oficiais Superiores configurados para a Fase 4.\n";

// 5. GERAÇÃO DO CSV EXPORTÁVEL DO EFETIVO COMPLETO
$csvFile = __DIR__ . '/efetivo_completo.csv';
$fp = fopen($csvFile, 'w');
// BOM para abrir acentos perfeitamente no Microsoft Excel
fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
foreach ($csvData as $linha) {
    fputcsv($fp, $linha, ';');
}
fclose($fp);
echo "\n[CSV GERADO] Planilha oficial salva em: database/efetivo_completo.csv (" . count($csvData) . " linhas)\n";

// 6. ESTATÍSTICAS FINAIS NO BANCO
$totais = $pdo->query('SELECT categoria, COUNT(*) as qtd FROM efetivo WHERE ativo = 1 GROUP BY categoria')->fetchAll(PDO::FETCH_KEY_PAIR);
echo "\n=== DISTRIBUIÇÃO FINAL DE CANDIDATOS E ELEITORES NO BANCO ===\n";
echo "  - Graduado Padrão (SO / Sargentos): " . ($totais[CAT_GRADUADO] ?? 0) . " candidatos\n";
echo "  - Praça Padrão (Cabos / Soldados):   " . ($totais[CAT_PRACA] ?? 0) . " candidatos\n";
echo "  - Servidor Civil Permanente (SPPF): " . ($totais[CAT_SPPF] ?? 0) . " candidatos\n";
echo "  - Servidor Civil Temporário (SPTF): " . ($totais[CAT_SPTF] ?? 0) . " candidatos\n";
echo "  - Inelegíveis (Oficiais / Chefias): " . ($totais[CAT_INELIGIVEL] ?? 0) . " eleitores/avaliadores\n";
$totalGeral = $pdo->query('SELECT COUNT(*) FROM efetivo WHERE ativo = 1')->fetchColumn();
echo "  TOTAL GERAL NO EFETIVO: {$totalGeral} pessoas cadastradas.\n";
echo "=============================================================\n";
