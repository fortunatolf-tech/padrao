<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Models/Pleito.php';
require_once __DIR__ . '/../Models/Efetivo.php';
require_once __DIR__ . '/../Models/FichaIndicacao.php';
require_once __DIR__ . '/../Models/AvaliacaoTacf.php';

class Fase1Controller {

    public function index(): void {
        AuthMiddleware::handle();

        if (!AuthManager::isOficial()) {
            flash_message('erro', 'Acesso negado: A Fase 1 (Avaliação pelos Chefes Diretos) é de prerrogativa exclusiva de Oficiais da Força Aérea Brasileira.', 'danger');
            header('Location: /index.php?r=dashboard');
            exit;
        }

        $user = AuthManager::user();
        $pleito = Pleito::getAtivo();

        if (!$pleito) {
            flash_message('erro', 'Nenhum pleito ativo no momento.', 'warning');
            header('Location: /index.php?r=dashboard');
            exit;
        }

        $subordinados = [];
        if (!empty($user['efetivo_id'])) {
            $subordinados = Efetivo::getSubordinadosDiretos((int)$user['efetivo_id'], (int)$pleito['id']);
        }

        // Todos os candidatos disponíveis para avaliação voluntária
        $todosCandidatos = Efetivo::listarCandidatosFase1((int)$pleito['id']);

        // Se for Educação Física, prepara lista de militares para TACF
        $militaresTacf = [];
        if (AuthManager::hasRole([PERFIL_ED_FISICA, PERFIL_ADMIN])) {
            $pdo = Database::getConnection();
            $sqlTacf = "
                SELECT e.*, t.nota_tacf, t.data_realizacao, t.observacoes, t.updated_at as tacf_atualizado_em
                FROM efetivo e
                LEFT JOIN avaliacoes_tacf t ON t.candidato_id = e.id AND t.pleito_id = :p
                WHERE e.categoria IN ('graduado', 'praca') AND e.ativo = 1
                ORDER BY e.categoria, e.posto, e.nome
            ";
            $stmt = $pdo->prepare($sqlTacf);
            $stmt->execute([':p' => $pleito['id']]);
            $militaresTacf = $stmt->fetchAll();
        }

        // Pendências globais para o Admin ou Direção Superior
        $verificacaoPendencias = Pleito::validarPendenciasFase1((int)$pleito['id']);

        require_once ROOT_PATH . '/views/fase1/index.php';
    }

    /**
     * Exibe o formulário digital da Ficha de Indicação a Padrão
     */
    public function avaliar(): void {
        AuthMiddleware::handle();

        if (!AuthManager::isOficial()) {
            flash_message('erro', 'Acesso negado: Apenas Oficiais da Força Aérea Brasileira possuem prerrogativa para preencher Fichas de Indicação da Fase 1.', 'danger');
            header('Location: /index.php?r=dashboard');
            exit;
        }

        $user = AuthManager::user();
        $pleito = Pleito::getAtivo();

        $candidatoId = (int)($_GET['id'] ?? 0);
        $candidato = Efetivo::getPorId($candidatoId);

        if (!$candidato) {
            flash_message('erro', 'Candidato não encontrado.', 'danger');
            header('Location: /index.php?r=fase1');
            exit;
        }

        $isSubordinado = ($candidato['chefe_direto_id'] && $candidato['chefe_direto_id'] == ($user['efetivo_id'] ?? null));
        $tipoAval = $isSubordinado ? 'OBRIGATORIA_CHEFE' : 'VOLUNTARIA';

        // Busca ficha anterior se já preenchida
        $fichaExistente = FichaIndicacao::getFicha((int)$pleito['id'], $candidatoId, (int)$user['id']);

        // Busca nota do TACF (apenas leitura aqui!)
        $tacf = AvaliacaoTacf::getNota((int)$pleito['id'], $candidatoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                flash_message('erro', 'Token CSRF inválido.', 'danger');
                header("Location: /index.php?r=fase1/avaliar&id={$candidatoId}");
                exit;
            }

            $dados = $_POST;
            $dados['pleito_id'] = $pleito['id'];
            $dados['candidato_id'] = $candidatoId;
            $dados['tipo_avaliacao'] = $tipoAval;

            $resultado = FichaIndicacao::salvarFicha($dados, (int)$user['id']);
            if ($resultado['sucesso']) {
                flash_message('sucesso', "Ficha de indicação para {$candidato['posto']} {$candidato['nome_guerra']} registrada com sucesso! (Média calculada: {$resultado['media']})", 'success');
                header('Location: /index.php?r=fase1');
                exit;
            } else {
                flash_message('erro', $resultado['mensagem'], 'danger');
            }
        }

        require_once ROOT_PATH . '/views/fase1/form_ficha.php';
    }

    /**
     * Lançamento exclusivo de TACF pelo Setor de Educação Física
     */
    public function salvarTacf(): void {
        AuthMiddleware::handle();
        RoleMiddleware::handle([PERFIL_ED_FISICA, PERFIL_ADMIN]);

        $user = AuthManager::user();
        $pleito = Pleito::getAtivo();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                flash_message('erro', 'Token CSRF inválido.', 'danger');
                header('Location: /index.php?r=fase1');
                exit;
            }

            $candidatoId = (int)($_POST['candidato_id'] ?? 0);
            $notaTacf = (float)str_replace(',', '.', $_POST['nota_tacf'] ?? '0');
            $dataRealizacao = $_POST['data_realizacao'] ?? date('Y-m-d');
            $obs = trim($_POST['observacoes'] ?? '');

            $res = AvaliacaoTacf::salvarNota(
                (int)$pleito['id'],
                $candidatoId,
                $notaTacf,
                (int)$user['id'],
                $obs,
                $dataRealizacao
            );

            if ($res['sucesso']) {
                flash_message('sucesso', $res['mensagem'], 'success');
            } else {
                flash_message('erro', $res['mensagem'], 'danger');
            }
        }

        header('Location: /index.php?r=fase1');
        exit;
    }
}
