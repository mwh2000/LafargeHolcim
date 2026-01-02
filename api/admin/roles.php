<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/ApiResponseTrait.php';
require_once __DIR__ . '/../../controllers/RolesController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';

$config = require __DIR__ . '/../../config/config.php';

try {
    // الاتصال بقاعدة البيانات
    $db = new Database($config['db']);
    $conn = $db->getConnection();

    // تهيئة الكنترولر والـ Middleware
    $auth = new AuthMiddleware();
    $decoded = $auth->verifyToken(); // يتحقق من التوكن
    $auth->requireAdmin($decoded);   // يسمح فقط للـ Admin / Super Admin

    $controller = new RolesController($conn);

    // تحديد نوع الطلب
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        /** ✅ Get all (with search & pagination) */
        case 'GET':
            $filters = [
                'search' => $_GET['search'] ?? null,
            ];
            $controller->getAll($filters);

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
