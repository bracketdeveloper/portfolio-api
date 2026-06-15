<?php
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Automatically parse local .env file if it exists
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }

        $this->host = isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : "localhost";
        $this->port = isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : "3306";
        $this->db_name = isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : "portfolio_admin";
        $this->username = isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : "root";
        $this->password = isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : "";
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ]);
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode(["message" => "Database connection error: " . $exception->getMessage()]);
            exit();
        }
        return $this->conn;
    }
}