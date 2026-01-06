<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/ApiResponseTrait.php';
require_once __DIR__ . '/../../controllers/typeCategoriesController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';

$config = require __DIR__ . '/../../config/config.php';

try {
    // الاتصال بقاعدة البيانات
    $db = new Database($config['db']);
    $conn = $db->getConnection();

    // تهيئة الكنترولر والـ Middleware
    $auth = new AuthMiddleware();
    $decoded = $auth->verifyToken(); // يتحقق من التوكن
    // $auth->requireAdmin($decoded);   // يسمح فقط للـ Admin / Super Admin

    $controller = new TypeCategoriesController($conn);

    // تحديد نوع الطلب
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        /** ✅ Get all (with search & pagination) */
        case 'GET':
            if (isset($_GET['id'])) {
                $controller->getById((int) $_GET['id']);
            } else {
                $filters = [
                    'search' => $_GET['search'] ?? null,
                    'limit' => $_GET['limit'] ?? null,
                    'offset' => $_GET['offset'] ?? null
                ];
                $controller->getAll($filters);
            }
            break;

        /** ✅ Create new category */
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $controller->create($data);
            break;

        /** ✅ Update category */
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing id parameter']);
                exit;
            }
            $controller->update((int) $_GET['id'], $data);
            break;

        /** ✅ Delete category */
        case 'DELETE':
            if (empty($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing id parameter']);
                exit;
            }
            $controller->delete((int) $_GET['id']);
            break;

        /** ❌ Unsupported method */
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
