<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Services/AuditoriaService.php';

/**
 * Modelo de Gestão de Ciclos Eleitorais e Fases Regimentais
 */
class Pleito {

    public static function getAtivo(): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM pleitos WHERE status IN ('em_andamento', 'planejamento') ORDER BY ano DESC, id DESC LIMIT 1");
        $res = $stmt->fetch();
        if ($res) {
            return $res;
        }
        // Fallback: se o pleito foi homologado ou concluído, retorna o mais recente para permitir gestão, relatórios e reinicialização
        $stmt = $pdo->query("SELECT * FROM pleitos ORDER BY ano DESC, id DESC LIMIT 1");
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function getPorId(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM pleitos WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    /**
     * Valida se a Fase 1 pode ser encerrada.
     * Regra mandatória: Todos os oficiais chefes diretos DEVEM ter concluído as fichas de seus subordinados diretos.
     */
    public static function validarPendenciasFase1(int $pleitoId): array {
        $pdo = Database::getConnection();

        // Militares que possuem chefe direto e que concorrem nas categorias (Graduado e Praça)
        $sql = "
            SELECT e.id, e.nome, e.nome_guerra, e.posto, e.setor, e.categoria,
                   c.nome as chefe_nome, c.nome_guerra as chefe_guerra, c.posto as chefe_posto
            FROM efetivo e
            INNER JOIN efetivo c ON c.id = e.chefe_direto_id
            LEFT JOIN fichas_indicacao f ON f.candidato_id = e.id AND f.pleito_id = :p AND f.tipo_avaliacao = 'OBRIGATORIA_CHEFE'
            WHERE e.ativo = 1 
              AND e.categoria IN ('graduado', 'praca')
              AND f.id IS NULL
            ORDER BY c.nome_guerra, e.nome
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':p' => $pleitoId]);
        $pendentes = $stmt->fetchAll();

        return [
            'pode_avancar' => empty($pendentes),
            'total_pendentes' => count($pendentes),
            'pendencias' => $pendentes
        ];
    }

    /**
     * Valida e avança o pleito para a fase seguinte com bloqueios regimentais
     */
    public static function avancarFase(int $pleitoId, ?int $usuarioId = null): array {
        $pleito = self::getPorId($pleitoId);
        if (!$pleito) {
            return ['sucesso' => false, 'mensagem' => 'Pleito não encontrado.'];
        }

        $faseAtual = (int)$pleito['fase_atual'];
        $proximaFase = $faseAtual + 1;

        if ($proximaFase > 5) {
            return ['sucesso' => false, 'mensagem' => 'O pleito já atingiu a fase final. Utilize a opção de Homologação.'];
        }

        // BLOQUEIO DA FASE 1: Se houver avaliações obrigatórias pendentes pelos chefes diretos
        if ($faseAtual === FASE_1) {
            $verificacao = self::validarPendenciasFase1($pleitoId);
            if (!$verificacao['pode_avancar']) {
                AuditoriaService::log('AVANCO_FASE_BLOQUEADO', [
                    'pleito_id'       => $pleitoId,
                    'fase_tentada'    => 2,
                    'total_pendentes' => $verificacao['total_pendentes']
                ], $usuarioId);

                return [
                    'sucesso'    => false,
                    'bloqueado'  => true,
                    'mensagem'   => "Bloqueio Regimental: Não é possível avançar para a Fase 2 enquanto houver avaliações obrigatórias de chefes diretos pendentes ({$verificacao['total_pendentes']} pendência(s)).",
                    'pendencias' => $verificacao['pendencias']
                ];
            }
        }

        // SE SAIR DA FASE 2: Consolida automaticamente os indicados
        if ($faseAtual === FASE_2) {
            self::consolidarFase2($pleitoId);
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('UPDATE pleitos SET fase_atual = :fase WHERE id = :id');
        $stmt->execute([':fase' => $proximaFase, ':id' => $pleitoId]);

        AuditoriaService::log('FASE_AVANCADA', [
            'pleito_id' => $pleitoId,
            'fase_de'   => $faseAtual,
            'fase_para' => $proximaFase
        ], $usuarioId);

        return [
            'sucesso'  => true,
            'fase'     => $proximaFase,
            'mensagem' => "Pleito avançado com sucesso para a Fase {$proximaFase}: " . FASES_PROCESSO[$proximaFase]['nome']
        ];
    }

    /**
     * Consolidação automática dos indicados da Fase 2 para as fases seguintes
     */
    public static function consolidarFase2(int $pleitoId): void {
        $pdo = Database::getConnection();

        // Agrupa votos/seleções de cada divisão e assessoria na Fase 2
        $sql = "
            SELECT s.candidato_id, s.categoria, COUNT(s.id) as total_indicacoes,
                   COALESCE(AVG(f.media_calculada), 0) as media_fase1
            FROM selecao_fase2 s
            LEFT JOIN fichas_indicacao f ON f.candidato_id = s.candidato_id AND f.pleito_id = s.pleito_id
            WHERE s.pleito_id = :p
            GROUP BY s.candidato_id, s.categoria
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':p' => $pleitoId]);
        $indicados = $stmt->fetchAll();

        $stmtIns = $pdo->prepare('
            INSERT INTO `indicados_consolidados` (
                `pleito_id`, `candidato_id`, `categoria`, `total_indicacoes_orgaos`, `media_fase1`, `status_validacao_superior`
            ) VALUES (
                :pleito, :cand, :cat, :total, :media, "VALIDADO"
            ) ON DUPLICATE KEY UPDATE
                `total_indicacoes_orgaos` = VALUES(`total_indicacoes_orgaos`),
                `media_fase1` = VALUES(`media_fase1`)
        ');

        foreach ($indicados as $ind) {
            $stmtIns->execute([
                ':pleito' => $pleitoId,
                ':cand'   => (int)$ind['candidato_id'],
                ':cat'    => $ind['categoria'],
                ':total'  => (int)$ind['total_indicacoes'],
                ':media'  => (float)$ind['media_fase1']
            ]);
        }
    }

    /**
     * Reinicia o pleito eleitoral retornando-o para a Fase 1 (Avaliações dos Chefes Diretos)
     * com opção de limpeza dos votos e avaliações do ciclo atual.
     */
    public static function reiniciar(
        int $pleitoId,
        bool $limparDados = true,
        bool $limparTacf = false,
        ?int $usuarioId = null
    ): array {
        $pleito = self::getPorId($pleitoId);
        if (!$pleito) {
            return ['sucesso' => false, 'mensagem' => 'Pleito não encontrado.'];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Reverte incremento de indicações anteriores de vencedores homologados (se houver)
            $stmtHomol = $pdo->prepare('SELECT vencedor_graduado_id, vencedor_praca_id, vencedor_sppf_id, vencedor_sptf_id FROM homologacao_pleito WHERE pleito_id = :p');
            $stmtHomol->execute([':p' => $pleitoId]);
            $homol = $stmtHomol->fetch(PDO::FETCH_ASSOC);
            if ($homol) {
                $ids = array_filter([
                    (int)($homol['vencedor_graduado_id'] ?? 0),
                    (int)($homol['vencedor_praca_id'] ?? 0),
                    (int)($homol['vencedor_sppf_id'] ?? 0),
                    (int)($homol['vencedor_sptf_id'] ?? 0)
                ]);
                if (!empty($ids)) {
                    $in = implode(',', $ids);
                    $pdo->exec("UPDATE efetivo SET indicacoes_anteriores_qtd = GREATEST(0, indicacoes_anteriores_qtd - 1) WHERE id IN ($in)");
                }
            }

            // 2. Limpa registros das etapas regimentais
            if ($limparDados) {
                $pdo->prepare('DELETE FROM homologacao_pleito WHERE pleito_id = :p')->execute([':p' => $pleitoId]);
                $pdo->prepare('DELETE FROM voto_minerva_presidente WHERE pleito_id = :p')->execute([':p' => $pleitoId]);
                $pdo->prepare('DELETE FROM urna_votos WHERE pleito_id = :p')->execute([':p' => $pleitoId]);
                $pdo->prepare('DELETE FROM eleitores_ciclo WHERE pleito_id = :p')->execute([':p' => $pleitoId]);
                $pdo->prepare('DELETE FROM indicados_consolidados WHERE pleito_id = :p')->execute([':p' => $pleitoId]);
                $pdo->prepare('DELETE FROM selecao_fase2 WHERE pleito_id = :p')->execute([':p' => $pleitoId]);
                $pdo->prepare('DELETE FROM fichas_indicacao WHERE pleito_id = :p')->execute([':p' => $pleitoId]);

                if ($limparTacf) {
                    $pdo->prepare('DELETE FROM avaliacoes_tacf WHERE pleito_id = :p')->execute([':p' => $pleitoId]);
                }
            }

            // 3. Atualiza o pleito para Fase 1 e reabre status para 'em_andamento'
            $stmtUp = $pdo->prepare('
                UPDATE pleitos SET
                    fase_atual = 1,
                    status = "em_andamento",
                    homologado_em = NULL,
                    homologado_por = NULL
                WHERE id = :p
            ');
            $stmtUp->execute([':p' => $pleitoId]);

            $pdo->commit();

            AuditoriaService::log('PLEITO_REINICIADO', [
                'pleito_id'       => $pleitoId,
                'ano'             => $pleito['ano'],
                'titulo'          => $pleito['titulo'],
                'limpou_dados'    => $limparDados,
                'limpou_tacf'     => $limparTacf,
                'fase_anterior'   => $pleito['fase_atual'],
                'status_anterior' => $pleito['status']
            ], $usuarioId);

            $msg = 'Pleito reiniciado com sucesso para a Fase 1: Avaliações dos Chefes Diretos & TACF.';
            if ($limparDados) {
                $msg .= ' Todos os votos e avaliações do ciclo anterior foram zerados para um novo processo.';
            }

            return [
                'sucesso'  => true,
                'fase'     => 1,
                'mensagem' => $msg
            ];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [
                'sucesso'  => false,
                'mensagem' => 'Erro ao reiniciar pleito: ' . $e->getMessage()
            ];
        }
    }
}
