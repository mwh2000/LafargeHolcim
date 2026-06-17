<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/HotWorkPermitController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';
$database = new Database($config['db']);
$conn = $database->getConnection();

$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
// Allow requester, area_manager, manager etc.
$auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'shift leader and issurs', 'مسؤل العزل', 'plant manager']);

$controller = new HotWorkPermitController($conn);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    $res = null;

    switch ($method) {
        case 'GET':
            if ($action === 'getAssignees') {
                $res = $controller->getAssignees();
            } elseif ($action === 'getManagers') {
                $res = $controller->getManagers();
            } elseif ($action === 'getSupervisors') {
                $res = $controller->getSupervisors();
            } elseif ($action === 'show' && isset($_GET['id'])) {
                $res = $controller->getPermit($_GET['id']);
            } elseif ($action === 'getStatistics') {
                $filters = $_GET;
                $filters['user_id'] = $decoded->id;
                $filters['role_id'] = $decoded->role_id;
                $res = $controller->getStatistics($filters);
            } elseif ($action === 'getAll') {
                $filters = $_GET;
                $filters['user_id'] = $decoded->id;
                $filters['role_id'] = $decoded->role_id;
                $res = $controller->getAll($filters);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            $input['created_by'] = $decoded->id;
            // route by action
            if ($action === 'assignSupervisor') {
                // manager assigns supervisor
                $permitId = $input['permit_id'] ?? null;
                $supervisorId = $input['supervisor_id'] ?? null;
                $res = $controller->assignSupervisor($permitId, $decoded->id, $supervisorId);
            } elseif ($action === 'markDone') {
                $permitId = $input['permit_id'] ?? null;
                $res = $controller->markDoneBySupervisor($permitId, $decoded->id);
            } elseif ($action === 'complete') {
                $permitId = $input['permit_id'] ?? null;
                $res = $controller->completePermit($permitId, $input);
            } elseif ($action === 'updateFinishingTime') {
                $permitId = $input['permit_id'] ?? null;
                $finishingTime = $input['finishing_time'] ?? null;
                $res = $controller->updateFinishingTime($permitId, $finishingTime, $decoded->id);
            } else {
                $res = $controller->createPermit($input);
            }
            break;

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
