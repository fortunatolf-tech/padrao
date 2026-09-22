<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/AuditoriaService.php';

/**
 * Modelo da Ficha Digital de Indicação a Padrão (Fase 1)
 * Validação estrita de notas (1 a 5 inteiros), serviço de escala militar e integridade criptográfica
 */
class FichaIndicacao {

    public static function salvarFicha(array $dados, int $avaliadorUsuarioId): array {
        $pleitoId    = (int)($dados['pleito_id'] ?? 0);
        $candidatoId = (int)($dados['candidato_id'] ?? 0);
        $tipoAval    = $dados['tipo_avaliacao'] ?? 'OBRIGATORIA_CHEFE';

        if (!$pleitoId || !$candidatoId) {
            return ['sucesso' => false, 'mensagem' => 'Pleito e Candidato são obrigatórios.'];
        }

        $candidato = Efetivo::getPorId($candidatoId);
        if (!$candidato) {
            return ['sucesso' => false, 'mensagem' => 'Candidato não localizado.'];
        }

        $isMilitar = in_array($candidato['categoria'], ['graduado', 'praca'], true);

        // Validação de Serviço de Escala (obrigatório para militares)
        $servicoEscala = trim($dados['servico_escala'] ?? '');
        if ($isMilitar && $servicoEscala === '') {
            return ['sucesso' => false, 'mensagem' => 'O campo Serviço de Escala é obrigatório para candidatos militares.'];
        }

        // Validação dos 7 Critérios Avaliativos (Valores exclusivamente inteiros de 1 a 5)
        $criterios = [
            'eficiencia_eficacia'        => (int)($dados['eficiencia_eficacia'] ?? 0),
            'conhecimento_especialidade' => (int)($dados['conhecimento_especialidade'] ?? 0),
            'iniciativa_adaptabilidade'  => (int)($dados['iniciativa_adaptabilidade'] ?? 0),
            'conduta_pontuacao'          => (int)($dados['conduta_pontuacao'] ?? 0),
            'apresentacao_pessoal'       => (int)($dados['apresentacao_pessoal'] ?? 0),
            'relacionamento_trabalho'    => (int)($dados['relacionamento_trabalho'] ?? 0),
            'lideranca'                  => (int)($dados['lideranca'] ?? 0)
        ];

        foreach ($criterios as $campo => $valor) {
            if ($valor < 1 || $valor > 5) {
                return [
                    'sucesso' => false,
                    'mensagem' => "A nota do critério {$campo} deve variar exclusivamente entre 1 e 5."
                ];
            }
        }

        $justificativa = trim($dados['justificativa'] ?? '');
        if (mb_strlen($justificativa) < 10) {
            return ['sucesso' => false, 'mensagem' => 'A justificativa da indicação é obrigatória e deve conter pelo menos 10 caracteres.'];
        }

        $condutaFichas = trim($dados['conduta_fichas'] ?? '');

        // Cálculo da média aritmética dos critérios
        $media = array_sum($criterios) / count($criterios);

        // Assinatura e Hash de integridade dos dados
        $agora = date('Y-m-d H:i:s');
        $stringIntegridade = "{$pleitoId}|{$candidatoId}|{$avaliadorUsuarioId}|" . implode('|', $criterios) . "|{$justificativa}|{$agora}";
        $hashIntegridade = hash('sha256', $stringIntegridade);

        $pdo = Database::getConnection();

        $stmt = $pdo->prepare('
            INSERT INTO `fichas_indicacao` (
                `pleito_id`, `candidato_id`, `avaliador_id`, `tipo_avaliacao`,
                `servico_escala`, `eficiencia_eficacia`, `conhecimento_especialidade`,
                `iniciativa_adaptabilidade`, `conduta_pontuacao`, `conduta_fichas`,
                `apresentacao_pessoal`, `relacionamento_trabalho`, `lideranca`,
                `justificativa`, `media_calculada`, `hash_integridade`, `created_at`
            ) VALUES (
                :pleito, :candidato, :avaliador, :tipo,
                :escala, :efic, :conhec,
                :inic, :conduta, :fichas,
                :apres, :relac, :lider,
                :justif, :media, :hash, :created
            ) ON DUPLICATE KEY UPDATE
                `servico_escala`            = VALUES(`servico_escala`),
                `eficiencia_eficacia`        = VALUES(`eficiencia_eficacia`),
                `conhecimento_especialidade` = VALUES(`conhecimento_especialidade`),
                `iniciativa_adaptabilidade`  = VALUES(`iniciativa_adaptabilidade`),
                `conduta_pontuacao`          = VALUES(`conduta_pontuacao`),
                `conduta_fichas`             = VALUES(`conduta_fichas`),
                `apresentacao_pessoal`       = VALUES(`apresentacao_pessoal`),
                `relacionamento_trabalho`    = VALUES(`relacionamento_trabalho`),
                `lideranca`                  = VALUES(`lideranca`),
                `justificativa`              = VALUES(`justificativa`),
                `media_calculada`            = VALUES(`media_calculada`),
                `hash_integridade`           = VALUES(`hash_integridade`),
                `updated_at`                 = VALUES(`created_at`)
        ');

        $stmt->execute([
            ':pleito'    => $pleitoId,
            ':candidato' => $candidatoId,
            ':avaliador' => $avaliadorUsuarioId,
            ':tipo'      => $tipoAval,
            ':escala'    => $servicoEscala,
            ':efic'      => $criterios['eficiencia_eficacia'],
            ':conhec'    => $criterios['conhecimento_especialidade'],
            ':inic'      => $criterios['iniciativa_adaptabilidade'],
            ':conduta'   => $criterios['conduta_pontuacao'],
            ':fichas'    => $condutaFichas,
            ':apres'     => $criterios['apresentacao_pessoal'],
            ':relac'     => $criterios['relacionamento_trabalho'],
            ':lider'     => $criterios['lideranca'],
            ':justif'    => $justificativa,
            ':media'     => $media,
            ':hash'      => $hashIntegridade,
            ':created'   => $agora
        ]);

        AuditoriaService::log('AVALIACAO_FASE1_REGISTRADA', [
            'pleito_id'    => $pleitoId,
            'candidato_id' => $candidatoId,
            'tipo'         => $tipoAval,
            'media'        => round($media, 2)
        ], $avaliadorUsuarioId);

        return [
            'sucesso'  => true,
            'media'    => round($media, 2),
            'mensagem' => 'Ficha de indicação registrada com sucesso.'
        ];
    }

    public static function getFicha(int $pleitoId, int $candidatoId, int $avaliadorId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM fichas_indicacao WHERE pleito_id = :p AND candidato_id = :c AND avaliador_id = :a LIMIT 1');
        $stmt->execute([':p' => $pleitoId, ':c' => $candidatoId, ':a' => $avaliadorId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }
}
