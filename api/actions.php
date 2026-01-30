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

// ✅ تحميل إعدادات المشروع
$config = require __DIR__ . '/../config/config.php';

// ✅ إنشاء الاتصال بقاعدة البيانات
$database = new Database($config['db']);
$conn = $database->getConnection();

$controller = new ActionController($conn);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$user_id = $_GET['user_id'] ?? null;
parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
$input = json_decode(file_get_contents("php://input"), true) ?? [];

try {
    switch ($method) {
        case 'GET':
            $auth = new AuthMiddleware();
            $decoded = $auth->verifyToken();
            $filters = array_merge(
                $_GET ?? [],
                $_POST ?? []
            );

            if ($action === 'exportExcel') {

                $data = $controller->getAll($filters, true); // 👈 نفس getAll

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="actions.csv"');
                echo "\xEF\xBB\xBF";

                $output = fopen('php://output', 'w');

                fputcsv($output, [
                    'Action',
                    'Created By',
                    'Assigned To',
                    'Group',
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
                        $row['start_date'],
                        $row['expiry_date'],
                        $row['status'],
                    ]);
                }

                fclose($output);
                exit;
            }


            // ✅ جلب إجراء محدد بالـ ID
            if (isset($_GET['id'])) {
                $controller->getById((int) $_GET['id']);
                break;
            }

            // ✅ جلب الإجراءات المسندة للمستخدم
            if ($action === 'assigned_to_me') {
                $controller->getAssignedToMe($user_id, $filters);
                break;
            }

            // ✅ جلب الإجراءات التي أنشأها المستخدم
            if ($action === 'created_by_me') {
                $controller->getAllByME($user_id, []);
                break;
            }

            if ($action === 'getStatistics') {
                // $auth->requireRoles($decoded, ['admin']);
                $filters = $_GET ?? [];

                $controller->getStatistics($filters);
                break;
            }

            // ✅ الافتراضي: كل الإجراءات
            $controller->getAll($filters);
            break;

        case 'PUT':
            $auth = new AuthMiddleware();
            $decoded = $auth->verifyToken();

            if ($action === 'update_status') {
                if (isset($_GET['id'])) {
                    $controller->updateStatus($_GET['id'], 'closed', $input['note'] ?? '');
                }
                break;
            }

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
