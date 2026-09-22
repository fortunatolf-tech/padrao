<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h4 fw-bold text-primary mb-1">Fase 4: Validação pela Direção Superior</h2>
        <div class="text-muted small">Acesso restrito ao Sr. Presidente e Oficiais Superiores da COMARA para verificação regimental de elegibilidade.</div>
    </div>
    <div>
        <span class="badge bg-primary p-2 fs-6"><i class="bi bi-shield-lock-fill me-1"></i> Direção Superior COMARA</span>
    </div>
</div>

<div class="alert alert-secondary d-flex align-items-center shadow-sm p-3 mb-4">
    <i class="bi bi-info-circle-fill text-primary fs-4 me-3"></i>
    <div class="small">
        <strong>Critérios de Verificação:</strong> Analise as condições regimentais dos candidatos classificados (histórico disciplinar, tempo de serviço, avaliações e compatibilidade de função). 
        Caso haja inabilitação, <strong>é obrigatório registrar a fundamentação textual detalhada</strong>, a qual será arquivada em log indelével de auditoria.
    </div>
</div>

<!-- Tabs pelas 4 Categorias -->
<ul class="nav nav-pills mb-3 gap-2" id="fase4Tabs" role="tablist">
    <?php foreach (CATEGORIAS_PADRAO as $catKey => $catInfo): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($catKey === CAT_GRADUADO) ? 'active' : '' ?> fw-bold border shadow-sm"
                    id="tab4-btn-<?= $catKey ?>" data-bs-toggle="tab" data-bs-target="#tab4-cat-<?= $catKey ?>" type="button">
                <?= $catInfo['nome'] ?>
                <span class="badge bg-secondary ms-1"><?= count($porCategoria[$catKey] ?? []) ?></span>
            </button>
        </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content">
    <?php foreach (CATEGORIAS_PADRAO as $catKey => $catInfo): ?>
        <div class="tab-pane fade <?= ($catKey === CAT_GRADUADO) ? 'show active' : '' ?>" id="tab4-cat-<?= $catKey ?>" role="tabpanel">
            <div class="card card-comara shadow-sm">
                <div class="card-comara-header">
                    <span>Candidatos Classificados — <?= $catInfo['nome'] ?></span>
                    <small class="text-muted">Análise de elegibilidade regimental</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70px;">Foto</th>
                                    <th>Candidato / SARAM</th>
                                    <th>Posto / Especialidade</th>
                                    <th>Setor</th>
                                    <th>Antiguidade COMARA / OM</th>
                                    <th>Indicações Anteriores</th>
                                    <th>Média F1 / TACF</th>
                                    <th>Status Regimental</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $cands = $porCategoria[$catKey] ?? []; ?>
                                <?php if (empty($cands)): ?>
                                    <tr><td colspan="9" class="text-center p-4 text-muted">Nenhum candidato consolidado nesta categoria.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($cands as $c): ?>
                                        <?php $isExcluido = ($c['status_validacao_superior'] === 'EXCLUIDO'); ?>
                                        <tr class="<?= $isExcluido ? 'table-danger' : '' ?>">
                                            <td>
                                                <img src="/index.php?r=foto&saram=<?= $c['saram'] ?>&id=<?= $c['candidato_id'] ?>" class="cand-photo-sm" alt="Foto">
                                            </td>
                                            <td>
                                                <strong><?= sanitize_output($c['nome_guerra']) ?></strong><br>
                                                <small class="text-muted"><?= sanitize_output($c['nome']) ?></small><br>
                                                <small class="text-secondary">SARAM: <?= sanitize_output($c['saram'] ?: 'Civil') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= sanitize_output($c['posto']) ?></span>
                                                <small class="text-muted d-block"><?= sanitize_output($c['quadro']) ?> - <?= sanitize_output($c['especialidade']) ?></small>
                                            </td>
                                            <td><?= sanitize_output($c['setor']) ?> (<?= sanitize_output($c['divisao_sigla']) ?>)</td>
                                            <td>
                                                <small class="d-block text-muted">COMARA: <strong><?= $c['data_admissao_comara'] ? date('d/m/Y', strtotime($c['data_admissao_comara'])) : '--' ?></strong></small>
                                                <small class="d-block text-muted">OM: <strong><?= $c['data_vinculo_om'] ? date('d/m/Y', strtotime($c['data_vinculo_om'])) : '--' ?></strong></small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= (int)$c['indicacoes_anteriores_qtd'] === 0 ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= (int)$c['indicacoes_anteriores_qtd'] === 0 ? 'Nunca Indicado' : $c['indicacoes_anteriores_qtd'] . ' pleito(s)' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="small">Média F1: <strong class="text-primary"><?= number_format((float)$c['media_fase1'], 2, ',', '.') ?></strong></div>
                                                <?php if ($c['nota_tacf']): ?>
                                                    <div class="small">TACF: <strong class="text-success"><?= number_format((float)$c['nota_tacf'], 2, ',', '.') ?></strong></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($isExcluido): ?>
                                                    <span class="badge bg-danger fs-6 mb-1">INABILITADO</span>
                                                    <div class="small text-danger" style="max-width: 220px;" title="<?= sanitize_output($c['justificativa_exclusao']) ?>">
                                                        <em>"<?= sanitize_output($c['justificativa_exclusao']) ?>"</em>
                                                    </div>
                                                    <small class="text-muted d-block">Por: <?= sanitize_output($c['excluidor_login'] ?: 'Oficial') ?></small>
                                                <?php else: ?>
                                                    <span class="badge bg-success fs-6"><i class="bi bi-check-circle-fill me-1"></i> HABILITADO</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($isExcluido): ?>
                                                    <form method="POST" action="/index.php?r=fase4/reverter" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="candidato_id" value="<?= $c['candidato_id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success fw-bold">
                                                            <i class="bi bi-arrow-counterclockwise"></i> Reabilitar
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold"
                                                            data-bs-toggle="modal" data-bs-target="#modalExclusao"
                                                            data-candidato-id="<?= $c['candidato_id'] ?>"
                                                            data-candidato-nome="<?= sanitize_output($c['posto'] . ' ' . $c['nome_guerra']) ?>">
                                                        <i class="bi bi-x-circle me-1"></i> Inabilitar
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal de Inabilitação com Justificativa Textual Obrigatória -->
<div class="modal fade" id="modalExclusao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/index.php?r=fase4/excluir">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="candidato_id" id="excluir_candidato_id">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Inabilitar Candidato Regimentalmente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Candidato Selecionado:</label>
                        <div class="fs-5 fw-bold text-dark" id="excluir_candidato_nome">--</div>
                    </div>

                    <div class="alert alert-warning small">
                        <strong>Aviso de Auditoria:</strong> A exclusão de um candidato nesta fase exige motivação legal/regimental explícita. O texto digitado será permanentemente gravado com seu usuário e timestamp.
                    </div>

                    <div class="mb-3">
                        <label for="justificativa_exclusao" class="form-label fw-bold">Justificativa Textual Obrigatória <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="justificativa_exclusao" name="justificativa" rows="4" required minlength="15"
                                  placeholder="Fundamente o impedimento regimental (ex: Punição disciplinar recente, não atendimento ao tempo mínimo de OM, incompatibilidade regimental)..."></textarea>
                        <div class="form-text">Mínimo de 15 caracteres fundamentando o motivo da exclusão.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-slash-circle me-1"></i> Confirmar Inabilitação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
