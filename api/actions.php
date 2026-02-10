<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../controllers/actionController.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$config = require __DIR__ . '/../config/config.php';
$database = new Database($config['db']);
$conn = $database->getConnection();

$controller = new ActionController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$user_id = $_GET['user_id'] ?? null;
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
$input = json_decode(file_get_contents("php://input"), true) ?? [];

function sendJson($res)
{
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $auth = new AuthMiddleware();
    $decoded = $auth->verifyToken();

    $filters = array_merge($_GET ?? [], $_POST ?? []);
    $res = null;

    switch ($method) {
        case 'GET':

            if ($action === 'exportExcel') {
                $data = $controller->getAll($filters, true); // 👈 دالة ترجع array

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="actions.csv"');
                echo "\xEF\xBB\xBF";
                $output = fopen('php://output', 'w');
                fputcsv($output, [
                    'Action',
                    'Created By',
                    'Assigned To',
                    'Group',
                    'Visit Duration',
                    'Description',
                    'Priority',
                    'Start Date',
                    'Due Date',
                    'Status'
                ]);
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['action'],
                        $row['created_by_name'],
                        $row['assigned_user_name'],
                        $row['group'],
                        $row['visit_duration'],
                        $row['description'],
                        $row['priority'],
                        $row['start_date'],
                        $row['expiry_date'],
                        $row['status'],
                    ]);
                }
                fclose($output);
                exit;
            }

            if (isset($_GET['id'])) {
                $res = $controller->getById((int) $_GET['id']);
            } elseif ($action === 'assigned_to_me') {
                $res = $controller->getAssignedToMe($user_id, $filters);
            } elseif ($action === 'created_by_me') {
                $res = $controller->getAllByME($user_id, $filters);
            } elseif ($action === 'getStatistics') {
                $res = $controller->getStatistics($filters);
            } else {
                $res = $controller->getAll($filters);
            }
            sendJson($res);
            break;

        case 'PUT':
            if ($action === 'update_status' && isset($_GET['id'])) {
                $res = $controller->updateStatus($_GET['id'], 'closed', $input['note'] ?? '');
                sendJson($res);
            }
            break;

        default:
            http_response_code(405);
            sendJson(['success' => false, 'message' => 'Method Not Allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    sendJson([
        'success' => false,
        'message' => 'Server Error',
        'error'   => $e->getMessage()
    ]);
}
