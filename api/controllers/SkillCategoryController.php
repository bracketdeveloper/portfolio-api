<?php
require_once __DIR__ . '/../config/database.php';

class SkillCategoryController {
    private $db;
    private $table = "skill_categories";

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
        $query = "SELECT id, name, sort_order FROM " . $this->table . " ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
        http_response_code(200);
        echo json_encode($categories);
    }

    private function create() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(["message" => "Category name is required."]);
            return;
        }

        $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;

        $query = "INSERT INTO " . $this->table . " (name, sort_order) VALUES (:name, :sort_order)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':sort_order', $sort_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["message" => "Category created successfully.", "id" => $this->db->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create category."]);
        }
    }

    private function update() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id']) || empty($data['name'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID and name are required for updates."]);
            return;
        }

        $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;

        $query = "UPDATE " . $this->table . " SET name = :name, sort_order = :sort_order WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':sort_order', $sort_order, PDO::PARAM_INT);
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(["message" => "Category updated successfully."]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Category not found or no changes made."]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update category."]);
        }
    }

    private function delete() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID is required for deletion."]);
            return;
        }

        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(["message" => "Category deleted successfully."]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Category not found."]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete category."]);
        }
    }
}