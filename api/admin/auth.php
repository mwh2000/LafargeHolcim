<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/JWTHandler.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

$config = require __DIR__ . '/../../config/config.php';

$database = new Database($config['db']);
$conn = $database->getConnection();
$jwtHandler = new JWTHandler($config['jwt_secret']);
$authController = new AuthController($conn, $jwtHandler);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$input = json_decode(file_get_contents("php://input"), true) ?? [];

switch ($method) {
    case 'POST':
        if ($action === 'login') {
            $authController->login($input);
        } elseif ($action === 'logout') {
            $authController->logout();
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid POST action']);
        }
        break;

    case 'OPTIONS':
        http_response_code(200);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        break;
}
