<?php
class AboutController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest($method) {
        switch ($method) {
            case 'GET':
                $this->getAbout();
                break;
            case 'POST':
            case 'PUT':
                $this->updateAbout();
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Method Not Allowed"]);
                break;
        }
    }

    private function getAbout() {
        $query = "SELECT id, experience_years, projects_built, happy_clients, core_stack_count, description FROM about LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            // Ensure proper integer data types are returned
            $row['id'] = (int)$row['id'];
            $row['experience_years'] = (int)$row['experience_years'];
            $row['projects_built'] = (int)$row['projects_built'];
            $row['happy_clients'] = (int)$row['happy_clients'];
            $row['core_stack_count'] = (int)$row['core_stack_count'];
            
            echo json_encode($row);
        } else {
            echo json_encode([
                "id" => null,
                "experience_years" => 0,
                "projects_built" => 0,
                "happy_clients" => 0,
                "core_stack_count" => 0,
                "description" => ""
            ]);
        }
    }

    private function updateAbout() {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['description']) || trim($data['description']) === '') {
            http_response_code(400);
            echo json_encode(["message" => "Description is a required field."]);
            return;
        }

        $checkQuery = "SELECT id FROM about LIMIT 1";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute();
        $row = $checkStmt->fetch();

        if ($row) {
            $query = "UPDATE about SET 
                        experience_years = :experience_years, 
                        projects_built = :projects_built, 
                        happy_clients = :happy_clients, 
                        core_stack_count = :core_stack_count, 
                        description = :description 
                      WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $row['id']);
        } else {
            $query = "INSERT INTO about SET 
                        experience_years = :experience_years, 
                        projects_built = :projects_built, 
                        happy_clients = :happy_clients, 
                        core_stack_count = :core_stack_count, 
                        description = :description";
            $stmt = $this->db->prepare($query);
        }

        $exp = isset($data['experience_years']) ? (int)$data['experience_years'] : 0;
        $proj = isset($data['projects_built']) ? (int)$data['projects_built'] : 0;
        $clients = isset($data['happy_clients']) ? (int)$data['happy_clients'] : 0;
        $stack = isset($data['core_stack_count']) ? (int)$data['core_stack_count'] : 0;

        $stmt->bindParam(":experience_years", $exp, PDO::PARAM_INT);
        $stmt->bindParam(":projects_built", $proj, PDO::PARAM_INT);
        $stmt->bindParam(":happy_clients", $clients, PDO::PARAM_INT);
        $stmt->bindParam(":core_stack_count", $stack, PDO::PARAM_INT);
        $stmt->bindParam(":description", $data['description'], PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(["message" => "About metrics and description updated successfully."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to write metrics data."]);
        }
    }
}