<?php
declare(strict_types=1);
require_once ROOT_PATH . '/views/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h4 fw-bold text-primary mb-1">Gestão de Fotografias dos Candidatos</h2>
        <div class="text-muted small">Integração nativa com a API SIGPES CCARJ com opção de upload manual para contingência ou servidores civis.</div>
    </div>
</div>

<div class="card card-comara shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="/index.php" class="row g-2 align-items-center">
            <input type="hidden" name="r" value="admin/fotos">

            <div class="col-md-4">
                <select name="cat" class="form-select" onchange="this.form.submit()">
                    <?php foreach (CATEGORIAS_PADRAO as $ck => $ci): ?>
                        <option value="<?= $ck ?>" <?= ($categoria === $ck) ? 'selected' : '' ?>><?= $ci['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <input type="text" name="q" class="form-control" placeholder="Buscar por Nome de Guerra, Nome Completo ou SARAM..." value="<?= sanitize_output($termo) ?>">
            </div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-search me-1"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-comara shadow-sm">
    <div class="card-comara-header">
        <span>Listagem de Candidatos — <?= CATEGORIAS_PADRAO[$categoria]['nome'] ?> (<?= count($candidatos) ?> integrantes)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Foto Atual</th>
                        <th>Candidato</th>
                        <th>Posto / Especialidade</th>
                        <th>SARAM / Matrícula</th>
                        <th>Setor</th>
                        <th>Foto Customizada Manual</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($candidatos)): ?>
                        <tr><td colspan="7" class="text-center p-4 text-muted">Nenhum candidato encontrado com os filtros aplicados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($candidatos as $c): ?>
                            <tr>
                                <td>
                                    <img src="/index.php?r=foto&saram=<?= $c['saram'] ?>&id=<?= $c['id'] ?>" class="cand-photo-sm" alt="Foto">
                                </td>
                                <td>
                                    <strong><?= sanitize_output($c['posto']) ?> <?= sanitize_output($c['nome_guerra']) ?></strong><br>
                                    <small class="text-muted"><?= sanitize_output($c['nome']) ?></small>
                                </td>
                                <td><?= sanitize_output($c['quadro']) ?> - <?= sanitize_output($c['especialidade']) ?></td>
                                <td><code><?= sanitize_output($c['saram'] ?: 'Civil') ?></code></td>
                                <td><?= sanitize_output($c['setor']) ?> (<?= sanitize_output($c['divisao_sigla']) ?>)</td>
                                <td>
                                    <?php if ($c['foto_custom']): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Manual (<?= $c['foto_custom'] ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Padrão API SIGPES</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-primary btn-sm fw-bold"
                                            data-bs-toggle="modal" data-bs-target="#modalUploadFoto"
                                            data-candidato-id="<?= $c['id'] ?>"
                                            data-candidato-nome="<?= sanitize_output($c['posto'] . ' ' . $c['nome_guerra']) ?>"
                                            data-candidato-saram="<?= sanitize_output($c['saram']) ?>">
                                        <i class="bi bi-upload me-1"></i> Alterar Foto Manual
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Upload Manual de Fotografia -->
<div class="modal fade" id="modalUploadFoto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/index.php?r=admin/upload_foto" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="candidato_id" id="modal_foto_cand_id">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-camera-fill me-2"></i>Upload Manual de Fotografia</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-bold d-block">Candidato Selecionado:</label>
                        <div class="fs-5 fw-bold text-primary" id="modal_foto_cand_nome">--</div>
                    </div>

                    <div class="alert alert-info small">
                        <strong>Dica:</strong> Esta fotografia substituirá a imagem da API SIGPES na Urna Eletrônica, na Ficha de Indicação e na Placa Alusiva do Comando. Formatos suportados: JPG, PNG ou WebP.
                    </div>

                    <div class="mb-3">
                        <label for="inputFoto" class="form-label fw-bold">Selecione o arquivo de imagem <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="inputFoto" name="foto" accept="image/jpeg,image/png,image/webp" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold"><i class="bi bi-cloud-arrow-up-fill me-1"></i> Salvar Fotografia</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalUpload = document.getElementById('modalUploadFoto');
    if (modalUpload) {
        modalUpload.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-candidato-id');
            const nome = button.getAttribute('data-candidato-nome');
            document.getElementById('modal_foto_cand_id').value = id;
            document.getElementById('modal_foto_cand_nome').textContent = nome;
        });
    }
});
</script>

<?php require_once ROOT_PATH . '/views/layouts/footer.php'; ?>
