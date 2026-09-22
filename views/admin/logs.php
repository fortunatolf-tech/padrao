<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 fw-bold text-primary mb-1">Trilha de Auditoria Perene do Sistema (5 Anos de Retenção)</h2>
        <div class="text-muted small">Registros imutáveis com encadeamento de hash SHA-256 e controle rigoroso de integridade.</div>
    </div>
    <span class="badge bg-dark p-2 fs-6"><i class="bi bi-shield-check me-1"></i> Logs Auditáveis</span>
</div>

<div class="card card-comara shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2">
            <input type="hidden" name="r" value="admin/logs">
            <div class="col-md-10">
                <input type="text" name="termo" class="form-control" placeholder="Buscar por Ação, Usuário, IP ou Conteúdo..." value="<?= sanitize_output($termo) ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-search me-1"></i> Pesquisar</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-comara shadow-sm">
    <div class="card-comara-header">
        <span>Histórico de Operações Registradas (<?= count($logs) ?> registros exibidos)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 font-monospace" style="font-size: 0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th style="width: 150px;">Data / Hora</th>
                        <th>Usuário</th>
                        <th>IP Origem</th>
                        <th>Ação Auditada</th>
                        <th>Detalhes / Payload</th>
                        <th>Hash SHA-256 de Integridade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center p-4 text-muted font-sans-serif">Nenhum log encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td><code>#<?= $l['id'] ?></code></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></td>
                                <td>
                                    <strong><?= sanitize_output($l['login'] ?: 'Anônimo/Urna') ?></strong>
                                    <?php if ($l['usuario_id']): ?>
                                        <small class="text-muted">(ID: <?= $l['usuario_id'] ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= sanitize_output($l['ip']) ?></code></td>
                                <td>
                                    <?php
                                    $badge = 'bg-secondary';
                                    if (str_contains($l['acao'], 'VOTO')) $badge = 'bg-success';
                                    if (str_contains($l['acao'], 'HOMOL')) $badge = 'bg-primary';
                                    if (str_contains($l['acao'], 'EXCLU')) $badge = 'bg-danger';
                                    if (str_contains($l['acao'], 'TACF')) $badge = 'bg-info text-dark';
                                    if (str_contains($l['acao'], 'SELEC')) $badge = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= sanitize_output($l['acao']) ?></span>
                                </td>
                                <td style="max-width: 350px;" class="text-truncate" title="<?= sanitize_output($l['detalhes_json']) ?>">
                                    <?= sanitize_output($l['detalhes_json'] ?: '--') ?>
                                </td>
                                <td style="max-width: 140px;" class="text-truncate text-muted" title="<?= $l['hash_registro'] ?>">
                                    <code><?= substr($l['hash_registro'], 0, 16) ?>...</code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
