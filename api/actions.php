<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../controllers/actionController.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

// ✅ تحميل إعدادات المشروع
$config = require __DIR__ . '/../config/config.php';

// ✅ إنشاء الاتصال بقاعدة البيانات
$database = new Database($config['db']);
$conn = $database->getConnection();

$controller = new ActionController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$user_id = $_GET['user_id'] ?? null;
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
$input = json_decode(file_get_contents("php://input"), true) ?? [];

try {
    switch ($method) {
        case 'GET':
            $auth = new AuthMiddleware();
            $decoded = $auth->verifyToken();
            $filters = array_merge(
                $_GET ?? [],
                $_POST ?? []
            );

            // ✅ جلب إجراء محدد بالـ ID
            if (isset($_GET['id'])) {
                $controller->getById((int) $_GET['id']);
                break;
            }

            // ✅ جلب الإجراءات المسندة للمستخدم
            if ($action === 'assigned_to_me') {
                $controller->getAssignedToMe($user_id, $filters);
                break;
            }

            // ✅ جلب الإجراءات التي أنشأها المستخدم
            if ($action === 'created_by_me') {
                $controller->getAllByME($user_id, []);
                break;
            }

            if ($action === 'getStatistics') {
                // $auth->requireRoles($decoded, ['admin']);
                $filters = $_GET ?? [];

                $controller->getStatistics($filters);
                break;
            }

            // ✅ الافتراضي: كل الإجراءات
            $controller->getAll($filters);
            break;

        case 'PUT':
            $auth = new AuthMiddleware();
            $decoded = $auth->verifyToken();

            if ($action === 'update_status') {
                if (isset($_GET['id'])) {
                    $controller->updateStatus($_GET['id']);
                }
                break;
            }

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error',
        'error' => $e->getMessage()
    ]);
}
