<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';

// Carregamento automático de variáveis do arquivo .env (se existir)
(static function() {
    $envFile = dirname(__DIR__) . '/.env';
    if (!file_exists($envFile)) {
        return;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === '') {
            continue;
        }
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
})();

/**
 * Configurações Gerais do Sistema de Votação COMARA
 */

// Ambiente: 'production' ou 'development'
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_NAME', 'Sistema de Eleição dos Padrões COMARA');
define('APP_OM', 'COMISSÃO DE AEROPORTOS DA REGIÃO AMAZÔNICA');
define('APP_VERSION', '1.0.0');

// Fuso horário oficial (Horário de Belém / Brasília)
date_default_timezone_set('America/Belem');

// Chave da Aplicação para hashes criptográficos e blind token signatures
define('APP_KEY', getenv('APP_KEY') ?: 'COMARA_PADRAO_SECURE_KEY_2026_!@#$99');

// Configurações do Banco de Dados MySQL (aaPanel / Debian 13)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_NAME', getenv('DB_NAME') ?: 'comara_votacao');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Configurações da API SIGPES CCARJ (Fotografias e Dados do Efetivo)
// Em produção na INTRAER: http://api.servicos.ccarj.intraer/sigpesApi
// Em homologação: http://api.servicos.homolog.ccarj.intraer/sigpesApi
define('SIGPES_API_BASE', getenv('SIGPES_API_BASE') ?: 'http://api.servicos.homolog.ccarj.intraer/sigpesApi');
define('SIGPES_API_TIMEOUT', 6); // Segundos de timeout para não travar telas
define('SIGPES_ENABLE_CACHE', true);

// Diretórios
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_FOTOS_PATH', PUBLIC_PATH . '/uploads/fotos');

// Configuração de Sessão Segura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    // Se estiver sob HTTPS, habilita cookie_secure
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

// Helpers Globais
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function flash_message(string $key, ?string $message = null, string $type = 'info'): ?array {
    if ($message !== null) {
        $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
        return null;
    }
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function sanitize_output(?string $data): string {
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}
