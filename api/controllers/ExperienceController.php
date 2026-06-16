<?php
require_once __DIR__ . '/../config/database.php';

class ExperienceController {
    private $db;
    private $table = "experiences";

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest($method) {
        switch ($method) {
            case 'GET':
                $this->getAll();
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

    private function getAll() {
        $query = "SELECT id, role, company, location, period, bullets, tech_array, sort_order FROM " . $this->table . " ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($experiences as &$exp) {
            $exp['id'] = (string)$exp['id'];
            $exp['sort_order'] = (string)$exp['sort_order'];
            $exp['bullets'] = json_decode($exp['bullets'], true) ?? [];
            $exp['tech_array'] = json_decode($exp['tech_array'], true) ?? [];
        }
        
        echo json_encode($experiences);
    }

    private function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['role']) || empty($data['company']) || empty($data['location']) || empty($data['period'])) {
            http_response_code(400);
            echo json_encode(["message" => "Role, company, location, and period are required."]);
            return;
        }

        $bullets = isset($data['bullets']) ? json_encode($data['bullets']) : json_encode([]);
        $tech_array = isset($data['tech_array']) ? json_encode($data['tech_array']) : json_encode([]);
        $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;

        $query = "INSERT INTO " . $this->table . " (role, company, location, period, bullets, tech_array, sort_order) VALUES (:role, :company, :location, :period, :bullets, :tech_array, :sort_order)";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':company', $data['company']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':period', $data['period']);
        $stmt->bindParam(':bullets', $bullets);
        $stmt->bindParam(':tech_array', $tech_array);
        $stmt->bindParam(':sort_order', $sort_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["message" => "Experience created successfully.", "id" => (string)$this->db->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create experience."]);
        }
    }

    private function update() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id']) || empty($data['role']) || empty($data['company']) || empty($data['location']) || empty($data['period'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID, role, company, location, and period are required."]);
            return;
        }

        $bullets = isset($data['bullets']) ? json_encode($data['bullets']) : json_encode([]);
        $tech_array = isset($data['tech_array']) ? json_encode($data['tech_array']) : json_encode([]);
        $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;

        $query = "UPDATE " . $this->table . " SET role = :role, company = :company, location = :location, period = :period, bullets = :bullets, tech_array = :tech_array, sort_order = :sort_order WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':company', $data['company']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':period', $data['period']);
        $stmt->bindParam(':bullets', $bullets);
        $stmt->bindParam(':tech_array', $tech_array);
        $stmt->bindParam(':sort_order', $sort_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Experience updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update experience."]);
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
            echo json_encode(["message" => "Experience deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete experience."]);
        }
    }
}