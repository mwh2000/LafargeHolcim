<?php
require_once __DIR__ . '/../core/JWTHandler.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/config.php';

class AuthMiddleware
{
    private $jwtHandler;
    private $config;
    private $conn;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/config.php';
        $this->jwtHandler = new JWTHandler($this->config['jwt_secret']);

        // إنشاء الاتصال هنا وليس في تعريف الخاصية
        $database = new Database($this->config['db']);
        $this->conn = $database->getConnection();
    }

    /**
     * يتحقق من صلاحية التوكن و يرجع بيانات المستخدم
     */
    public function verifyToken()
    {
        $headers = getallheaders();
        $token = null;

        // 1️⃣ من Authorization Header (fetch / API)
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        }

        // 2️⃣ من Cookie (download / redirect)
        if (!$token && isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        }

        // ❌ لا يوجد توكن
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authorization token missing'
            ]);
            exit;
        }

        // ✅ تحقق من التوكن
        $decoded = $this->jwtHandler->decodeToken($token);

        if (!$decoded) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired token'
            ]);
            exit;
        }

        return $decoded;
    }


    /**
     * يتحقق من أن المستخدم Admin أو Super Admin فقط
     */
    public function requireAdmin($decoded)
    {
        if (empty($decoded) || !isset($decoded->role_id)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        // نفترض أن الـ super_admin هو role_id = 1
        // و admin هو role_id = 2
        if (!in_array((int)$decoded->role_id, [1, 7], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }

    public function requireRoles($decoded, array $allowedRoles)
    {
        if (empty($decoded) || !isset($decoded->role_id)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        // 🔍 جلب اسم الدور من قاعدة البيانات
        $stmt = $this->conn->prepare("SELECT name FROM roles WHERE id = ?");
        $stmt->execute([$decoded->role_id]);
        $role = $stmt->fetchColumn();

        if (!$role) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            exit;
        }

        // add admin and shift leader roles automatically to allowed roles
        if (strtolower($role) === 'admin' || strtolower($role) === 'shift leader and issurs') {
            $allowedRoles[] = strtolower($role);
        }

        // ✅ التحقق من أن الدور ضمن الأدوار المسموحة
        $normalizedRoles = array_map('strtolower', $allowedRoles);
        if (!in_array(strtolower($role), $normalizedRoles)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied'
            ]);
            exit;
        }
    }
}
