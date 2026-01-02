<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/JWTHandler.php';

$config = require __DIR__ . '/config/config.php';
// إعدادات الاتصال
$database = new Database($config['db']);
$conn = $database->getConnection();
$jwtHandler = new JWTHandler($config['jwt_secret']);

// بيانات Super Admin
$email = 'superadmin@example.com';
$password_plain = '12345678';
$name = 'Super Admin';
$phone = '07700000000';
$role_id = 1; // super_admin

// تحقق إذا موجود مسبقًا
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Super Admin موجود مسبقًا في قاعدة البيانات.\n";
} else {
    // إنشاء حساب جديد
    $password_hashed = password_hash($password_plain, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("
        INSERT INTO users (name, email, password, phone, role_id, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$name, $email, $password_hashed, $phone, $role_id]);
    echo "تم إنشاء Super Admin بنجاح.\n";
}

// توليد JWT
$tokenData = $jwtHandler->generateToken([
    'id' => $user['id'] ?? $conn->lastInsertId(),
    'name' => $name,
    'email' => $email,
    'role_id' => $role_id
]);

echo "\n======= بيانات تسجيل الدخول =======\n";
echo "Email: $email\n";
echo "Password: $password_plain\n";
echo "JWT Token:\n" . $tokenData['token'] . "\n";
echo "Expires at (timestamp): " . $tokenData['exp'] . "\n";
echo "==================================\n";
