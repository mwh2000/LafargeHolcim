<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/HotWorkPermitController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';
$database = new Database($config['db']);
$conn = $database->getConnection();

$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
// Allow requester, area_manager, manager etc.
$auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'shift leader and issurs', 'مسؤل العزل']);

$controller = new HotWorkPermitController($conn);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    $res = null;

    switch ($method) {
        case 'GET':
            if ($action === 'getAssignees') {
                $res = $controller->getAssignees();
            } elseif ($action === 'show' && isset($_GET['id'])) {
                $res = $controller->getPermit($_GET['id']);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            $input['created_by'] = $decoded->id;
            $res = $controller->createPermit($input);
            break;

        default:
            $res = ['success' => false, 'message' => 'Method Not Allowed'];
            http_response_code(405);
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
