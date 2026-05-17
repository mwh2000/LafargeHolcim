<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../services/GoodPracticeService.php';
require_once __DIR__ . '/../../controllers/GoodPracticeController.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

require_once __DIR__ . '/../../vendor/autoload.php';

// ✅ إعدادات المشروع
$config = require __DIR__ . '/../../config/config.php';
$database = new Database($config['db']);
$conn = $database->getConnection();

$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
$auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'plant manager', 'shift leader and issurs']);

$service = new GoodPracticeService($conn);
$controller = new GoodPracticeController($conn);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);

try {
    $res = null;

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $res = $controller->getById((int) $_GET['id']);
            } else {
                $res = ['success' => false, 'message' => 'Missing ID'];
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
