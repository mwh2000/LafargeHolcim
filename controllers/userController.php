<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class UserController
{
    private $conn;
    private $jwtHandler;

    public function __construct($conn, $jwtHandler)
    {
        $this->conn = $conn;
        $this->jwtHandler = $jwtHandler;
    }

    /** 🔸 Standard JSON response */
    use ApiResponseTrait;

    /** ✅ Create user */
    public function create(array $data, $decodedAdmin)
    {
        if (empty($decodedAdmin) || $decodedAdmin->role_id != 1) {
            return $this->respond(false, 'Unauthorized access', null, ['code' => 403], 403);
        }

        if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['role_id'])) {
            return $this->respond(false, 'Missing required fields', null, ['code' => 400], 400);
        }

        // Check for duplicate email
        $check = $this->conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->execute([$data['email']]);
        if ($check->fetch()) {
            return $this->respond(false, 'Email already exists', null, ['code' => 409], 409);
        }

        // Insert user
        $stmt = $this->conn->prepare("
            INSERT INTO users (name, email, password, phone, department, `group`, manager_id, time_target, role_id, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['phone'] ?? null,
            $data['department'] ?? null,
            $data['group'] ?? null,
            $data['manager_id'] ?? null,
            $data['time_target'] ?? null,
            $data['role_id'] ?? 2,
            isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);

        $userId = $this->conn->lastInsertId();
        return $this->respond(true, 'User created successfully', ['user_id' => $userId], null, 201);
    }

    /** ✅ Update user */
    public function update(int $id, array $data, $decodedAdmin)
    {
        if (empty($decodedAdmin) || $decodedAdmin->role_id != 1) {
            return $this->respond(false, 'Unauthorized', null, ['code' => 403], 403);
        }

        $fields = [];
        $values = [];

        // Include new fields
        foreach (['name', 'email', 'phone', 'department', 'manager_id', 'time_target', 'role_id', 'is_active'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return $this->respond(false, 'No fields to update', null, ['code' => 400], 400);
        }

        $values[] = $id;
        $sql = "UPDATE users SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($values);

        if ($stmt->rowCount() === 0) {
            return $this->respond(false, 'User not found or no changes applied', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'User updated successfully');
    }

    /** ✅ Delete user */
    public function delete(int $id, $decodedAdmin)
    {
        if (empty($decodedAdmin) || $decodedAdmin->role_id != 1) {
            return $this->respond(false, 'Unauthorized', null, ['code' => 403], 403);
        }

        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $this->respond(false, 'User not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'User deleted successfully');
    }

    /** ✅ Get user by ID */
    public function getById(int $id)
    {
        $stmt = $this->conn->prepare("
            SELECT 
                u.id, u.name, u.email, u.phone, u.department, 
                u.manager_id, m.name AS manager_name,
                u.time_target, u.is_active, u.created_at, 
                r.name AS role_name
            FROM users u
            LEFT JOIN users m ON u.manager_id = m.id
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return $this->respond(false, 'User not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'User retrieved successfully', $user);
    }

    /** ✅ Get all users (with new fields) */
    public function getAll(array $filters = [])
    {
        $query = "
        SELECT 
            u.id, u.name, u.email, u.phone, u.department, 
            u.manager_id, m.name AS manager_name,
            u.manager_id, m.email AS manager_email,
            u.time_target, u.is_active, u.created_at,
            r.name AS role_name
        FROM users u
        LEFT JOIN users m ON u.manager_id = m.id
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE 1
    ";
        $params = [];

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.department LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }

        // ✅ فلترة حسب department
        if (!empty($filters['department'])) {
            $query .= " AND u.department = ?";
            $params[] = $filters['department'];
        }

        if (!empty($filters['role_id'])) {
            $query .= " AND u.role_id = ?";
            $params[] = (int) $filters['role_id'];
        }

        if (isset($filters['is_active'])) {
            $query .= " AND u.is_active = ?";
            $params[] = (int) $filters['is_active'];
        }

        // نضيف limit و offset مباشرة بالاستعلام بعد التأكد أنها أرقام صحيحة
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 200;
        $offset = isset($filters['offset']) ? (int) $filters['offset'] : 0;

        // ما نستخدم placeholders هنا
        $query .= " ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->respond(true, 'Users retrieved successfully', [
            'total' => count($users),
            'limit' => $limit,
            'offset' => $offset,
            'users' => $users
        ]);

    }

}
