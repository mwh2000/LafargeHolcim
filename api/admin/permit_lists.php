<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../controllers/PermitListController.php';
require_once __DIR__ . '/../../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../../public/helpers/sendJson.php';

$config = require __DIR__ . '/../../config/config.php';

$database = new Database($config['db']);
$conn = $database->getConnection();

$auth = new AuthMiddleware();
$decoded = $auth->verifyToken();
$auth->requireAdmin($decoded);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$listKey = $_GET['list'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$input = json_decode(file_get_contents("php://input"), true) ?? [];

if (!isset(PermitListController::LISTS[$listKey])) {
    sendJson(['success' => false, 'message' => 'Unknown list'], 400);
}
$controller = new PermitListController($conn, $listKey);

try {
    if ($method === 'GET' && $action === 'all') {
        sendJson($controller->getAll(trim((string)($_GET['search'] ?? ''))));
    } elseif ($method === 'POST' && $action === 'create') {
        sendJson($controller->create($input));
    } elseif ($method === 'PUT' && $action === 'update' && $id) {
        sendJson($controller->update($id, $input));
    } elseif ($method === 'DELETE' && $action === 'delete' && $id) {
        sendJson($controller->delete($id));
    } else {
        sendJson(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => 'Server Error', 'error' => $e->getMessage()], 500);
}
