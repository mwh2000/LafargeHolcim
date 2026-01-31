<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/ApiResponseTrait.php';
require_once __DIR__ . '/../../controllers/typeCategoriesController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';

try {
    // الاتصال بقاعدة البيانات
    $db = new Database($config['db']);
    $conn = $db->getConnection();

    // تهيئة الكنترولر والـ Middleware
    $auth = new AuthMiddleware();
    $decoded = $auth->verifyToken(); // يتحقق من التوكن

    $controller = new TypeCategoriesController($conn);

    // تحديد نوع الطلب
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {

        /** ✅ Get all / Get by ID */
        case 'GET':
            if (isset($_GET['id'])) {
                $res = $controller->getById((int) $_GET['id']); // يجب أن ترجع array
                sendJson($res);
            } else {
                $filters = [
                    'search' => $_GET['search'] ?? null,
                    'limit' => $_GET['limit'] ?? null,
                    'offset' => $_GET['offset'] ?? null
                ];
                $res = $controller->getAll($filters);
                sendJson($res);
            }
            break;

        /** ✅ Create new category */
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $res = $controller->create($data);
            sendJson($res);
            break;

        /** ✅ Update category */
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            if (empty($_GET['id'])) {
                http_response_code(400);
                sendJson(['success' => false, 'message' => 'Missing id parameter']);
                exit;
            }
            $res = $controller->update((int) $_GET['id'], $data);
            sendJson($res);
            break;

        /** ✅ Delete category */
        case 'DELETE':
            if (empty($_GET['id'])) {
                http_response_code(400);
                sendJson(['success' => false, 'message' => 'Missing id parameter']);
                exit;
            }
            $res = $controller->delete((int) $_GET['id']);
            sendJson($res);
            break;

        /** ❌ Unsupported method */
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
