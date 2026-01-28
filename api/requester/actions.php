<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/actionController.php';
require_once __DIR__ . '/../../controllers/notificationsController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';

// ✅ تحميل إعدادات المشروع
$config = require __DIR__ . '/../../config/config.php';

// ✅ إنشاء الاتصال بقاعدة البيانات
$database = new Database($config['db']);
$conn = $database->getConnection();

$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
$auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'plant manager']);

$controller = new ActionController($conn);
$notificationController = new NotificationController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
$input = json_decode(file_get_contents("php://input"), true) ?? [];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $controller->getById((int) $_GET['id']);
            } else {
                $controller->getAll($_GET);
            }
            break;

        case 'POST':

            $data  = $_POST;
            $files = $_FILES;

            $res = $controller->create($data, $files);

            if (!$res['success']) {
                echo json_encode($res);
                break;
            }

            $notificationController->sendNotification(
                "New Action Created",
                $res['data']['title'] ?? 'A new action has been created',
                [$res['data']['assigned_user_id']],
                BASE_URL . '/public/action.php?id=' . $res['data']['id'],
                $_SESSION['user_id'] ?? null
            );

            echo json_encode($res);
            break;



        case 'PUT':
            parse_str(file_get_contents('php://input'), $data);
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing ID']);
                exit;
            }
            $controller->update((int) $_GET['id'], $data);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing ID']);
                exit;
            }
            $controller->delete((int) $_GET['id']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error', 'error' => $e->getMessage()]);
}
