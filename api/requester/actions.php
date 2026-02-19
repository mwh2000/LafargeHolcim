<?php
header("Content-Type: application/json; charset=UTF-8");


require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/actionController.php';
require_once __DIR__ . '/../../controllers/notificationsController.php';
require_once __DIR__ . '/../../controllers/emailController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../services/actionService.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

require_once __DIR__ . '/../../vendor/autoload.php';

// ✅ إعدادات المشروع
$config = require __DIR__ . '/../../config/config.php';
$database = new Database($config['db']);
$conn = $database->getConnection();

$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
$auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'plant manager']);

$service = new ActionService($conn);
$controller = new ActionController($conn);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
$input = json_decode(file_get_contents("php://input"), true) ?? [];

/**
 * ✨ Helper لطباعة JSON مرة واحدة
 */


try {
    $res = null;

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $res = $controller->getById((int) $_GET['id']);
            } else {
                $res = $controller->getAll($_GET);
            }
            break;

        case 'POST':
            $data  = $_POST;
            $files = $_FILES;

            $res = $service->createWithNotifications(
                $data,
                $files,
                $decoded->id   // ✅ من التوكن
            );
            break;

        case 'PUT':
            parse_str(file_get_contents('php://input'), $data);
            if (!isset($_GET['id'])) {
                $res = ['success' => false, 'message' => 'Missing ID'];
            } else {
                $res = $controller->update((int) $_GET['id'], $data);
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                $res = ['success' => false, 'message' => 'Missing ID'];
            } else {
                $res = $controller->delete((int) $_GET['id']);
            }
            break;

        case 'OPTIONS': // CORS preflight
            http_response_code(200);
            exit;

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
