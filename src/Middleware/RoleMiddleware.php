<?php
declare(strict_types=1);

require_once __DIR__ . '/../Auth/AuthManager.php';

/**
 * Middleware para controle de acesso baseado em perfis (RBAC)
 */
class RoleMiddleware {
    public static function handle(string|array $rolesPermitidas): void {
        if (!AuthManager::check()) {
            flash_message('erro', 'Acesso restrito. Faça login.', 'danger');
            header('Location: /index.php?r=login');
            exit;
        }

        if (!AuthManager::hasRole($rolesPermitidas)) {
            flash_message('erro', 'Acesso negado: Seu perfil não possui permissão para acessar este recurso.', 'danger');
            header('Location: /index.php?r=dashboard');
            exit;
        }
    }
}
