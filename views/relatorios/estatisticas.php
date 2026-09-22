<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';

$taxaParticipacao = ($stats['total_aptos_voto'] > 0) 
    ? round(($stats['total_eleitores'] / $stats['total_aptos_voto']) * 100, 1) 
    : 0;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="/index.php?r=fase5" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <h3 class="h4 fw-bold text-primary mb-0">Relatório Consolidado de Estatísticas e Participação</h3>
    </div>
    <button class="btn btn-primary btn-sm fw-bold" onclick="window.print()">
        <i class="bi bi-printer me-1"></i> Imprimir Relatório
    </button>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card card-comara p-3 border-start border-4 border-primary">
            <div class="text-muted small fw-bold text-uppercase">Efetivo Total COMARA</div>
            <div class="h2 fw-bold text-primary mb-0"><?= $stats['total_efetivo'] ?></div>
            <small class="text-muted">Militares e Servidores Civis</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-comara p-3 border-start border-4 border-success">
            <div class="text-muted small fw-bold text-uppercase">Eleitores que Votaram</div>
            <div class="h2 fw-bold text-success mb-0"><?= $stats['total_eleitores'] ?></div>
            <small class="text-success fw-bold"><?= $taxaParticipacao ?>% de participação</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-comara p-3 border-start border-4 border-info">
            <div class="text-muted small fw-bold text-uppercase">Cédulas na Urna Cega</div>
            <div class="h2 fw-bold text-info mb-0"><?= $stats['total_votos_cedulas'] ?></div>
            <small class="text-muted">4 categorias x <?= $stats['total_eleitores'] ?> votos</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-comara p-3 border-start border-4 border-warning">
            <div class="text-muted small fw-bold text-uppercase">Fichas de Avaliação (F1)</div>
            <div class="h2 fw-bold text-warning mb-0"><?= $stats['total_fichas_fase1'] ?></div>
            <small class="text-muted">Critérios 1 a 5 registrados</small>
        </div>
    </div>
</div>

<div class="card card-comara shadow-sm">
    <div class="card-comara-header">
        <span><i class="bi bi-shield-check me-2 text-success"></i>Certificado de Conformidade Regimental e Integridade Criptográfica</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <h6 class="fw-bold text-dark border-bottom pb-2">Parâmetros de Segurança do Pleito</h6>
                <ul class="list-unstyled small text-muted">
                    <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> <strong>Banco de Dados:</strong> MySQL 8 / MariaDB (InnoDB Strict UTF8MB4)</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> <strong>Anonimização da Urna:</strong> Protocolo Cego com tabela desacoplada sem chaves</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> <strong>Unicidade por CPF:</strong> Hashing SHA-256 e validação matemática de dígitos</li>
                    <li class="mb-2"><i class="bi bi-check2 text-success me-1"></i> <strong>Retenção de Auditoria:</strong> 5 anos com encadeamento de hash anti-adulteração</li>
                </ul>
            </div>
            <div class="col-md-6 border-start ps-md-4">
                <h6 class="fw-bold text-dark border-bottom pb-2">Resumo das 5 Fases Regimentais</h6>
                <ol class="small text-muted ps-3 mb-0">
                    <li class="mb-1"><strong>Fase 1:</strong> Avaliação obrigatória de subordinados e TACF Ed. Física concluídos.</li>
                    <li class="mb-1"><strong>Fase 2:</strong> Seleção restrita de 6 candidatos por categoria pelas Divisões e Assessorias.</li>
                    <li class="mb-1"><strong>Fase 3:</strong> Votação Geral do Efetivo em turno único.</li>
                    <li class="mb-1"><strong>Fase 4:</strong> Validação de elegibilidade pela Direção Superior.</li>
                    <li><strong>Fase 5:</strong> Apuração com desempates automáticos, minerva e homologação.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
