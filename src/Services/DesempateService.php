<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Serviço de Resolução Automatizada dos Critérios de Desempate
 * Aplica a sequência regimental estrita da COMARA
 */
class DesempateService {

    /**
     * Ordena uma lista de candidatos aplicando pontuação principal e os 4 critérios de desempate
     * Retorna a lista ordenada e um array identificando se houve empate persistente em 1º lugar.
     */
    public static function ordenarCandidatos(array $candidatos, string $campoPontuacao = 'total_votos'): array {
        usort($candidatos, function ($a, $b) use ($campoPontuacao) {
            // 0. Pontuação Principal (Votos ou Média) - Maior primeiro
            $pontosA = (float)($a[$campoPontuacao] ?? 0);
            $pontosB = (float)($b[$campoPontuacao] ?? 0);
            if ($pontosA !== $pontosB) {
                return ($pontosB <=> $pontosA);
            }

            // CRITÉRIO 1: Candidatos que NUNCA foram indicados em pleitos anteriores têm prioridade
            $indA = (int)($a['indicacoes_anteriores_qtd'] ?? 0);
            $indB = (int)($b['indicacoes_anteriores_qtd'] ?? 0);
            $nuncaA = ($indA === 0) ? 1 : 0;
            $nuncaB = ($indB === 0) ? 1 : 0;
            if ($nuncaA !== $nuncaB) {
                return ($nuncaB <=> $nuncaA); // 1 tem prioridade sobre 0
            }

            // CRITÉRIO 2: Candidato mais antigo na instituição COMARA (Data mais antiga vence)
            $dtComaraA = !empty($a['data_admissao_comara']) ? strtotime($a['data_admissao_comara']) : PHP_INT_MAX;
            $dtComaraB = !empty($b['data_admissao_comara']) ? strtotime($b['data_admissao_comara']) : PHP_INT_MAX;
            if ($dtComaraA !== $dtComaraB) {
                return ($dtComaraA <=> $dtComaraB);
            }

            // CRITÉRIO 3: Maior tempo de vínculo à OM atual (Data mais antiga vence)
            $dtOmA = !empty($a['data_vinculo_om']) ? strtotime($a['data_vinculo_om']) : PHP_INT_MAX;
            $dtOmB = !empty($b['data_vinculo_om']) ? strtotime($b['data_vinculo_om']) : PHP_INT_MAX;
            if ($dtOmA !== $dtOmB) {
                return ($dtOmA <=> $dtOmB);
            }

            // CRITÉRIO 4: Maior nota na Avaliação do TACF
            $tacfA = (float)($a['nota_tacf'] ?? 0);
            $tacfB = (float)($b['nota_tacf'] ?? 0);
            if ($tacfA !== $tacfB) {
                return ($tacfB <=> $tacfA); // Maior nota vence
            }

            // Empate persistente irresolúvel pelos critérios automáticos
            return 0;
        });

        // Verifica se há empate em 1º lugar entre o primeiro e o segundo colocado
        $empatePrimeiroLugar = false;
        $candidatosEmpatados = [];

        if (count($candidatos) >= 2) {
            $c1 = $candidatos[0];
            $c2 = $candidatos[1];

            $igualPontos = ((float)($c1[$campoPontuacao] ?? 0) === (float)($c2[$campoPontuacao] ?? 0));
            $igualCrit1  = ((int)($c1['indicacoes_anteriores_qtd'] ?? 0) === 0) === ((int)($c2['indicacoes_anteriores_qtd'] ?? 0) === 0);
            $igualCrit2  = (($c1['data_admissao_comara'] ?? '') === ($c2['data_admissao_comara'] ?? ''));
            $igualCrit3  = (($c1['data_vinculo_om'] ?? '') === ($c2['data_vinculo_om'] ?? ''));
            $igualCrit4  = ((float)($c1['nota_tacf'] ?? 0) === (float)($c2['nota_tacf'] ?? 0));

            if ($igualPontos && $igualCrit1 && $igualCrit2 && $igualCrit3 && $igualCrit4) {
                $empatePrimeiroLugar = true;
                $candidatosEmpatados = [$c1, $c2];
                // Verifica se há um 3º também empatado
                if (isset($candidatos[2]) && ((float)$candidatos[2][$campoPontuacao] === (float)$c1[$campoPontuacao])) {
                    $candidatosEmpatados[] = $candidatos[2];
                }
            }
        }

        return [
            'ranking'                => $candidatos,
            'requer_voto_de_minerva' => $empatePrimeiroLugar,
            'candidatos_empatados'   => $candidatosEmpatados
        ];
    }

    /**
     * Retorna a justificativa legível do critério que desempatou dois candidatos
     */
    public static function explicarDesempate(array $vencedor, array $segundo): string {
        $indV = (int)($vencedor['indicacoes_anteriores_qtd'] ?? 0);
        $indS = (int)($segundo['indicacoes_anteriores_qtd'] ?? 0);
        if ($indV === 0 && $indS > 0) {
            return "Critério 1: O candidato vencedor nunca foi indicado em pleitos anteriores ({$vencedor['nome_guerra']}), enquanto o concorrente já possuía indicação ({$segundo['nome_guerra']}).";
        }

        if (($vencedor['data_admissao_comara'] ?? '') !== ($segundo['data_admissao_comara'] ?? '')) {
            $dtV = date('d/m/Y', strtotime($vencedor['data_admissao_comara']));
            $dtS = date('d/m/Y', strtotime($segundo['data_admissao_comara']));
            return "Critério 2: Maior antiguidade na COMARA. Vencedor admitido em {$dtV} vs concorrente em {$dtS}.";
        }

        if (($vencedor['data_vinculo_om'] ?? '') !== ($segundo['data_vinculo_om'] ?? '')) {
            $dtV = date('d/m/Y', strtotime($vencedor['data_vinculo_om']));
            $dtS = date('d/m/Y', strtotime($segundo['data_vinculo_om']));
            return "Critério 3: Maior tempo de vínculo à OM atual. Vencedor desde {$dtV} vs concorrente desde {$dtS}.";
        }

        if ((float)($vencedor['nota_tacf'] ?? 0) !== (float)($segundo['nota_tacf'] ?? 0)) {
            return "Critério 4: Maior nota na Avaliação do TACF ({$vencedor['nota_tacf']} vs {$segundo['nota_tacf']}).";
        }

        return "Empate persistente após todos os 4 critérios regimentais.";
    }
}
