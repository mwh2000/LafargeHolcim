<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/ConfinedSpacePermitController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';
$database = new Database($config['db']);
$conn = $database->getConnection();
$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
$auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'shift leader and issurs', 'مسؤل العزل', 'plant manager']);
$controller = new ConfinedSpacePermitController($conn);

try {
    $action = $_GET['action'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'show' && isset($_GET['id'])) {
        $result = $controller->getPermit((int)$_GET['id'], (int)$decoded->id, (int)$decoded->role_id);
        if (!$result['success'] && ($result['message'] ?? '') === 'غير مخول لعرض هذه الرخصة') {
            http_response_code(403);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'getAll') {
        $result = $controller->getAll((int)$decoded->id, (int)$decoded->role_id);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            http_response_code(400);
            $result = ['success' => false, 'message' => 'بيانات الطلب غير صالحة'];
        } else {
            $input['created_by'] = (int)$decoded->id;
            $userStmt = $conn->prepare('SELECT name FROM users WHERE id = ?');
            $userStmt->execute([(int)$decoded->id]);
            $input['issuer_name'] = (string)$userStmt->fetchColumn();
            $result = $controller->createPermit($input);
        }
    } else {
        http_response_code(405);
        $result = ['success' => false, 'message' => 'العملية غير مدعومة'];
    }
    sendJson($result);
} catch (Throwable $e) {
    http_response_code(500);
    sendJson(['success' => false, 'message' => 'حدث خطأ في الخادم']);
}
