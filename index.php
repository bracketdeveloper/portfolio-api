<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/api/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// Secure write/modify operations. Allow public GET requests to read portfolio data.
if ($method !== 'GET') {
    $headers = getallheaders();
    $providedKey = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : null;
    
    // Fallback load of environment variables to fetch expected key value locally
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            list($name, $value) = explode('=', $line, 2);
            if (trim($name) === 'API_KEY') {
                $_ENV['API_KEY'] = trim($value);
            }
        }
    }

    $expectedKey = isset($_ENV['API_KEY']) ? $_ENV['API_KEY'] : getenv('API_KEY');

    if (!$providedKey || $providedKey !== $expectedKey) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized: Invalid or missing API key."]);
        exit();
    }
}

// URL Routing Engine
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriSegments = explode('/', trim($requestUri, '/'));
$apiIndex = array_search('api', $uriSegments);

if ($apiIndex === false || !isset($uriSegments[$apiIndex + 1])) {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint not found."]);
    exit();
}

$resource = $uriSegments[$apiIndex + 1];

switch ($resource) {
    case 'about':
        require_once __DIR__ . '/api/controllers/AboutController.php';
        $controller = new AboutController();
        $controller->handleRequest($method);
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "Resource not found."]);
        break;
}