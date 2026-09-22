<?php
/**
 * INSTALADOR AUTOMATIZADO DO BANCO DE DADOS - COMARA
 * 
 * Pode ser executado via CLI:
 *   php database/instalar.php
 * 
 * Ou pelo navegador no primeiro deploy:
 *   http://seu-ip-ou-dominio/database/instalar.php
 */

declare(strict_types=1);

// Permitir execução via CLI ou Web
$isCli = (php_sapi_name() === 'cli');

require_once dirname(__DIR__) . '/config/config.php';

function output_msg(string $msg, string $type = 'info', bool $isCli = true): void {
    if ($isCli) {
        $prefix = match($type) {
            'success' => '[OK] ',
            'error'   => '[ERRO] ',
            'warning' => '[AVISO] ',
            default   => '[INFO] '
        };
        echo $prefix . $msg . PHP_EOL;
    } else {
        $color = match($type) {
            'success' => '#198754',
            'error'   => '#dc3545',
            'warning' => '#ffc107',
            default   => '#0d6efd'
        };
        echo "<div style='font-family:sans-serif;margin:8px 0;padding:10px 14px;border-radius:6px;background:#f8f9fa;border-left:5px solid {$color};'><strong>" . htmlspecialchars($msg) . "</strong></div>";
    }
}

if (!$isCli) {
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>Instalação do Banco COMARA</title></head><body style="max-width:800px;margin:40px auto;padding:20px;background:#f0f2f5;">';
    echo '<div style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);">';
    echo '<h2 style="color:#003366;margin-top:0;">Instalador Automático - Sistema de Eleição COMARA</h2>';
}

output_msg("Iniciando rotina de instalação do banco de dados...", 'info', $isCli);
output_msg("Host: " . DB_HOST . ":" . DB_PORT . " | Banco: " . DB_NAME . " | Usuário: " . DB_USER, 'info', $isCli);

try {
    // 1. Conectar ao servidor MySQL sem especificar banco para garantir que exista
    $dsnNoDb = sprintf('mysql:host=%s;port=%d;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
    $pdo = new PDO($dsnNoDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 15,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    // 2. Cria o banco se não existir
    $dbName = DB_NAME;
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    output_msg("Banco de dados '{$dbName}' validado/criado com sucesso.", 'success', $isCli);

    // 3. Executar o dump completo (carga_inicial_443.sql) se existir, ou schema + seeders
    $cargaFile = __DIR__ . '/carga_inicial_443.sql';
    if (file_exists($cargaFile)) {
        output_msg("Importando carga completa com 443 membros e estrutura oficial...", 'info', $isCli);
        $sql = file_get_contents($cargaFile);
        if (str_starts_with($sql, "\xEF\xBB\xBF")) {
            $sql = substr($sql, 3);
            @file_put_contents($cargaFile, $sql);
        }
        $pdo->exec($sql);
        output_msg("Carga inicial executada com sucesso.", 'success', $isCli);
    } else {
        output_msg("Importando schema.sql...", 'info', $isCli);
        $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($schemaSql);

        output_msg("Importando seeders.sql...", 'info', $isCli);
        $seedersSql = file_get_contents(__DIR__ . '/seeders.sql');
        $pdo->exec($seedersSql);
    }

    // 4. Garantir que o usuário root admin exista com senha padrao@2026
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE login = 'admin' LIMIT 1");
    $stmt->execute();
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $adminHash = password_hash('padrao@2026', PASSWORD_DEFAULT);

    if ($adminUser) {
        $upd = $pdo->prepare("UPDATE usuarios SET senha_hash = :senha, perfil = 'ADMIN', ativo = 1 WHERE login = 'admin'");
        $upd->execute(['senha' => $adminHash]);
        output_msg("Conta mestre 'admin' atualizada com a senha padrao@2026.", 'success', $isCli);
    } else {
        $ins = $pdo->prepare("INSERT INTO usuarios (login, nome_completo, perfil, senha_hash, ativo) VALUES ('admin', 'Administrador Geral COMARA', 'ADMIN', :senha, 1)");
        $ins->execute(['senha' => $adminHash]);
        output_msg("Conta mestre 'admin' criada com a senha padrao@2026.", 'success', $isCli);
    }

    // 5. Totalizador de verificação
    $totalEfetivo = $pdo->query("SELECT COUNT(*) FROM efetivo")->fetchColumn();
    $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $totalPleitos  = $pdo->query("SELECT COUNT(*) FROM pleitos")->fetchColumn();

    output_msg("Total de registros no Efetivo: {$totalEfetivo}", 'info', $isCli);
    output_msg("Total de Usuários habilitados: {$totalUsuarios}", 'info', $isCli);
    output_msg("Total de Pleitos cadastrados: {$totalPleitos}", 'info', $isCli);

    output_msg("INSTALAÇÃO FINALIZADA COM SUCESSO!", 'success', $isCli);
    output_msg("Credenciais iniciais do Administrador: login: admin | senha: padrao@2026", 'warning', $isCli);

    if (!$isCli) {
        echo '<div style="margin-top:20px;text-align:center;">';
        echo '<a href="/index.php?r=auth/login" style="display:inline-block;padding:12px 24px;background:#003366;color:#fff;text-decoration:none;border-radius:5px;font-weight:bold;">Acessar Tela de Login</a>';
        echo '</div>';
    }

} catch (Throwable $e) {
    output_msg("Erro na instalação: " . $e->getMessage(), 'error', $isCli);
    if ($isCli) {
        exit(1);
    }
}

if (!$isCli) {
    echo '</div></body></html>';
}
