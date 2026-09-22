<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <a href="/index.php?r=fase5" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="bi bi-arrow-left"></i> Voltar à Fase 5
        </a>
        <h3 class="h4 fw-bold text-primary mb-0">Placa Alusiva Oficial — Hall de Entrada do Comando</h3>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary fw-bold" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Imprimir Placa em Alta Resolução
        </button>
    </div>
</div>

<!-- PLACA ALUSIVA EMULADA (PADRÃO BRONZE / OURO DA AERONÁUTICA) -->
<div class="placa-hall my-4" id="placaPrint">
    
    <!-- Topo da Placa -->
    <div class="text-center mb-4 border-bottom border-warning pb-3">
        <img src="/assets/img/comara_brasao.png" alt="Brasão COMARA" style="width: 95px; height: auto;" class="mb-2">
        <div class="text-uppercase fw-bold text-warning small" style="letter-spacing: 2px;">Comando da Aeronáutica • Diretoria de Infraestrutura</div>
        <h2 class="h3 fw-bold text-white text-uppercase mb-1" style="letter-spacing: 1px;">Comissão de Aeroportos da Região Amazônica</h2>
        <h4 class="text-warning text-uppercase fw-bold mb-0" style="letter-spacing: 3px;">
            ★ PADRÕES DO ANO <?= $pleito['ano'] ?> ★
        </h4>
        <small class="text-light opacity-75 d-block mt-1">
            "Homenagem do Comando e do Efetivo da COMARA aos Militares e Servidores Civis que se destacaram pelo Excepcional Desempenho e Espírito de Corpo"
        </small>
    </div>

    <!-- Os 4 Quadros com Fotografias e Dados dos Eleitos -->
    <div class="row g-4 my-2">
        <?php foreach (CATEGORIAS_PADRAO as $catKey => $catInfo): ?>
            <?php
            $dadosCat = $apuracao['categorias'][$catKey] ?? null;
            $vencedor = $dadosCat['vencedor'] ?? null;
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="placa-frame">
                    <span class="badge bg-primary text-uppercase px-2 py-1 mb-2 d-inline-block" style="font-size: 0.72rem;">
                        <?= $catInfo['nome'] ?>
                    </span>

                    <div class="my-2">
                        <?php if ($vencedor): ?>
                            <img src="/index.php?r=foto&saram=<?= $vencedor['saram'] ?>&id=<?= $vencedor['candidato_id'] ?>" 
                                 class="shadow" alt="Foto Oficial">
                        <?php else: ?>
                            <div class="bg-light p-4 rounded text-muted">Aguardando Eleito</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($vencedor): ?>
                        <h5 class="fw-bold text-primary mb-1">
                            <?= sanitize_output($vencedor['posto']) ?> <?= sanitize_output($vencedor['nome_guerra']) ?>
                        </h5>
                        <div class="small fw-semibold text-dark text-truncate" title="<?= sanitize_output($vencedor['nome']) ?>">
                            <?= sanitize_output($vencedor['nome']) ?>
                        </div>
                        <div class="small text-secondary mb-1">
                            <?= sanitize_output($vencedor['setor']) ?> • <?= sanitize_output($vencedor['divisao_sigla']) ?>
                        </div>
                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                            SARAM: <?= sanitize_output($vencedor['saram'] ?: 'Civil') ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Assinaturas do Comando no Rodapé da Placa -->
    <div class="row mt-5 pt-4 text-center border-top border-warning text-white">
        <div class="col-6">
            <div class="border-top border-light d-inline-block pt-1 px-4" style="min-width: 250px;">
                <div class="fw-bold">Cel Av / Eng</div>
                <div class="small text-warning">Presidente da COMARA</div>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top border-light d-inline-block pt-1 px-4" style="min-width: 250px;">
                <div class="fw-bold">Cel Av</div>
                <div class="small text-warning">Vice-Presidente da COMARA</div>
            </div>
        </div>
        <div class="col-12 mt-3">
            <small class="text-light opacity-50" style="font-size: 0.7rem;">
                Belém - Pará • Pleito homologado em conformidade com o ROCA 21-55
            </small>
        </div>
    </div>

</div>

<style>
@media print {
    .comara-header, .comara-nav, .comara-footer, .d-print-none, .alert-dismissible {
        display: none !important;
    }
    body {
        background: #ffffff !important;
        padding: 0 !important;
    }
    .placa-hall {
        border: 4px solid #c89d2b !important;
        background: #1c2733 !important;
        color: #fff !important;
        box-shadow: none !important;
        max-width: 100% !important;
        page-break-inside: avoid;
    }
}
</style>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
