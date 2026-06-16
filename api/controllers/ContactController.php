<?php
require_once __DIR__ . '/../config/database.php';

class ContactController {
    private $db;
    private $table = "contacts";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest($method) {
        switch ($method) {
            case 'GET':
                $this->getContact();
                break;
            case 'POST':
                $this->create();
                break;
            case 'PUT':
                $this->update();
                break;
            case 'DELETE':
                $this->delete();
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Method not allowed."]);
                break;
        }
    }

    private function getContact() {
        $query = "SELECT id, email, phone, location, github_url, linkedin_url FROM " . $this->table . " LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($contact) {
            $contact['id'] = (string)$contact['id'];
            echo json_encode($contact);
        } else {
            echo json_encode(null);
        }
    }

    private function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['email']) || empty($data['phone']) || empty($data['location'])) {
            http_response_code(400);
            echo json_encode(["message" => "Email, phone, and location are required."]);
            return;
        }

        $github_url = isset($data['github_url']) ? $data['github_url'] : "";
        $linkedin_url = isset($data['linkedin_url']) ? $data['linkedin_url'] : "";

        $query = "INSERT INTO " . $this->table . " (email, phone, location, github_url, linkedin_url) VALUES (:email, :phone, :location, :github_url, :linkedin_url)";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':github_url', $github_url);
        $stmt->bindParam(':linkedin_url', $linkedin_url);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["message" => "Contact details created successfully.", "id" => (string)$this->db->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create contact details."]);
        }
    }

    private function update() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id']) || empty($data['email']) || empty($data['phone']) || empty($data['location'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID, email, phone, and location are required."]);
            return;
        }

        $github_url = isset($data['github_url']) ? $data['github_url'] : "";
        $linkedin_url = isset($data['linkedin_url']) ? $data['linkedin_url'] : "";

        $query = "UPDATE " . $this->table . " SET email = :email, phone = :phone, location = :location, github_url = :github_url, linkedin_url = :linkedin_url WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':github_url', $github_url);
        $stmt->bindParam(':linkedin_url', $linkedin_url);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Contact details updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update contact details."]);
        }
    }

    private function delete() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID is required."]);
            return;
        }

        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Contact details deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete contact details."]);
        }
    }
}