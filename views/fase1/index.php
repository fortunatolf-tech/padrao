<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 fw-bold text-primary mb-1">Fase 1: Avaliação pelos Chefes Diretos</h2>
        <div class="text-muted small">Preenchimento obrigatório da ficha pelos oficiais avaliadores e notas de TACF pela Educação Física.</div>
    </div>
    <div>
        <span class="badge bg-secondary p-2">Status da Fase: <?= FASES_PROCESSO[FASE_1]['nome'] ?></span>
    </div>
</div>

<!-- Abas de Navegação da Fase 1 -->
<ul class="nav nav-tabs mb-4" id="fase1Tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" id="subord-tab" data-bs-toggle="tab" data-bs-target="#tabSubordinados" type="button">
            <i class="bi bi-person-check-fill me-1"></i> Minhas Avaliações Obrigatórias
            <?php if (!empty($subordinados)): ?>
                <span class="badge bg-primary ms-1"><?= count($subordinados) ?></span>
            <?php endif; ?>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="voluntaria-tab" data-bs-toggle="tab" data-bs-target="#tabVoluntaria" type="button">
            <i class="bi bi-star me-1"></i> Avaliações Voluntárias (Outros Militares/Civis)
        </button>
    </li>
    <?php if (AuthManager::hasRole([PERFIL_ED_FISICA, PERFIL_ADMIN])): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-success" id="tacf-tab" data-bs-toggle="tab" data-bs-target="#tabTacf" type="button">
                <i class="bi bi-activity me-1"></i> Módulo Exclusivo TACF (Educação Física)
                <span class="badge bg-success ms-1"><?= count($militaresTacf) ?></span>
            </button>
        </li>
    <?php endif; ?>
    <?php if (AuthManager::hasRole([PERFIL_ADMIN, PERFIL_DIRECAO_SUPERIOR, PERFIL_PRESIDENTE])): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold text-danger" id="pendencias-tab" data-bs-toggle="tab" data-bs-target="#tabPendencias" type="button">
                <i class="bi bi-shield-lock me-1"></i> Controle de Pendências Globais
                <span class="badge bg-danger ms-1"><?= $verificacaoPendencias['total_pendentes'] ?></span>
            </button>
        </li>
    <?php endif; ?>
</ul>

<div class="tab-content">

    <!-- 1. ABA DE SUBORDINADOS DIRETOS (OBRIGATÓRIA) -->
    <div class="tab-pane fade show active" id="tabSubordinados" role="tabpanel">
        <div class="card card-comara">
            <div class="card-comara-header">
                <span>Militares Diretamente Subordinados (Avaliação Compulsória)</span>
                <span class="small text-muted">Avanço para Fase 2 condicionado a 100% de conclusão</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($subordinados)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-info-circle fs-3 d-block mb-2 text-secondary"></i>
                        Você não possui militares diretamente subordinados vinculados no cadastro funcional ou não está logado como Chefe Direto.<br>
                        Você pode realizar avaliações voluntárias na aba ao lado.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70px;">Foto</th>
                                    <th>Candidato</th>
                                    <th>Posto / Especialidade</th>
                                    <th>Setor</th>
                                    <th>Categoria</th>
                                    <th>Nota TACF</th>
                                    <th>Média da Ficha</th>
                                    <th>Status</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subordinados as $sub): ?>
                                    <tr>
                                        <td>
                                            <img src="/index.php?r=foto&saram=<?= $sub['saram'] ?>&id=<?= $sub['id'] ?>" class="cand-photo-sm" alt="Foto">
                                        </td>
                                        <td>
                                            <strong><?= sanitize_output($sub['nome_guerra']) ?></strong><br>
                                            <small class="text-muted"><?= sanitize_output($sub['nome']) ?></small>
                                            <?php if (!empty($sub['delegacao_id'])): ?>
                                                <div class="mt-1">
                                                    <?php if ((int)$sub['sou_o_delegado'] === 1): ?>
                                                        <span class="badge bg-warning text-dark" title="Motivo: <?= sanitize_output($sub['delegacao_motivo']) ?>">
                                                            <i class="bi bi-person-badge me-1"></i>Delegado por: <?= sanitize_output($sub['chefe_orig_posto'] . ' ' . $sub['chefe_orig_guerra']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info text-dark" title="Motivo: <?= sanitize_output($sub['delegacao_motivo']) ?>">
                                                            <i class="bi bi-person-fill-gear me-1"></i>Delegado ao: <?= sanitize_output($sub['delegado_posto'] . ' ' . $sub['delegado_guerra']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= sanitize_output($sub['posto']) ?></span>
                                            <small class="text-muted"><?= sanitize_output($sub['especialidade']) ?></small>
                                        </td>
                                        <td><?= sanitize_output($sub['setor']) ?></td>
                                        <td>
                                            <span class="badge bg-secondary"><?= CATEGORIAS_PADRAO[$sub['categoria']]['sigla'] ?></span>
                                        </td>
                                        <td>
                                            <?php if ($sub['nota_tacf']): ?>
                                                <span class="badge bg-info text-dark"><?= number_format((float)$sub['nota_tacf'], 2, ',', '.') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border">Pendente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($sub['ficha_id']): ?>
                                                <span class="badge bg-success fs-6"><?= number_format((float)$sub['media_calculada'], 2, ',', '.') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Não Avaliado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($sub['ficha_id']): ?>
                                                <span class="text-success small fw-semibold"><i class="bi bi-check-all"></i> Concluído</span>
                                            <?php else: ?>
                                                <span class="text-danger small fw-semibold"><i class="bi bi-exclamation-circle"></i> Obrigatória</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="/index.php?r=fase1/avaliar&id=<?= $sub['id'] ?>" class="btn btn-sm <?= $sub['ficha_id'] ? 'btn-outline-primary' : 'btn-primary fw-bold' ?>">
                                                <i class="bi bi-pencil-square me-1"></i> <?= $sub['ficha_id'] ? 'Editar Ficha' : 'Preencher Ficha' ?>
                                            </a>
                                            <?php if (!$sub['ficha_id'] && (int)$sub['sou_o_delegado'] === 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning ms-1"
                                                        data-bs-toggle="modal" data-bs-target="#modalDelegarOficial"
                                                        data-candidato-id="<?= $sub['id'] ?>"
                                                        data-candidato-nome="<?= sanitize_output($sub['posto'] . ' ' . $sub['nome_guerra']) ?>"
                                                        data-candidato-sub="<?= sanitize_output($sub['nome']) ?>"
                                                        data-delegado-id="<?= $sub['oficial_delegado_id'] ?? '' ?>"
                                                        data-delegacao-motivo="<?= sanitize_output($sub['delegacao_motivo'] ?? '') ?>"
                                                        title="Indicar outro oficial avaliador por motivo de missão/afastamento">
                                                    <i class="bi bi-person-fill-exclamation me-1"></i> <?= $sub['delegacao_id'] ? 'Alterar' : 'Em Missão?' ?>
                                                </button>
                                                <?php if ($sub['delegacao_id']): ?>
                                                    <form method="POST" action="/index.php?r=fase1/remover_delegacao" class="d-inline" onsubmit="return confirm('Remover delegação de <?= addslashes(sanitize_output($sub['nome_guerra'])) ?> e reassumir a avaliação?');">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="candidato_id" value="<?= $sub['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="Cancelar delegação">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 2. ABA DE AVALIAÇÕES VOLUNTÁRIAS -->
    <div class="tab-pane fade" id="tabVoluntaria" role="tabpanel">
        <div class="card card-comara">
            <div class="card-comara-header">
                <span>Avaliações Voluntárias (Efetivo COMARA)</span>
                <span class="small text-muted">Avaliador identificado e gravado em log permanente</span>
            </div>
            <div class="card-body">
                <p class="small text-muted">Como oficial ou avaliador credenciado, você pode indicar voluntariamente outros militares e servidores civis que se destacaram pelo desempenho e virtudes regimentais.</p>
                
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Foto</th>
                                <th>Nome / Nome de Guerra</th>
                                <th>Posto / Categoria</th>
                                <th>Setor</th>
                                <th>Média Fase 1</th>
                                <th class="text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todosCandidatos as $cand): ?>
                                <tr>
                                    <td style="width: 50px;">
                                        <img src="/index.php?r=foto&saram=<?= $cand['saram'] ?>&id=<?= $cand['id'] ?>" class="cand-photo-sm" alt="Foto">
                                    </td>
                                    <td>
                                        <strong><?= sanitize_output($cand['posto']) ?> <?= sanitize_output($cand['nome_guerra']) ?></strong><br>
                                        <small class="text-muted"><?= sanitize_output($cand['nome']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= CATEGORIAS_PADRAO[$cand['categoria']]['nome'] ?></span>
                                    </td>
                                    <td><?= sanitize_output($cand['setor']) ?> (<?= sanitize_output($cand['divisao_sigla']) ?>)</td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= number_format((float)$cand['media_geral_fase1'], 2, ',', '.') ?> (<?= $cand['total_avaliacoes'] ?> aval.)</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="/index.php?r=fase1/avaliar&id=<?= $cand['id'] ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-star me-1"></i> Avaliação Voluntária
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. MÓDULO EXCLUSIVO EDUCAÇÃO FÍSICA (TACF) -->
    <?php if (AuthManager::hasRole([PERFIL_ED_FISICA, PERFIL_ADMIN])): ?>
        <div class="tab-pane fade" id="tabTacf" role="tabpanel">
            <div class="card card-comara">
                <div class="card-comara-header bg-success-subtle text-success border-success">
                    <span class="fw-bold"><i class="bi bi-activity me-2"></i>Edição Exclusiva da Avaliação do TACF (DAEF / Seção de Educação Física)</span>
                    <span class="badge bg-success">Permissão Restrita</span>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Conforme determinação regimental, o campo <strong>Avaliação do TACF</strong> é bloqueado para todos os chefes diretos e avaliadores gerais, sendo de preenchimento e responsabilidade privativa deste setor.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>SARAM</th>
                                    <th>Militar</th>
                                    <th>Posto / Especialidade</th>
                                    <th>Setor</th>
                                    <th>Nota TACF (1.00 a 5.00)</th>
                                    <th>Data Realização</th>
                                    <th>Observações</th>
                                    <th class="text-end">Lançar / Atualizar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($militaresTacf as $mt): ?>
                                    <tr>
                                        <td><code><?= sanitize_output($mt['saram']) ?></code></td>
                                        <td>
                                            <strong><?= sanitize_output($mt['posto']) ?> <?= sanitize_output($mt['nome_guerra']) ?></strong><br>
                                            <small class="text-muted"><?= sanitize_output($mt['nome']) ?></small>
                                        </td>
                                        <td><?= sanitize_output($mt['quadro']) ?> - <?= sanitize_output($mt['especialidade']) ?></td>
                                        <td><?= sanitize_output($mt['setor']) ?></td>
                                        <td>
                                            <?php if ($mt['nota_tacf']): ?>
                                                <span class="badge bg-success fs-6"><?= number_format((float)$mt['nota_tacf'], 2, ',', '.') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pendente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $mt['data_realizacao'] ? date('d/m/Y', strtotime($mt['data_realizacao'])) : '--' ?></td>
                                        <td><small class="text-muted"><?= sanitize_output($mt['observacoes'] ?? '--') ?></small></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-success fw-bold"
                                                    data-bs-toggle="modal" data-bs-target="#modalTacf"
                                                    data-candidato-id="<?= $mt['id'] ?>"
                                                    data-candidato-nome="<?= sanitize_output($mt['posto'] . ' ' . $mt['nome_guerra']) ?>"
                                                    data-candidato-nota="<?= $mt['nota_tacf'] ?? '5.00' ?>">
                                                <i class="bi bi-pencil-fill"></i> Lançar Nota
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4. CONTROLE DE PENDÊNCIAS GLOBAIS (ADMIN / DIREÇÃO SUPERIOR) -->
    <?php if (AuthManager::hasRole([PERFIL_ADMIN, PERFIL_DIRECAO_SUPERIOR, PERFIL_PRESIDENTE])): ?>
        <div class="tab-pane fade" id="tabPendencias" role="tabpanel">
            <div class="card card-comara">
                <div class="card-comara-header">
                    <span>Relatório de Avaliações Obrigatórias de Chefes Diretos Pendentes</span>
                    <span class="badge <?= $verificacaoPendencias['pode_avancar'] ? 'bg-success' : 'bg-danger' ?>">
                        <?= $verificacaoPendencias['total_pendentes'] ?> Pendência(s)
                    </span>
                </div>
                <div class="card-body">
                    <?php if ($verificacaoPendencias['pode_avancar']): ?>
                        <div class="alert alert-success d-flex align-items-center mb-0">
                            <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                            <div>
                                <h5 class="alert-heading fw-bold mb-1">Todas as avaliações obrigatórias foram concluídas!</h5>
                                <p class="mb-0 small">O sistema está regimentalmente liberado para avançar para a Fase 2 (Seleção pelos Chefes de Divisão e Assessoria).</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-3">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i>
                            <strong>Bloqueio de Avanço Ativo:</strong> Enquanto os chefes diretos abaixo não finalizarem as fichas de seus subordinados, o avanço para a Fase 2 permanecerá bloqueado.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Chefe Direto Titular</th>
                                        <th>Militar Subordinado Pendente</th>
                                        <th>Posto / Categoria</th>
                                        <th>Setor</th>
                                        <th>Situação de Delegação</th>
                                        <?php if (AuthManager::hasRole(PERFIL_ADMIN)): ?>
                                            <th class="text-end">Ação Administrativa</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($verificacaoPendencias['pendencias'] as $p): ?>
                                        <tr>
                                            <td class="fw-bold text-danger">
                                                <i class="bi bi-person-exclamation me-1"></i>
                                                <?= sanitize_output($p['chefe_posto'] . ' ' . $p['chefe_guerra']) ?>
                                            </td>
                                            <td><?= sanitize_output($p['posto'] . ' ' . $p['nome_guerra']) ?> (<?= sanitize_output($p['nome']) ?>)</td>
                                            <td><?= sanitize_output($p['categoria']) ?></td>
                                            <td><?= sanitize_output($p['setor']) ?></td>
                                            <td>
                                                <?php if (!empty($p['delegacao_id'])): ?>
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-person-check-fill me-1"></i>Delegado ao: <?= sanitize_output($p['delegado_posto'] . ' ' . $p['delegado_guerra']) ?></span>
                                                    <small class="text-muted d-block"><?= sanitize_output($p['delegacao_motivo']) ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted border">Chefe Titular (Pendente)</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if (AuthManager::hasRole(PERFIL_ADMIN)): ?>
                                                <td class="text-end text-nowrap">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="modal" data-bs-target="#modalDelegarOficial"
                                                            data-candidato-id="<?= $p['id'] ?>"
                                                            data-candidato-nome="<?= sanitize_output($p['posto'] . ' ' . $p['nome_guerra']) ?>"
                                                            data-candidato-sub="<?= sanitize_output($p['nome']) ?>"
                                                            data-delegado-id="<?= $p['oficial_delegado_id'] ?? '' ?>"
                                                            data-delegacao-motivo="<?= sanitize_output($p['delegacao_motivo'] ?? '') ?>">
                                                        <i class="bi bi-person-plus me-1"></i> <?= $p['delegacao_id'] ? 'Alterar Substituto' : 'Designar Substituto' ?>
                                                    </button>
                                                    <?php if ($p['delegacao_id']): ?>
                                                        <form method="POST" action="/index.php?r=fase1/remover_delegacao" class="d-inline" onsubmit="return confirm('Remover delegação de <?= addslashes(sanitize_output($p['nome_guerra'])) ?>?');">
                                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                            <input type="hidden" name="candidato_id" value="<?= $p['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger ms-1" title="Cancelar delegação">
                                                                <i class="bi bi-x-circle"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Modal para Lançamento de TACF -->
<div class="modal fade" id="modalTacf" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/index.php?r=fase1/tacf">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="candidato_id" id="tacf_candidato_id">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-activity me-2"></i>Lançar Avaliação do TACF</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Militar Candidato:</label>
                        <div class="fs-5 fw-bold text-primary" id="tacf_candidato_nome">--</div>
                    </div>

                    <div class="mb-3">
                        <label for="tacf_nota" class="form-label fw-bold">Nota do TACF (1.00 a 5.00)</label>
                        <input type="number" step="0.01" min="1.00" max="5.00" class="form-control form-control-lg text-center fw-bold" id="tacf_nota" name="nota_tacf" required>
                        <div class="form-text">Nota oficial emitida pelo setor de Educação Física.</div>
                    </div>

                    <div class="mb-3">
                        <label for="tacf_data" class="form-label">Data da Realização do Teste</label>
                        <input type="date" class="form-control" id="tacf_data" name="data_realizacao" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="tacf_obs" class="form-label">Observações / Menções</label>
                        <textarea class="form-control" id="tacf_obs" name="observacoes" rows="2" placeholder="Ex: Aprovado com índice Muito Bom"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-save me-1"></i> Gravar Nota TACF</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Delegação de Avaliação de Chefe em Missão -->
<div class="modal fade" id="modalDelegarOficial" tabindex="-1" aria-labelledby="modalDelegarOficialLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <form method="POST" action="/index.php?r=fase1/delegar">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="candidato_id" id="delegar_candidato_id">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="modalDelegarOficialLabel">
                        <i class="bi bi-person-badge-fill me-2"></i> Indicar Oficial Substituto (Chefe em Missão)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Caso o chefe imediato esteja em missão ou afastado, outro Oficial pode ser designado para avaliar o militar. <strong>Assim que o oficial substituto registrar a avaliação, o militar sairá automaticamente da lista de pendências.</strong>
                    </div>

                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="text-muted small fw-semibold">Subordinado a ser avaliado:</div>
                        <div class="fs-6 fw-bold text-dark" id="delegar_candidato_nome">--</div>
                        <small class="text-muted" id="delegar_candidato_sub">--</small>
                    </div>

                    <div class="mb-3">
                        <label for="delegar_oficial_id" class="form-label fw-bold text-dark small">Oficial Substituto Designado <span class="text-danger">*</span>:</label>
                        <select name="oficial_delegado_id" id="delegar_oficial_id" class="form-select" required>
                            <option value="">Selecione o Oficial...</option>
                            <?php foreach ($oficiais as $of): ?>
                                <option value="<?= $of['id'] ?>">
                                    <?= sanitize_output($of['posto']) ?> <?= sanitize_output($of['nome_guerra']) ?> (<?= sanitize_output($of['setor'] ?: $of['divisao_sigla']) ?> - SARAM: <?= sanitize_output($of['saram']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">Apenas Oficiais da FAB podem realizar a avaliação na Fase 1.</div>
                    </div>

                    <div class="mb-3">
                        <label for="delegar_motivo" class="form-label fw-bold text-dark small">Motivo da Delegação / Afastamento <span class="text-danger">*</span>:</label>
                        <textarea name="motivo" id="delegar_motivo" class="form-control" rows="3" placeholder="Ex: Chefe imediato em missão operacional na região Amazônica..." required minlength="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Confirmar Delegação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalDelegar = document.getElementById('modalDelegarOficial');
    if (modalDelegar) {
        modalDelegar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            const candId = button.getAttribute('data-candidato-id');
            const candNome = button.getAttribute('data-candidato-nome');
            const candSub = button.getAttribute('data-candidato-sub');
            const motivo = button.getAttribute('data-delegacao-motivo') || '';
            const delegadoId = button.getAttribute('data-delegado-id') || '';

            document.getElementById('delegar_candidato_id').value = candId || '';
            document.getElementById('delegar_candidato_nome').textContent = candNome || '--';
            document.getElementById('delegar_candidato_sub').textContent = candSub || '';
            document.getElementById('delegar_motivo').value = motivo;
            if (delegadoId) {
                document.getElementById('delegar_oficial_id').value = delegadoId;
            } else {
                document.getElementById('delegar_oficial_id').value = '';
            }
        });
    }
});
</script>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
