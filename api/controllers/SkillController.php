<?php
require_once __DIR__ . '/../config/database.php';

class SkillController {
    private $db;
    private $table = "skills";

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
        $query = "SELECT id, category_id, name, strength, sort_order FROM " . $this->table . " ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($skills as &$skill) {
            $skill['id'] = (string)$skill['id'];
            $skill['category_id'] = (string)$skill['category_id'];
            $skill['strength'] = (string)$skill['strength'];
            $skill['sort_order'] = (string)$skill['sort_order'];
        }
        
        echo json_encode($skills);
    }

    private function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['category_id']) || empty($data['name']) || !isset($data['strength'])) {
            http_response_code(400);
            echo json_encode(["message" => "Category ID, name, and strength are required."]);
            return;
        }

        $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;

        $query = "INSERT INTO " . $this->table . " (category_id, name, strength, sort_order) VALUES (:category_id, :name, :strength, :sort_order)";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':strength', $data['strength'], PDO::PARAM_INT);
        $stmt->bindParam(':sort_order', $sort_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["message" => "Skill created successfully.", "id" => (string)$this->db->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create skill."]);
        }
    }

    private function update() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id']) || empty($data['category_id']) || empty($data['name']) || !isset($data['strength'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID, category ID, name, and strength are required."]);
            return;
        }

        $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;

        $query = "UPDATE " . $this->table . " SET category_id = :category_id, name = :name, strength = :strength, sort_order = :sort_order WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':strength', $data['strength'], PDO::PARAM_INT);
        $stmt->bindParam(':sort_order', $sort_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Skill updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update skill."]);
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
            echo json_encode(["message" => "Skill deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete skill."]);
        }
    }
}