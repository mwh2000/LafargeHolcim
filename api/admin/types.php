<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/ApiResponseTrait.php';
require_once __DIR__ . '/../../controllers/typesController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';

try {
    // الاتصال بقاعدة البيانات
    $db = new Database($config['db']);
    $conn = $db->getConnection();

    // تهيئة الكنترولر والـ Middleware
    $auth = new AuthMiddleware();
    $decoded = $auth->verifyToken();
    $auth->requireAdmin($decoded);

    $controller = new TypesController($conn);

    // تحديد نوع الطلب
    $method = $_SERVER['REQUEST_METHOD'];
    $id = $_GET['id'] ?? null;
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($method) {
        /** ✅ Get all / Get by ID */
        case 'GET':
            if ($id) {
                $res = $controller->getById((int) $id); // يجب أن ترجع array
            } else {
                $filters = [
                    'search' => $_GET['search'] ?? null,
                    'limit' => $_GET['limit'] ?? null,
                    'offset' => $_GET['offset'] ?? null
                ];
                $res = $controller->getAll($filters);
            }
            sendJson($res);
            break;

        /** ✅ Create new type */
        case 'POST':
            $res = $controller->create($input);
            sendJson($res);
            break;

        /** ✅ Update type */
        case 'PUT':
            if (!$id) {
                http_response_code(400);
                sendJson(['success' => false, 'message' => 'Missing id parameter']);
            }
            $res = $controller->update((int) $id, $input);
            sendJson($res);
            break;

        /** ✅ Delete type */
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                sendJson(['success' => false, 'message' => 'Missing id parameter']);
            }
            $res = $controller->delete((int) $id);
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
