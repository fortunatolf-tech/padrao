<?php
declare(strict_types=1);

require_once __DIR__ . '/constants.php';

// Função auxiliar segura para leitura de configurações do ambiente (compatível com aaPanel / disable_functions)
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $val = $_ENV[$key] ?? ($_SERVER[$key] ?? (function_exists('getenv') ? getenv($key) : false));
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        if (is_string($val)) {
            $lower = strtolower(trim($val));
            return match ($lower) {
                'true', '(true)' => true,
                'false', '(false)' => false,
                'empty', '(empty)' => '',
                'null', '(null)' => null,
                default => $val
            };
        }
        return $val;
    }
}

// Polyfills para funções mb_* caso a extensão php-mbstring não esteja instalada no servidor
if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(string $string, ?string $encoding = 'UTF-8'): string {
        return strtoupper($string);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = 'UTF-8'): string {
        return strtolower($string);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = 'UTF-8'): int {
        return strlen($string);
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = 'UTF-8'): string {
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos(string $haystack, string $needle, int $offset = 0, ?string $encoding = 'UTF-8'): int|false {
        return strpos($haystack, $needle, $offset);
    }
}

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

        // Armazena de forma persistente nas superglobais
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;

        // Se a função putenv estiver liberada no PHP, registra também nela com segurança
        if (function_exists('putenv')) {
            @putenv("{$name}={$value}");
        }
    }
})();

/**
 * Configurações Gerais do Sistema de Votação COMARA
 */

// Ambiente: 'production' ou 'development'
define('APP_ENV', (string)env('APP_ENV', 'production'));
define('APP_NAME', 'Sistema de Eleição dos Padrões COMARA');
define('APP_OM', 'COMISSÃO DE AEROPORTOS DA REGIÃO AMAZÔNICA');
define('APP_VERSION', '1.0.0');

// Fuso horário oficial (Horário de Belém / Brasília)
date_default_timezone_set('America/Belem');

// Chave da Aplicação para hashes criptográficos e blind token signatures
define('APP_KEY', (string)env('APP_KEY', 'COMARA_PADRAO_SECURE_KEY_2026_!@#$99'));

// Configurações do Banco de Dados MySQL (aaPanel / Debian 13)
define('DB_HOST', (string)env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int)env('DB_PORT', 3306));
define('DB_NAME', (string)env('DB_NAME', 'comara_votacao'));
define('DB_USER', (string)env('DB_USER', 'root'));
define('DB_PASS', (string)env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// Configurações da API SIGPES CCARJ (Fotografias e Dados do Efetivo)
// Em produção na INTRAER: http://api.servicos.ccarj.intraer/sigpesApi
// Em homologação: http://api.servicos.homolog.ccarj.intraer/sigpesApi
define('SIGPES_API_BASE', (string)env('SIGPES_API_BASE', 'http://api.servicos.homolog.ccarj.intraer/sigpesApi'));
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

/**
 * Mascara o CPF para visualização segura em conformidade com a LGPD (Privacy by Default)
 * Exemplo: 123.456.789-00 -> ***.456.789-**
 */
function mascarar_cpf(?string $cpf): string {
    if (!$cpf) {
        return '';
    }
    $digits = preg_replace('/\D/', '', $cpf);
    if (strlen($digits) !== 11) {
        return sanitize_output($cpf);
    }
    return '***.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-**';
}
