<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 fw-bold text-primary mb-1">Rotinas de Backup e Contingência</h2>
        <div class="text-muted small">Backups incrementais diários e cópias integrais semanais para garantia de disponibilidade e imutabilidade.</div>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="/index.php?r=admin/backups" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="acao" value="gerar">
            <input type="hidden" name="tipo" value="diario">
            <button type="submit" class="btn btn-outline-primary fw-bold shadow-sm">
                <i class="bi bi-clock-history me-1"></i> Executar Backup Diário
            </button>
        </form>

        <form method="POST" action="/index.php?r=admin/backups" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="acao" value="gerar">
            <input type="hidden" name="tipo" value="semanal">
            <button type="submit" class="btn btn-primary fw-bold shadow-sm">
                <i class="bi bi-cloud-arrow-down-fill me-1"></i> Executar Backup Semanal Completo
            </button>
        </form>
    </div>
</div>

<div class="card card-comara shadow-sm mb-4">
    <div class="card-comara-header">
        <span>Histórico de Arquivos de Backup Gerados</span>
        <span class="badge bg-secondary"><?= count($listaBackups) ?> arquivo(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tipo de Rotina</th>
                        <th>Nome do Arquivo</th>
                        <th>Tamanho</th>
                        <th>Data e Hora de Geração</th>
                        <th class="text-end">Status / Arquivamento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listaBackups)): ?>
                        <tr><td colspan="5" class="text-center p-4 text-muted">Nenhum arquivo de backup gerado até o momento. Utilize os botões acima para executar.</td></tr>
                    <?php else: ?>
                        <?php foreach ($listaBackups as $b): ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $b['tipo'] === 'semanal' ? 'bg-primary' : 'bg-secondary' ?> text-uppercase">
                                        <?= $b['tipo'] ?>
                                    </span>
                                </td>
                                <td><code><?= sanitize_output($b['nome']) ?></code></td>
                                <td><span class="badge bg-light text-dark border"><?= $b['tamanho'] ?></span></td>
                                <td><?= $b['data_hora'] ?></td>
                                <td class="text-end">
                                    <span class="text-success small fw-semibold"><i class="bi bi-shield-check"></i> Seguro no Storage</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Guia de Agendamento Automático no aaPanel (Debian 13) -->
<div class="card card-comara shadow-sm">
    <div class="card-comara-header bg-light">
        <span><i class="bi bi-terminal me-2"></i>Instruções para Agendamento no aaPanel (Cron Tab)</span>
    </div>
    <div class="card-body small text-muted">
        <p class="mb-2">No painel <strong>aaPanel -> Cron</strong> do servidor Debian 13, configure as duas tarefas automatizadas abaixo:</p>
        
        <div class="p-3 bg-dark text-light rounded font-monospace mb-3">
            # Backup Diário às 23:00 (Segunda a Domingo)<br>
            0 23 * * * /bin/bash /www/wwwroot/votacao.comara.intraer/scripts/backup_diario.sh > /dev/null 2>&1<br><br>
            # Backup Semanal Completo aos Domingos às 02:00<br>
            0 2 * * 0 /bin/bash /www/wwwroot/votacao.comara.intraer/scripts/backup_semanal.sh > /dev/null 2>&1
        </div>

        <p class="mb-0">Os scripts shell estão disponíveis na pasta <code>scripts/</code> do projeto com permissão de execução <code>chmod +x</code>.</p>
    </div>
</div>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
