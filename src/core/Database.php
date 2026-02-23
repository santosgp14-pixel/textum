<?php
/**
 * TEXTUM - Conexión PDO (Singleton)
 * Soporta MYSQL_URL (Railway) e individuales DB_HOST/PORT/NAME/USER/PASS
 */
class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct() {
        // Railway inyecta MYSQL_URL = mysql://user:pass@host:port/dbname
        $url = getenv('MYSQL_URL') ?: ($_ENV['MYSQL_URL'] ?? ($_SERVER['MYSQL_URL'] ?? ''));

        if ($url) {
            $p   = parse_url($url);
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $p['host'],
                $p['port'] ?? 3306,
                ltrim($p['path'], '/')
            );
            $user = $p['user'] ?? '';
            $pass = isset($p['pass']) ? urldecode($p['pass']) : '';
        } else {
            $dsn  = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            $user = DB_USER;
            $pass = DB_PASS;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $this->connection = new PDO($dsn, $user, $pass, $options);
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }
}
