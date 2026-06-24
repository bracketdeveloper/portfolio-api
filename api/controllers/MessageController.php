<?php
require_once __DIR__ . '/../config/database.php';

class MessageController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest($method) {
        switch ($method) {
            case 'POST':
                $this->createMessage();
                break;
            case 'GET':
                $this->getMessages();
                break;
            case 'DELETE':
                $this->deleteMessage();
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Method not allowed."]);
                break;
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

    private function deleteMessage() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID is required."]);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM messages WHERE id = :id");
        if ($stmt->execute([':id' => $data['id']])) {
            echo json_encode(["message" => "Message deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete message."]);
        }
    }
}