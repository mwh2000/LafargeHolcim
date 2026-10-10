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
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'getAssignees') {
        $result = $controller->getAssignees();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'getGasSigner') {
        if (!in_array((int)$decoded->role_id, [3, 5, 7], true)) {
            http_response_code(403);
            $result = ['success' => false, 'message' => 'هذه العملية متاحة للأدوار 3 و5 و7 فقط'];
        } else {
            $result = $controller->getGasSigner((int)$decoded->id);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'uploadControlImages') {
        $result = $controller->uploadControlImages($_FILES['images'] ?? []);
        if (!$result['success']) http_response_code(400);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'show' && isset($_GET['id'])) {
        $result = $controller->getPermit((int)$_GET['id'], (int)$decoded->id, (int)$decoded->role_id);
        if (!$result['success'] && ($result['message'] ?? '') === 'غير مخول لعرض هذه الرخصة') {
            http_response_code(403);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'getAll') {
        $result = $controller->getAll((int)$decoded->id, (int)$decoded->role_id, $_GET);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'getStatistics') {
        $result = $controller->getStatistics((int)$decoded->id, (int)$decoded->role_id, $_GET);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            http_response_code(400);
            $result = ['success' => false, 'message' => 'بيانات الطلب غير صالحة'];
        } else {
            if ($action === 'update') {
                $result = $controller->updatePermit((int)($input['permit_id'] ?? 0), (int)$decoded->id, $input);
            } elseif ($action === 'addGasMeasurement') {
                if (!in_array((int)$decoded->role_id, [3, 5, 7], true)) {
                    http_response_code(403);
                    $result = ['success' => false, 'message' => 'إضافة قراءات جديدة متاحة للأدوار 3 و5 و7 فقط'];
                } else {
                    $result = $controller->addGasMeasurement((int)($input['permit_id'] ?? 0), (int)$decoded->id, $input['measurement'] ?? []);
                }
            } elseif ($action === 'close') {
                $result = $controller->closePermit((int)($input['permit_id'] ?? 0), (int)$decoded->id);
            } elseif ($action === 'updateFinishingTime') {
                $result = $controller->updateInactiveFinishingTime(
                    (int)($input['permit_id'] ?? 0),
                    (int)$decoded->id,
                    (int)$decoded->role_id,
                    (string)($input['finishing_time'] ?? '')
                );
            } elseif ($action === 'addEntrant') {
                $result = $controller->addEntrant(
                    (int)($input['permit_id'] ?? 0),
                    (int)$decoded->id,
                    (int)$decoded->role_id,
                    $input
                );
            } else {
                $input['created_by'] = (int)$decoded->id;
                $userStmt = $conn->prepare('SELECT name FROM users WHERE id = ?');
                $userStmt->execute([(int)$decoded->id]);
                $input['issuer_name'] = (string)$userStmt->fetchColumn();
                $result = $controller->createPermit($input);
            }
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
