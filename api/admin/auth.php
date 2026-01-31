<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/JWTHandler.php';
require_once __DIR__ . '/../../controllers/authController.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';
$database = new Database($config['db']);
$conn = $database->getConnection();
$jwtHandler = new JWTHandler($config['jwt_secret']);
$authController = new AuthController($conn, $jwtHandler);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$input = json_decode(file_get_contents("php://input"), true) ?? [];

try {
    if ($method === 'POST') {
        if ($action === 'login') {
            $res = $authController->login($input); // ✅ respond() يطبع ويخرج
            sendJson($res); // ✅ نرسلها بشكل JSON
        } elseif ($action === 'logout') {
            $res = $authController->logout();
            sendJson($res);
        } else {
            http_response_code(400);
            sendJson(['success' => false, 'message' => 'Invalid POST action']);
        }
    } elseif ($method === 'OPTIONS') {
        http_response_code(200);
    } else {
        http_response_code(405);
        sendJson(['success' => false, 'message' => 'Method Not Allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    sendJson([
        'success' => false,
        'message' => 'Server Error',
        'error' => $e->getMessage()
    ]);
}
