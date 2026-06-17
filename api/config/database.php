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
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }

        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: "localhost";
        $this->port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: "3306";
        $this->db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: "portfolio_db";
        $this->username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: "avnadmin";
        $this->password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: "";
    }

    public function getConnection() {
        if ($this->conn) return $this->conn;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // Define SSL constants dynamically to support older and newer PHP versions
            $sslCa = class_exists('PDO\Mysql') ? \PDO\Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA;
            $sslVerify = class_exists('PDO\Mysql') ? \PDO\Mysql::ATTR_SSL_VERIFY_SERVER_CERT : PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT;

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false
            ];

            if (getenv('VERCEL') === '1') {
                $certPath = '/tmp/ca.pem';
                if (!file_exists($certPath) && isset($_ENV['DB_SSL_CERT'])) {
                    file_put_contents($certPath, $_ENV['DB_SSL_CERT']);
                }
                $options[$sslCa] = $certPath;
            } else {
                $localCert = realpath(__DIR__ . '/../../ca.pem');
                if ($localCert) {
                    $options[$sslCa] = $localCert;
                }
            }
            
            $options[$sslVerify] = true;

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(Exception $exception) {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode(["message" => "Database connection error: " . $exception->getMessage()]);
            exit();
        }
        return $this->conn;
    }
}