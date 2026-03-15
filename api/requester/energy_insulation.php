<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/EnergyInsulationController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';
$database = new Database($config['db']);
$conn = $database->getConnection();

$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
// Only requester, area_manager, etc. can access
$auth->requireRoles($decoded, ['requester', 'safety', 'area_manager', 'manager', 'plant manager']);

$controller = new EnergyInsulationController($conn);

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
                $res = $controller->getEquipmentsBySection($sectionId);
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents("php://input"), true) ?? [];
            $input['created_by'] = $decoded->id;
            $res = $controller->createLicense($input);
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
