<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class RolesController
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * 🔸 Standard JSON response
     */
    use ApiResponseTrait;

    /** ✅ Get one */
    // public function getById(int $id)
    // {
    //     $stmt = $this->conn->prepare("SELECT id, name, created_at FROM type_categories WHERE id = ?");
    //     $stmt->execute([$id]);
    //     $category = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if (!$category) {
    //         return $this->respond(false, 'Category not found', null, ['code' => 404], 404);
    //     }

    //     return $this->respond(true, 'Category retrieved successfully', $category);
    // }

    /** ✅ Get all (with search & pagination) */
    public function getAll(array $filters = [])
    {
        $query = "SELECT id, name FROM roles WHERE 1";
        $params = [];

        // 🔍 بحث بالاسم
        if (!empty($filters['search'])) {
            $query .= " AND name LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params); // فقط المعاملات للبحث
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // إجمالي السجلات (بدون pagination)
        $totalStmt = $this->conn->query("SELECT COUNT(*) FROM roles");
        $total = $totalStmt->fetchColumn();

        return $this->respond(true, 'Roles retrieved successfully', [
            'total' => (int) $total,
            'roles' => $roles
        ]);
    }

}
