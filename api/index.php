<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// Secure write/modify operations
if ($method !== 'GET') {
    // Get all headers and force keys to lowercase to fix Vercel/production normalization
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $providedKey = isset($headers['x-api-key']) ? $headers['x-api-key'] : null;

    // Parse local .env file if it exists (Localhost)
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            if (trim($name) === 'API_KEY') {
                $_ENV['API_KEY'] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }
    }

    // Cascade lookup to handle localhost environments and Vercel serverless environment injection
    $expectedKey = $_ENV['API_KEY'] ?? getenv('API_KEY') ?? $_SERVER['API_KEY'] ?? '';

    if (!$providedKey || empty($expectedKey) || $providedKey !== $expectedKey) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized: Invalid or missing API key."]);
        exit();
    }
}

// Route extraction via explicit query string passed by vercel.json
$resource = isset($_GET['route']) ? rtrim($_GET['route'], '/') : '';

switch ($resource) {
    case 'about':
        require_once __DIR__ . '/controllers/AboutController.php';
        $controller = new AboutController();
        $controller->handleRequest($method);
        break;
    case 'skill-categories':
        require_once __DIR__ . '/controllers/SkillCategoryController.php';
        $controller = new SkillCategoryController();
        $controller->handleRequest($method);
        break;
    case 'project-categories':
        require_once __DIR__ . '/controllers/ProjectCategoryController.php';
        $controller = new ProjectCategoryController();
        $controller->handleRequest($method);
        break;
    case 'experiences':
        require_once __DIR__ . '/controllers/ExperienceController.php';
        $controller = new ExperienceController();
        $controller->handleRequest($method);
        break;
    case 'contact':
        require_once __DIR__ . '/controllers/ContactController.php';
        $controller = new ContactController();
        $controller->handleRequest($method);
        break;
    case 'skills':
        require_once __DIR__ . '/controllers/SkillController.php';
        $controller = new SkillController();
        $controller->handleRequest($method);
        break;
    default:
        http_response_code(404);
        echo json_encode([
            "message" => "Resource not found.",
            "debug_route_received" => $resource
        ]);
        break;
}
