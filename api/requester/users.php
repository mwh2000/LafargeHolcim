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
$auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'plant manager']);

$userController = new UserController($conn, $jwtHandler);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
$input = json_decode(file_get_contents("php://input"), true) ?? [];

/**
 * ✨ Helper لطباعة JSON مرة واحدة
 */
function sendJson($res)
{
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $res = null;

    switch ($method) {
        case 'GET':
            if ($action === 'all') {
                $filters = [
                    'search' => $queryParams['search'] ?? null,
                    'role_id' => $queryParams['role_id'] ?? null,
                    'is_active' => $queryParams['is_active'] ?? null,
                ];
                $res = $userController->getAll($filters);
            } elseif ($action === 'show' && isset($queryParams['id'])) {
                $res = $userController->getById((int) $queryParams['id']);
            } else {
                http_response_code(400);
                $res = ['success' => false, 'message' => 'Invalid GET action'];
            }
            break;

        case 'OPTIONS': // CORS preflight
            http_response_code(200);
            exit;

        default:
            http_response_code(405);
            $res = ['success' => false, 'message' => 'Method Not Allowed'];
            break;
    }

    sendJson($res);
} catch (Exception $e) {
    http_response_code(500);
    sendJson([
        'success' => false,
        'message' => 'Server Error',
        'error' => $e->getMessage()
    ]);
}
