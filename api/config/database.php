<?php
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
                putenv(trim($name) . '=' . trim($value));
            }
        }

        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: "localhost";
        $this->port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: "3306";
        $this->db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: "portfolio_db";
        $this->username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: "root";
        $this->password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: "";
    }

    public function getConnection() {
        if ($this->conn) return $this->conn;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $sslCa = class_exists('\PDO\Mysql') ? \PDO\Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false
            ];

            // If connecting to TiDB Cloud (which uses SSL), use ca.pem
            if (strpos($this->host, 'tidbcloud.com') !== false) {
                $localCert = realpath(__DIR__ . '/../../ca.pem');
                if ($localCert) {
                    $options[$sslCa] = $localCert;
                }
            }

            // Vercel-specific logic
            if (getenv('VERCEL') === '1' && !empty($_ENV['DB_SSL_CERT'])) {
                $certPath = '/tmp/ca.pem';
                if (!file_exists($certPath)) {
                    file_put_contents($certPath, str_replace(['\\n', '\\r'], ["\n", ""], $_ENV['DB_SSL_CERT']));
                }
                $options[$sslCa] = $certPath;
            }

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode(["message" => "Debug: " . $exception->getMessage()]);
            exit();
        }
        return $this->conn;
    }
}