<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-md-5 col-lg-4">
        <div class="card card-comara shadow-sm border">
            <div class="card-comara-header text-center justify-content-center py-4 bg-light border-bottom">
                <div class="text-center">
                    <img src="/assets/img/comara_brasao.png" alt="COMARA" style="width: 80px; height: auto;" class="mb-2">
                    <h5 class="mb-1 text-primary fw-bold">Eleição dos Padrões</h5>
                    <div class="text-muted small">Autenticação do Efetivo COMARA</div>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="/index.php?r=login">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="mb-3">
                        <label for="login" class="form-label fw-semibold">SARAM ou CPF</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-person-fill text-muted"></i></span>
                            <input type="text" class="form-control" id="login" name="login" required autofocus placeholder="Digite seu SARAM ou CPF">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="senha" class="form-label fw-semibold">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-shield-lock-fill text-muted"></i></span>
                            <input type="password" class="form-control" id="senha" name="senha" required placeholder="Digite sua senha">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Entrar
                    </button>
                </form>

                <div class="text-center mt-3 pt-3 border-top">
                    <button type="button" class="btn btn-link btn-sm text-decoration-none text-muted" data-bs-toggle="modal" data-bs-target="#modalResetSenhaLogin">
                        <i class="bi bi-key-fill text-warning me-1"></i> Esqueceu a senha? Resetar para <strong>padrao@2026</strong>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reset de Senha na Tela de Login -->
<div class="modal fade" id="modalResetSenhaLogin" tabindex="-1" aria-labelledby="modalResetSenhaLoginLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-warning-subtle text-dark">
                <h5 class="modal-title fw-bold" id="modalResetSenhaLoginLabel">
                    <i class="bi bi-key-fill text-warning me-2"></i> Redefinir Senha de Acesso
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" action="/index.php?r=reset_senha_login">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Informe o seu <strong>SARAM</strong> ou <strong>CPF</strong>. Ao confirmar, sua senha será restaurada para a senha padrão da COMARA: <code class="fw-bold text-dark">padrao@2026</code>.
                    </p>
                    <div class="mb-3">
                        <label for="identificador_reset" class="form-label fw-semibold">SARAM ou CPF</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-person-badge text-muted"></i></span>
                            <input type="text" class="form-control" id="identificador_reset" name="identificador" required autofocus placeholder="Digite seu SARAM ou CPF">
                        </div>
                    </div>
                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle me-1"></i> Após a confirmação, acesse com o seu usuário e a senha <strong>padrao@2026</strong>.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Resetar para padrao@2026
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
