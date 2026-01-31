<?php
session_start();
require_once __DIR__ . '/../core/JWTHandler.php';
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class AuthController
{
    private $conn;
    private $jwtHandler;

    public function __construct($conn, $jwtHandler)
    {
        $this->conn = $conn;
        $this->jwtHandler = $jwtHandler;
    }

    /**
     * 🔹 Standard JSON response
     */
    use ApiResponseTrait;

    /**
     * 🔹 تسجيل الدخول
     * @param array $data ['email', 'password']
     */
    public function login(array $data)
    {
        if (empty($data['email']) || empty($data['password'])) {
            return $this->respond(false, 'Email and password are required', null, ['code' => 400], 400);
        }

        $stmt = $this->conn->prepare("
            SELECT u.id, u.name, u.email, u.password, u.role_id, u.`group`, u.is_active, u.department, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.email = ? LIMIT 1
        ");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return $this->respond(false, 'Invalid credentials', null, ['code' => 401], 401);
        }

        if (!password_verify($data['password'], $user['password'])) {
            // ممكن تزيد عداد المحاولات الفاشلة هنا
            return $this->respond(false, 'Invalid credentials', null, ['code' => 401], 401);
        }

        if (!$user['is_active']) {
            return $this->respond(false, 'Account is inactive', null, ['code' => 403], 403);
        }

        // توليد JWT
        $tokenData = $this->jwtHandler->generateToken([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role_id' => $user['role_id']
        ]);

        $expire = time() + 365 * 24 * 60 * 60; // سنة

        setcookie('user_id', $user['id'], $expire, '/', '', false, true);
        setcookie('token', $tokenData['token'], $expire, '/', '', false, true);
        setcookie('user_type', $user['role_id'], $expire, '/', '', false, true);
        // save all user data except password in cookie
        setcookie('user_data', json_encode([
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'group' => $user['group'],
            'department' => $user['department']
        ]), $expire, '/', '', false, true);


        return $this->respond(true, 'Login successful', [
            'token' => $tokenData['token'],
            'expires_at' => $tokenData['exp'],
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role_id' => $user['role_id'],
                'role_name' => $user['role_name']
            ]
        ]);
    }

    /**
     * 🔹 مثال تسجيل الخروج
     * في JWT stateless لا تحتاج حذف السيرفر توكن، مجرد حذف من client
     */
    public function logout()
    {
        return $this->respond(true, 'Logout successful');
    }

    /**
     * 🔹 مثال إعادة توليد توكن (refresh token)
     * يمكن توسعه لاحقًا إذا أردت دعم refresh token
     */
    public function refreshToken($decoded)
    {
        if (!$decoded) {
            return $this->respond(false, 'Invalid token', null, ['code' => 401], 401);
        }

        $tokenData = $this->jwtHandler->generateToken([
            'id' => $decoded->id,
            'name' => $decoded->name,
            'email' => $decoded->email,
            'role_id' => $decoded->role_id
        ]);

        return $this->respond(true, 'Token refreshed', [
            'token' => $tokenData['token'],
            'expires_at' => $tokenData['exp']
        ]);
    }
}
