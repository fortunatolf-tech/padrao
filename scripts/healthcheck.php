<?php
declare(strict_types=1);

/**
 * Healthcheck de Disponibilidade 24h e Plano de Contingência
 * Monitoramento contínuo para garantia de 99.9% de SLA
 */

header('Content-Type: application/json; charset=utf-8');

$start = microtime(true);
$checks = [];
$statusGeral = true;

// 1. Verificação do PHP 8.3+
$checks['php_version'] = [
    'versao' => PHP_VERSION,
    'ok'     => version_compare(PHP_VERSION, '8.3.0', '>=')
];
if (!$checks['php_version']['ok']) $statusGeral = false;

// 2. Conexão e Latência MySQL
try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';

    $dbStart = microtime(true);
    $pdo = Database::getConnection();
    $stmt = $pdo->query('SELECT 1');
    $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);

    $checks['mysql'] = [
        'ok'            => true,
        'latencia_ms'   => $dbLatency,
        'banco'         => DB_NAME
    ];
} catch (\Throwable $e) {
    $checks['mysql'] = [
        'ok'   => false,
        'erro' => $e->getMessage()
    ];
    $statusGeral = false;
}

// 3. Permissões de Escrita em Diretórios Críticos
$dirs = [
    'uploads' => UPLOAD_FOTOS_PATH,
    'backups' => ROOT_PATH . '/backups',
    'cache'   => ROOT_PATH . '/cache'
];

$checks['storage'] = [];
foreach ($dirs as $k => $d) {
    if (!is_dir($d)) @mkdir($d, 0775, true);
    $writable = is_writable($d);
    $checks['storage'][$k] = [
        'path'     => $d,
        'gravavel' => $writable
    ];
    if (!$writable) $statusGeral = false;
}

// 4. Espaço em Disco
$freeBytes = disk_free_space(ROOT_PATH);
$checks['disco'] = [
    'espaco_livre_mb' => round($freeBytes / (1024 * 1024), 2),
    'ok'              => ($freeBytes > 100 * 1024 * 1024) // Pelo menos 100MB livres
];
if (!$checks['disco']['ok']) $statusGeral = false;

$totalTime = round((microtime(true) - $start) * 1000, 2);

if (!$statusGeral) {
    http_response_code(503);
}

echo json_encode([
    'status'       => $statusGeral ? 'HEALTHY' : 'UNHEALTHY',
    'timestamp'    => date('c'),
    'tempo_tot_ms' => $totalTime,
    'checks'       => $checks
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
