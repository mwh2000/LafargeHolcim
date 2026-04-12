<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/ApiResponseTrait.php';
require_once __DIR__ . '/../../controllers/typesController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';

$config = require __DIR__ . '/../../config/config.php';

/**
 * ✨ Helper لطباعة JSON مرة واحدة
 */
function sendJson($res)
{
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ✅ الاتصال بقاعدة البيانات
    $db = new Database($config['db']);
    $conn = $db->getConnection();

    // ✅ تهيئة الكنترولر و الـ Middleware
    $auth = new AuthMiddleware();
    $decoded = $auth->verifyToken();
    $auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'plant manager', 'shift leader and issurs']);

    $controller = new TypesController($conn);

    // ✅ نوع الطلب
    $method = $_SERVER['REQUEST_METHOD'];
    $res = null;

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $res = $controller->getById((int) $_GET['id']);
            } else {
                $filters = [
                    'search' => $_GET['search'] ?? null,
                    'limit'  => $_GET['limit'] ?? null,
                    'offset' => $_GET['offset'] ?? null
                ];
                $res = $controller->getAll($filters);
            }
            break;

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
        'error'   => $e->getMessage()
    ]);
}
