<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Auth/AuthManager.php';

class AuthController {

    public function login(): void {
        if (AuthManager::check()) {
            header('Location: /index.php?r=dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!verify_csrf_token($token)) {
                flash_message('erro', 'Token de segurança CSRF inválido. Tente novamente.', 'danger');
                header('Location: /index.php?r=login');
                exit;
            }

            $login = trim($_POST['login'] ?? '');
            $senha = trim($_POST['senha'] ?? '');

            $res = AuthManager::login($login, $senha);
            if ($res['sucesso']) {
                flash_message('sucesso', 'Login realizado com sucesso. Bem-vindo(a)!', 'success');
                header('Location: /index.php?r=dashboard');
                exit;
            } else {
                flash_message('erro', $res['mensagem'], 'danger');
                header('Location: /index.php?r=login');
                exit;
            }
        }

        require_once ROOT_PATH . '/views/auth/login.php';
    }

    public function logout(): void {
        AuthManager::logout();
        flash_message('sucesso', 'Sessão encerrada com segurança.', 'info');
        header('Location: /index.php?r=login');
        exit;
    }

    /**
     * Permite a qualquer usuário logado alterar sua própria senha
     * ou restaurá-la para a senha padrão (padrao@2026)
     */
    public function alterarSenha(): void {
        AuthMiddleware::handle();
        $user = AuthManager::user();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                flash_message('erro', 'Token de segurança CSRF inválido. Tente novamente.', 'danger');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?r=dashboard'));
                exit;
            }

            $pdo = Database::getConnection();
            $acao = $_POST['acao'] ?? 'alterar';

            // Opção 1: Reset imediato para a senha padrão
            if ($acao === 'reset_padrao') {
                $hashPadrao = password_hash('padrao@2026', PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = :h WHERE id = :id');
                $stmt->execute([':h' => $hashPadrao, ':id' => $user['id']]);

                AuditoriaService::log('SENHA_REDEFINIDA_PADRAO', [
                    'usuario_id' => $user['id'],
                    'login'      => $user['login']
                ]);

                flash_message('sucesso', 'Sua senha foi redefinida com sucesso para o padrão inicial: padrao@2026', 'success');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?r=dashboard'));
                exit;
            }

            // Opção 2: Definir nova senha customizada
            $senhaAtual = (string)($_POST['senha_atual'] ?? '');
            $novaSenha = (string)($_POST['nova_senha'] ?? '');
            $confirmaSenha = (string)($_POST['confirma_senha'] ?? '');

            // Busca hash atual do usuário no banco
            $stmtHash = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = :id LIMIT 1');
            $stmtHash->execute([':id' => $user['id']]);
            $hashAtual = (string)$stmtHash->fetchColumn();

            // Valida senha atual se já tiver senha definida
            if ($hashAtual !== '' && !password_verify($senhaAtual, $hashAtual)) {
                flash_message('erro', 'Senha atual incorreta. Digite sua senha atual para autorizar a alteração.', 'danger');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?r=dashboard'));
                exit;
            }

            if (strlen($novaSenha) < 6) {
                flash_message('erro', 'A nova senha deve ter no mínimo 6 caracteres.', 'warning');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?r=dashboard'));
                exit;
            }

            if ($novaSenha !== $confirmaSenha) {
                flash_message('erro', 'A confirmação de senha não confere com a nova senha digitada.', 'warning');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?r=dashboard'));
                exit;
            }

            $novoHash = password_hash($novaSenha, PASSWORD_BCRYPT);
            $stmtUp = $pdo->prepare('UPDATE usuarios SET senha_hash = :h WHERE id = :id');
            $stmtUp->execute([':h' => $novoHash, ':id' => $user['id']]);

            AuditoriaService::log('SENHA_CUSTOMIZADA_ALTERADA', [
                'usuario_id' => $user['id'],
                'login'      => $user['login']
            ]);

            flash_message('sucesso', 'Sua senha foi alterada com sucesso!', 'success');
            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/index.php?r=dashboard'));
            exit;
        }

        header('Location: /index.php?r=dashboard');
        exit;
    }

    /**
     * Permite a qualquer usuário que esqueceu a senha redefini-la
     * para padrao@2026 diretamente na tela de login informando SARAM ou CPF
     */
    public function resetSenhaPublico(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                flash_message('erro', 'Token de segurança CSRF inválido.', 'danger');
                header('Location: /index.php?r=login');
                exit;
            }

            $identificador = trim((string)($_POST['identificador'] ?? ''));
            if ($identificador === '') {
                flash_message('erro', 'Informe seu SARAM ou CPF para redefinir a senha.', 'warning');
                header('Location: /index.php?r=login');
                exit;
            }

            $pdo = Database::getConnection();
            $cpfLimpo = preg_replace('/\D/', '', $identificador);
            $cpfHash = $cpfLimpo ? hash('sha256', $cpfLimpo) : '';

            $sql = "
                SELECT u.id, u.login, e.nome_guerra, e.posto
                FROM usuarios u
                LEFT JOIN efetivo e ON e.id = u.efetivo_id
                WHERE u.login = :id 
                   OR e.saram = :id 
                   " . ($cpfHash ? "OR e.cpf_hash = :hash OR e.cpf = :id" : "") . "
                LIMIT 1
            ";
            $params = [':id' => $identificador];
            if ($cpfHash) {
                $params[':hash'] = $cpfHash;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                flash_message('erro', 'Nenhum usuário localizado com o identificador informado. Verifique seu SARAM ou CPF.', 'danger');
                header('Location: /index.php?r=login');
                exit;
            }

            $hashPadrao = password_hash('padrao@2026', PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE usuarios SET senha_hash = :h WHERE id = :id')->execute([
                ':h'  => $hashPadrao,
                ':id' => $usuario['id']
            ]);

            AuditoriaService::log('SENHA_RESETADA_TELA_LOGIN', [
                'usuario_id' => $usuario['id'],
                'login'      => $usuario['login']
            ]);

            $nomeExibicao = (!empty($usuario['posto']) ? $usuario['posto'] . ' ' : '') . ($usuario['nome_guerra'] ?: $usuario['login']);
            flash_message('sucesso', "A senha do usuário {$nomeExibicao} foi redefinida com sucesso para o padrão: padrao@2026. Você já pode autenticar.", 'success');
            header('Location: /index.php?r=login');
            exit;
        }

        header('Location: /index.php?r=login');
        exit;
    }
}
