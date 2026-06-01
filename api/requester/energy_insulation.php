<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/EnergyInsulationController.php';
require_once __DIR__ . '/../../controllers/notificationsController.php';
require_once __DIR__ . '/../../controllers/emailController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';
$database = new Database($config['db']);
$conn = $database->getConnection();

$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
// Only requester, area_manager, etc. can access
$auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'shift leader and issurs', 'مسؤل العزل']);

$notificationController = new NotificationController($conn);
$emailController = new EmailController($conn);
$controller = new EnergyInsulationController($conn, $notificationController, $emailController);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    $res = null;

    switch ($method) {
        case 'GET':
            if ($action === 'getEligibleUsers') {
                $res = $controller->getEligibleUsers();
            } elseif ($action === 'getEquipmentsBySection') {
                $sectionId = (int) ($_GET['section_id'] ?? 0);
                $search = $_GET['search'] ?? '';
                $page = (int) ($_GET['page'] ?? 1);
                $limit = (int) ($_GET['limit'] ?? 10);
                $res = $controller->getEquipmentsBySection($sectionId, $search, $page, $limit);
            } elseif ($action === 'show' && isset($_GET['id'])) {
                $res = $controller->getLicenseById((int)$_GET['id']);
            } elseif ($action === 'getIsolationOfficers') {
                $res = $controller->getIsolationOfficers();
            } elseif ($action === 'getShiftLeaders') {
                $res = $controller->getShiftLeaders();
            } elseif ($action === 'getStatistics') {
                $filters = $_GET;
                $filters['user_id'] = $decoded->id;
                $filters['role_id'] = $decoded->role_id;
                $res = $controller->getStatistics($filters);
            } elseif ($action === 'getAll') {
                $filters = $_GET;
                $filters['user_id'] = $decoded->id;
                $filters['role_id'] = $decoded->role_id;
                $res = $controller->getAllLicenses($filters);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            if ($action === 'updateIsolationOfficer') {
                $res = $controller->updateIsolationOfficer((int)$input['license_id'], (int)$input['officer_id'], $decoded->id);
            } elseif ($action === 'confirmByIsolationOfficer') {
                $res = $controller->confirmByIsolationOfficer((int)$input['license_id'], (int)$input['shift_leader_id'], $decoded->id);
            } elseif ($action === 'confirmByShiftLeader') {
                $res = $controller->confirmByShiftLeader((int)$input['license_id'], $decoded->id);
            } elseif ($action === 'amDone') {
                $res = $controller->amDoneAction((int)$input['license_id'], $decoded->id);
            } elseif ($action === 'removeIsolation') {
                $res = $controller->removeIsolationAction((int)$input['license_id'], $decoded->id);
            } elseif ($action === 'reject') {
                $res = $controller->rejectLicense((int)$input['license_id'], $input['reason'] ?? '', $decoded->id);
            } elseif ($action === 'updateStaffGroups') {
                $res = $controller->updateStaffGroups((int)$input['license_id'], $input['staff_groups'] ?? [], $decoded->id);
            } elseif ($action === 'toggleGroupDone') {
                $res = $controller->toggleGroupDone((int)($input['group_id'] ?? 0), (int)($input['license_id'] ?? 0), $decoded->id, (int)($input['is_done'] ?? 0));
            } else {
                $input['created_by'] = $decoded->id;
                $res = $controller->createLicense($input);
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
