<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/JWTHandler.php';
require_once __DIR__ . '/../../controllers/userController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';

$config = require __DIR__ . '/../../config/config.php';

$database = new Database($config['db']);
$conn = $database->getConnection();
$jwtHandler = new JWTHandler($config['jwt_secret']);
$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
$auth->requireAdmin($decoded);
$userController = new UserController($conn, $jwtHandler);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
$input = json_decode(file_get_contents("php://input"), true) ?? [];

// ✅ Decode JWT
$decoded = $auth->verifyToken();

switch ($method) {
    // 🔸 Create User
    case 'POST':
        if ($action === 'create') {
            $userController->create($input, $decoded);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid POST action']);
        }
        break;

    // 🔸 Get All / Get One
    case 'GET':
        if ($action === 'all') {
            $filters = [
                'search' => $queryParams['search'] ?? null,
                'role_id' => $queryParams['role_id'] ?? null,
                'is_active' => $queryParams['is_active'] ?? null,
            ];
            $userController->getAll($filters);
        } elseif ($action === 'show' && isset($queryParams['id'])) {
            $userController->getById((int) $queryParams['id']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid GET action']);
        }
        break;

    // 🔸 Update User
    case 'PUT':
        if ($action === 'update' && isset($queryParams['id'])) {
            $userController->update((int) $queryParams['id'], $input, $decoded);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid PUT action']);
        }
        break;

    // 🔸 Delete User
    case 'DELETE':
        if ($action === 'delete' && isset($queryParams['id'])) {
            $userController->delete((int) $queryParams['id'], $decoded);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid DELETE action']);
        }
        break;

    // 🔸 Preflight (CORS)
    case 'OPTIONS':
        http_response_code(200);
        break;

    // ❌ Unsupported
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        break;
}