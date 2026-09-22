<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';

$isMilitar = in_array($candidato['categoria'], ['graduado', 'praca'], true);
?>

<div class="row justify-content-center my-3">
    <div class="col-lg-10">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="/index.php?r=fase1" class="btn btn-outline-secondary btn-sm mb-2">
                    <i class="bi bi-arrow-left"></i> Voltar à listagem da Fase 1
                </a>
                <h3 class="h4 fw-bold text-primary mb-0">Ficha Digital de Indicação a Padrão</h3>
            </div>
            <span class="badge bg-primary fs-6"><?= CATEGORIAS_PADRAO[$candidato['categoria']]['nome'] ?></span>
        </div>

        <form method="POST" action="/index.php?r=fase1/avaliar&id=<?= $candidato['id'] ?>" class="card card-comara shadow">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <!-- 1. DADOS CADASTRAIS (PREENCHIMENTO AUTOMÁTICO) -->
            <div class="card-comara-header">
                <span>1. Dados Cadastrais do Candidato (Integrado à Base COMARA)</span>
                <span class="badge bg-secondary"><?= $tipoAval === 'OBRIGATORIA_CHEFE' ? 'Avaliação de Subordinado Direto' : 'Avaliação Voluntária' ?></span>
            </div>
            <div class="card-body bg-light border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center mb-3 mb-md-0">
                        <img src="/index.php?r=foto&saram=<?= $candidato['saram'] ?>&id=<?= $candidato['id'] ?>" class="cand-photo shadow-sm" alt="Foto">
                    </div>
                    <div class="col-md-10">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold">Nome Completo</label>
                                <div class="fs-5 fw-bold text-dark"><?= sanitize_output($candidato['nome']) ?></div>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted fw-bold">Nome de Guerra / Posto</label>
                                <div class="fs-5 text-primary fw-bold"><?= sanitize_output($candidato['posto']) ?> <?= sanitize_output($candidato['nome_guerra']) ?></div>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted fw-bold">SARAM / Matrícula</label>
                                <div class="fs-5 text-muted"><?= sanitize_output($candidato['saram'] ?: 'Civil') ?></div>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold">Setor de Trabalho</label>
                                <div class="fw-semibold"><?= sanitize_output($candidato['setor']) ?> (<?= sanitize_output($candidato['divisao_sigla']) ?>)</div>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold">Função Atual</label>
                                <div class="fw-semibold"><?= sanitize_output($candidato['funcao'] ?: 'Em exercício') ?></div>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold">Quadro / Especialidade</label>
                                <div><?= sanitize_output($candidato['quadro']) ?> - <?= sanitize_output($candidato['especialidade']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">

                <!-- 2. SERVIÇO DE ESCALA (MILITARES) -->
                <?php if ($isMilitar): ?>
                    <div class="mb-4 p-3 border rounded bg-white">
                        <label for="servico_escala" class="form-label fw-bold text-dark">
                            2. Serviço de Escala <span class="text-danger">*</span>
                            <small class="text-muted fw-normal d-block">Descreva a participação e o histórico do militar nos serviços de escala da OM (Guarda, Permanência, Sobreaviso, Operações).</small>
                        </label>
                        <input type="text" class="form-control form-control-lg" id="servico_escala" name="servico_escala" required
                               value="<?= sanitize_output($fichaExistente['servico_escala'] ?? '') ?>"
                               placeholder="Ex: Escala de Oficial de Dia / Adjunto / Guarnição de Serviço sem faltas ou atrasos.">
                    </div>
                <?php endif; ?>

                <!-- 3. AVALIAÇÃO DO TACF (EXCLUSIVO EDUCAÇÃO FÍSICA) -->
                <?php if ($isMilitar): ?>
                    <div class="mb-4 p-3 border rounded bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <label class="form-label fw-bold text-dark mb-0">
                                    3. Avaliação do TACF (Teste de Aptidão do Condicionamento Físico)
                                </label>
                                <div class="small text-muted">
                                    <i class="bi bi-lock-fill text-danger me-1"></i> Campo de edição privativa do setor de <strong>Educação Física (DAEF)</strong>. Bloqueado para edição do chefe direto.
                                </div>
                            </div>
                            <div>
                                <?php if ($tacf && $tacf['nota_tacf']): ?>
                                    <span class="badge bg-success fs-5 px-3 py-2">
                                        <?= number_format((float)$tacf['nota_tacf'], 2, ',', '.') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark fs-6">Aguardando lançamento DAEF</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 4 A 10. CRITÉRIOS AVALIATIVOS (NOTAS DE 1 A 5) -->
                <h5 class="fw-bold text-primary border-bottom pb-2 mb-3">Critérios Avaliativos Regimentais (Notas Inteiras de 1 a 5)</h5>
                <p class="small text-muted mb-4">Atribua a pontuação em cada critério estritamente de 1 (Desempenho Fraco) a 5 (Excelente / Padrão Notável).</p>

                <?php
                $criteriosCampos = [
                    'eficiencia_eficacia' => [
                        'num' => 4,
                        'titulo' => 'Eficiência e eficácia no desempenho de atribuições',
                        'desc' => 'Capacidade de cumprir missões com zelo, exatidão técnica, celeridade e otimização dos recursos públicos.'
                    ],
                    'conhecimento_especialidade' => [
                        'num' => 5,
                        'titulo' => 'Nível de conhecimento e interesse na especialidade',
                        'desc' => 'Domínio prático-teórico da sua área de atuação, busca contínua por aperfeiçoamento e atualização doutrinária.'
                    ],
                    'iniciativa_adaptabilidade' => [
                        'num' => 6,
                        'titulo' => 'Iniciativa, criatividade e adaptabilidade',
                        'desc' => 'Capacidade de antecipar soluções em situações imprevistas e adaptar-se com rapidez a mudanças de cenários.'
                    ],
                    'conduta_pontuacao' => [
                        'num' => 7,
                        'titulo' => 'Conduta e disciplina',
                        'desc' => 'Postura ética, respeito à hierarquia, disciplina, lealdade institucional e histórico de elogios/punições.'
                    ],
                    'apresentacao_pessoal' => [
                        'num' => 8,
                        'titulo' => 'Apresentação pessoal',
                        'desc' => 'Asseio pessoal, aprumo militar, cuidado com fardamento ou vestimenta corporativa padrão.'
                    ],
                    'relacionamento_trabalho' => [
                        'num' => 9,
                        'titulo' => 'Relacionamento no ambiente de trabalho',
                        'desc' => 'Espírito de corpo, camaradagem, cortesia, urbanidade e facilidade de cooperação com superiores e pares.'
                    ],
                    'lideranca' => [
                        'num' => 10,
                        'titulo' => 'Liderança',
                        'desc' => 'Capacidade de inspirar confiança, motivar equipes e conduzir atividades com firmeza e serenidade.'
                    ],
                ];
                ?>

                <div class="row g-4 mb-4">
                    <?php foreach ($criteriosCampos as $campo => $crit): ?>
                        <?php $valorAtual = (int)($fichaExistente[$campo] ?? 5); ?>
                        <div class="col-md-6">
                            <div class="card h-100 border p-3 bg-white">
                                <label class="fw-bold text-dark mb-1">
                                    <?= $crit['num'] ?>. <?= $crit['titulo'] ?> <span class="text-danger">*</span>
                                </label>
                                <small class="text-muted mb-3 d-block" style="min-height: 38px;"><?= $crit['desc'] ?></small>
                                
                                <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                    <?php for ($nota = 1; $nota <= 5; $nota++): ?>
                                        <div class="form-check form-check-inline m-0 text-center">
                                            <input class="form-check-input crit-radio" type="radio" 
                                                   name="<?= $campo ?>" id="<?= $campo ?>_<?= $nota ?>" 
                                                   value="<?= $nota ?>" <?= ($valorAtual === $nota) ? 'checked' : '' ?> required>
                                            <label class="form-check-label d-block fw-bold" for="<?= $campo ?>_<?= $nota ?>">
                                                <?= $nota ?>
                                            </label>
                                            <small class="text-muted" style="font-size: 0.65rem;">
                                                <?= $nota === 1 ? 'Fraco' : ($nota === 5 ? 'Notável' : '') ?>
                                            </small>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Campo Específico de Conduta (Fichas Meritórias e Demeritórias) -->
                <div class="mb-4 p-3 border rounded bg-white">
                    <label for="conduta_fichas" class="form-label fw-bold text-dark">
                        Registro Complementar de Conduta (Fichas Meritórias / Demeritórias / Elogios)
                    </label>
                    <textarea class="form-control" id="conduta_fichas" name="conduta_fichas" rows="2"
                              placeholder="Mencione elogios registrados em alterações, menções honrosas ou registros disciplinares relevantes..."><?= sanitize_output($fichaExistente['conduta_fichas'] ?? '') ?></textarea>
                </div>

                <!-- 11. CAMPOS ADICIONAIS: JUSTIFICATIVA E IDENTIFICAÇÃO -->
                <div class="mb-4 p-3 border rounded bg-white">
                    <label for="justificativa" class="form-label fw-bold text-dark">
                        11. Justificativa Detalhada da Indicação <span class="text-danger">*</span>
                        <small class="text-muted fw-normal d-block">Exponha de maneira circunstanciada os motivos pelos quais este integrante merece ser laureado como Padrão da COMARA.</small>
                    </label>
                    <textarea class="form-control" id="justificativa" name="justificativa" rows="4" required minlength="10"
                              placeholder="Descreva as contribuições extraordinárias, postura exemplar e méritos do candidato..."><?= sanitize_output($fichaExistente['justificativa'] ?? '') ?></textarea>
                </div>

                <!-- Identificação do Indicador -->
                <div class="p-3 bg-light border rounded d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted fw-bold">Oficial Avaliador Responsável:</div>
                        <div class="fw-bold text-primary"><?= sanitize_output($userLogado['posto']) ?> <?= sanitize_output($userLogado['nome_guerra']) ?> (<?= sanitize_output($userLogado['login']) ?>)</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted fw-bold">Registro Timestamp:</div>
                        <div class="small text-dark font-monospace"><?= date('d/m/Y H:i:s') ?></div>
                    </div>
                </div>

            </div>

            <div class="card-footer bg-light p-3 d-flex justify-content-between align-items-center">
                <a href="/index.php?r=fase1" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg fw-bold px-4">
                    <i class="bi bi-check-circle-fill me-2"></i> Salvar e Assinar Ficha de Indicação
                </button>
            </div>
        </form>

    </div>
</div>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
