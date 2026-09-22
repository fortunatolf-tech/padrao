<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-7 col-lg-6">
        
        <div class="card card-comara shadow-lg border-2 border-primary" id="comprovantePrint">
            <div class="card-header bg-primary text-white text-center py-4">
                <img src="/assets/img/comara_brasao.png" alt="COMARA" style="width: 70px; height: auto;" class="mb-2">
                <h4 class="mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Comissão de Aeroportos da Região Amazônica</h4>
                <div class="small text-light opacity-90">COMPROVANTE OFICIAL DE VOTAÇÃO ELETRÔNICA</div>
                <div class="badge bg-warning text-dark mt-2 px-3 py-1 fw-bold fs-6">PLEITO PADRÃO <?= $comprovante['ano'] ?></div>
            </div>

            <div class="card-body p-4">
                <div class="alert alert-success text-center py-3 mb-4">
                    <i class="bi bi-patch-check-fill fs-2 d-block mb-1"></i>
                    <strong>Voto Registrado e Computado com Sucesso!</strong>
                    <div class="small">Sua participação foi computada com garantia de sigilo absoluto.</div>
                </div>

                <div class="bg-light p-3 rounded border mb-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <span class="small text-muted d-block">Eleitor(a):</span>
                            <strong class="text-dark"><?= sanitize_output($comprovante['eleitor']) ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="small text-muted d-block">Data e Horário do Registro:</span>
                            <strong class="text-dark"><?= date('d/m/Y H:i:s', strtotime($comprovante['data_hora'])) ?></strong>
                        </div>
                        <div class="col-12 mt-3 pt-2 border-top">
                            <span class="small text-muted d-block">Código Autenticador Único:</span>
                            <code class="fs-4 fw-bold text-primary"><?= sanitize_output($comprovante['comprovante']) ?></code>
                        </div>
                    </div>
                </div>

                <div class="text-muted small border-start border-3 border-secondary ps-3 mb-4">
                    <p class="mb-1"><strong>Garantia de Sigilo e Confidencialidade:</strong></p>
                    O sistema de votação da COMARA utiliza a arquitetura de urna eletrônica cega com desacoplamento criptográfico ponta a ponta. Este comprovante atesta unicamente o exercício do voto pelo eleitor, sem permitir qualquer associação posterior entre o militar/servidor e os candidatos escolhidos nas 4 categorias.
                </div>

                <div class="d-flex justify-content-between gap-2 d-print-none">
                    <a href="/index.php?r=dashboard" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Voltar ao Painel
                    </a>
                    <button type="button" class="btn btn-primary fw-bold" onclick="window.print()">
                        <i class="bi bi-printer-fill me-1"></i> Imprimir Comprovante
                    </button>
                </div>
            </div>

            <div class="card-footer bg-light text-center small text-muted py-3">
                COMARA / DIRINFRA • Comando da Aeronáutica • Autenticidade verificável nos logs de auditoria
            </div>
        </div>

    </div>
</div>

<style>
@media print {
    .comara-header, .comara-nav, .comara-footer, .d-print-none, .alert-dismissible {
        display: none !important;
    }
    body {
        background: #fff !important;
    }
    #comprovantePrint {
        border: 2px solid #000 !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
