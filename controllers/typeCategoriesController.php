<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class TypeCategoriesController
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

    /** ✅ Create */
    public function create(array $data)
    {
        if (empty($data['name'])) {
            return $this->respond(false, 'Name is required', null, ['field' => 'name'], 400);
        }

        // تحقق من الاسم المكرر
        $check = $this->conn->prepare("SELECT id FROM type_categories WHERE name = ? LIMIT 1");
        $check->execute([$data['name']]);
        if ($check->fetch()) {
            return $this->respond(false, 'Category name already exists', null, ['field' => 'name'], 409);
        }

        $stmt = $this->conn->prepare("
            INSERT INTO type_categories (name, created_at)
            VALUES (?, NOW())
        ");
        $stmt->execute([$data['name']]);

        return $this->respond(true, 'Category created successfully', ['id' => $this->conn->lastInsertId()], null, 201);
    }

    /** ✅ Update */
    public function update(int $id, array $data)
    {
        if (empty($data['name'])) {
            return $this->respond(false, 'Name is required', null, ['field' => 'name'], 400);
        }

        // تحقق من وجود السجل
        $check = $this->conn->prepare("SELECT id FROM type_categories WHERE id = ? LIMIT 1");
        $check->execute([$id]);
        if (!$check->fetch()) {
            return $this->respond(false, 'Category not found', null, ['code' => 404], 404);
        }

        $stmt = $this->conn->prepare("
            UPDATE type_categories SET name = ? WHERE id = ?
        ");
        $stmt->execute([$data['name'], $id]);

        return $this->respond(true, 'Category updated successfully');
    }

    /** ✅ Delete */
    public function delete(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM type_categories WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $this->respond(false, 'Category not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'Category deleted successfully');
    }

    /** ✅ Get one */
    public function getById(int $id)
    {
        $stmt = $this->conn->prepare("SELECT id, name, created_at FROM type_categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            return $this->respond(false, 'Category not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'Category retrieved successfully', $category);
    }

    /** ✅ Get all (with search & pagination) */
    public function getAll(array $filters = [])
    {
        $query = "SELECT id, name, created_at FROM type_categories WHERE 1";
        $params = [];

        // 🔍 بحث بالاسم
        if (!empty($filters['search'])) {
            $query .= " AND name LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        // 🔢 Pagination
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 100;
        $offset = isset($filters['offset']) ? (int) $filters['offset'] : 0;

        // نمرر LIMIT و OFFSET مباشرة في الاستعلام بدون ?
        $query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params); // فقط المعاملات للبحث
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // إجمالي السجلات (بدون pagination)
        $totalStmt = $this->conn->query("SELECT COUNT(*) FROM type_categories");
        $total = $totalStmt->fetchColumn();

        return $this->respond(true, 'Categories retrieved successfully', [
            'total' => (int) $total,
            'limit' => $limit,
            'offset' => $offset,
            'categories' => $categories
        ]);
    }

}
