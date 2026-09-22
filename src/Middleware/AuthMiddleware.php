<?php
declare(strict_types=1);

require_once __DIR__ . '/../Auth/AuthManager.php';

/**
 * Middleware para exigir usuário autenticado
 */
class AuthMiddleware {
    public static function handle(): void {
        if (!AuthManager::check()) {
            flash_message('erro', 'Você precisa fazer login para acessar esta página.', 'danger');
            header('Location: /index.php?r=login');
            exit;
        }
    }
}
