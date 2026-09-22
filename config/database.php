<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Gerenciador Singleton de Conexão PDO MySQL
 * Otimizado para PHP 8.3 e MySQL / MariaDB no Debian 13 (aaPanel)
 */
class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT         => false,
                PDO::ATTR_TIMEOUT            => 5,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                // Compatibilidade com MySQL 8.0/8.4 (desativa ONLY_FULL_GROUP_BY mantendo integridade)
                self::$instance->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            } catch (PDOException $e) {
                // Não expor credenciais em tela
                error_log('[DB ERROR] Falha na conexão com MySQL: ' . $e->getMessage());
                die('<div style="font-family:sans-serif;padding:30px;background:#f8d7da;color:#842029;border:1px solid #f5c2c7;border-radius:6px;max-width:700px;margin:50px auto;">' .
                    '<h3>Erro de Conexão com o Banco de Dados</h3>' .
                    '<p>Não foi possível conectar ao banco de dados MySQL do Sistema de Votação da COMARA.</p>' .
                    '<p><small>Verifique se o serviço MySQL está rodando no Debian/aaPanel e se as credenciais em <code>config/config.php</code> ou variáveis de ambiente estão corretas.</small></p>' .
                    '</div>');
            }
        }

        return self::$instance;
    }
}
