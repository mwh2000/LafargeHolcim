<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/JWTHandler.php';
require_once __DIR__ . '/../../controllers/equipmentSectionController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';

$database = new Database($config['db']);
$conn = $database->getConnection();
$jwtHandler = new JWTHandler($config['jwt_secret']);

$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
$auth->requireRoles($decoded, ['admin', 'manager', 'area_manager']);

$equipmentSectionController = new EquipmentSectionController($conn);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
$input = json_decode(file_get_contents("php://input"), true) ?? [];

try {
    switch ($method) {

        // 🔸 Create
        case 'POST':
            if ($action === 'create') {
                $res = $equipmentSectionController->create($input);
                sendJson($res);
            } else {
                http_response_code(400);
                sendJson(['success' => false, 'message' => 'Invalid POST action']);
            }
            break;

        // 🔸 Get All / Get One
        case 'GET':
            if ($action === 'all') {
                $filters = [
                    'search' => $queryParams['search'] ?? null,
                ];
                $res = $equipmentSectionController->getAll($filters);
                sendJson($res);
            } elseif ($action === 'show' && isset($queryParams['id'])) {
                $res = $equipmentSectionController->getById((int) $queryParams['id']);
                sendJson($res);
            } else {
                http_response_code(400);
                sendJson(['success' => false, 'message' => 'Invalid GET action']);
            }
            break;

        // 🔸 Update
        case 'PUT':
            if ($action === 'update' && isset($queryParams['id'])) {
                $res = $equipmentSectionController->update((int) $queryParams['id'], $input);
                sendJson($res);
            } else {
                http_response_code(400);
                sendJson(['success' => false, 'message' => 'Invalid PUT action']);
            }
            break;

        // 🔸 Delete
        case 'DELETE':
            if ($action === 'delete' && isset($queryParams['id'])) {
                $res = $equipmentSectionController->delete((int) $queryParams['id']);
                sendJson($res);
            } else {
                http_response_code(400);
                sendJson(['success' => false, 'message' => 'Invalid DELETE action']);
            }
            break;

        // 🔸 Preflight (CORS)
        case 'OPTIONS':
            http_response_code(200);
            break;

        // ❌ Unsupported
        default:
            http_response_code(405);
            sendJson(['success' => false, 'message' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    sendJson([
        'success' => false,
        'message' => 'Server Error',
        'error' => $e->getMessage()
    ]);
}
