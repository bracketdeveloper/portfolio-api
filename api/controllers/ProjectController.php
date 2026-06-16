<?php
require_once __DIR__ . '/../config/database.php';

class ProjectController {
    private $db;
    private $table = "projects";

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
        // Updated query using category_id and LEFT JOIN to prevent 1054 error
        $query = "SELECT p.id, p.title, p.category_id, pc.name AS category_name, p.description, p.tech_array, p.github, p.demo, p.challenge, p.solution, p.metrics, p.sort_order 
                  FROM " . $this->table . " p
                  LEFT JOIN project_categories pc ON p.category_id = pc.id
                  ORDER BY p.sort_order ASC";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($projects as &$project) {
            $project['id'] = (string)$project['id'];
            $project['category_id'] = (string)$project['category_id'];
            $project['sort_order'] = (string)$project['sort_order'];
            $project['tech_array'] = json_decode($project['tech_array'], true) ?? [];
            $project['metrics'] = json_decode($project['metrics'], true) ?? [];
        }
        
        echo json_encode($projects);
    }

    private function create() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['title']) || empty($data['category_id']) || empty($data['description'])) {
            http_response_code(400);
            echo json_encode(["message" => "Title, category_id, and description are required."]);
            return;
        }

        $tech_array = isset($data['tech_array']) ? json_encode($data['tech_array']) : json_encode([]);
        $metrics = isset($data['metrics']) ? json_encode($data['metrics']) : json_encode([]);
        $github = isset($data['github']) ? $data['github'] : "";
        $demo = isset($data['demo']) ? $data['demo'] : "";
        $challenge = isset($data['challenge']) ? $data['challenge'] : "";
        $solution = isset($data['solution']) ? $data['solution'] : "";
        $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;

        $query = "INSERT INTO " . $this->table . " (title, category_id, description, tech_array, github, demo, challenge, solution, metrics, sort_order) VALUES (:title, :category_id, :description, :tech_array, :github, :demo, :challenge, :solution, :metrics, :sort_order)";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':tech_array', $tech_array);
        $stmt->bindParam(':github', $github);
        $stmt->bindParam(':demo', $demo);
        $stmt->bindParam(':challenge', $challenge);
        $stmt->bindParam(':solution', $solution);
        $stmt->bindParam(':metrics', $metrics);
        $stmt->bindParam(':sort_order', $sort_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["message" => "Project created successfully.", "id" => (string)$this->db->lastInsertId()]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to create project."]);
        }
    }

    private function update() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['id']) || empty($data['title']) || empty($data['category_id']) || empty($data['description'])) {
            http_response_code(400);
            echo json_encode(["message" => "ID, title, category_id, and description are required."]);
            return;
        }

        $tech_array = isset($data['tech_array']) ? json_encode($data['tech_array']) : json_encode([]);
        $metrics = isset($data['metrics']) ? json_encode($data['metrics']) : json_encode([]);
        $github = isset($data['github']) ? $data['github'] : "";
        $demo = isset($data['demo']) ? $data['demo'] : "";
        $challenge = isset($data['challenge']) ? $data['challenge'] : "";
        $solution = isset($data['solution']) ? $data['solution'] : "";
        $sort_order = isset($data['sort_order']) ? intval($data['sort_order']) : 0;

        $query = "UPDATE " . $this->table . " SET title = :title, category_id = :category_id, description = :description, tech_array = :tech_array, github = :github, demo = :demo, challenge = :challenge, solution = :solution, metrics = :metrics, sort_order = :sort_order WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':category_id', $data['category_id'], PDO::PARAM_INT);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':tech_array', $tech_array);
        $stmt->bindParam(':github', $github);
        $stmt->bindParam(':demo', $demo);
        $stmt->bindParam(':challenge', $challenge);
        $stmt->bindParam(':solution', $solution);
        $stmt->bindParam(':metrics', $metrics);
        $stmt->bindParam(':sort_order', $sort_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(["message" => "Project updated successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to update project."]);
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
            echo json_encode(["message" => "Project deleted successfully."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Failed to delete project."]);
        }
    }
}