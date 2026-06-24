<?php
require_once __DIR__ . '/../config/database.php';

class MessageController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest($method) {
        if ($method === 'POST') {
            $this->createMessage();
        } elseif ($method === 'GET') {
            $this->getMessages();
        } else {
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed."]);
        }
    }

    private function createMessage() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
            http_response_code(400);
            echo json_encode(["message" => "Required fields missing."]);
            return;
        }

        $query = "INSERT INTO messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':subject' => $data['subject'] ?? 'No Subject',
            ':message' => $data['message']
        ]);

        echo json_encode(["message" => "Message received."]);
    }

    private function getMessages() {
        $stmt = $this->db->query("SELECT * FROM messages ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll());
    }
}