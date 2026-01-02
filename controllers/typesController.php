<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class TypesController
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

        if (empty($data['category_id'])) {
            return $this->respond(false, 'Category is required', null, ['field' => 'category_id'], 400);
        }

        // 🔍 تحقق من أن التصنيف موجود فعلاً
        $categoryCheck = $this->conn->prepare("SELECT id FROM type_categories WHERE id = ? LIMIT 1");
        $categoryCheck->execute([$data['category_id']]);
        if (!$categoryCheck->fetch()) {
            return $this->respond(false, 'Invalid category', null, ['field' => 'category_id'], 404);
        }

        // 🔍 تحقق من الاسم المكرر داخل نفس التصنيف
        $check = $this->conn->prepare("SELECT id FROM types WHERE name = ? AND category_id = ? LIMIT 1");
        $check->execute([$data['name'], $data['category_id']]);
        if ($check->fetch()) {
            return $this->respond(false, 'Type name already exists in this category', null, ['field' => 'name'], 409);
        }

        // ✅ تنفيذ الإدخال
        $stmt = $this->conn->prepare("
        INSERT INTO types (category_id, name, created_at)
        VALUES (?, ?, NOW())
    ");
        $stmt->execute([
            $data['category_id'],
            $data['name']
        ]);

        return $this->respond(true, 'Type created successfully', [
            'id' => $this->conn->lastInsertId()
        ], null, 201);
    }


    /** ✅ Update */
    public function update(int $id, array $data)
    {
        if (empty($data['name'])) {
            return $this->respond(false, 'Name is required', null, ['field' => 'name'], 400);
        }

        if (empty($data['category_id'])) {
            return $this->respond(false, 'Category is required', null, ['field' => 'category'], 400);
        }

        // تحقق من وجود السجل
        $check = $this->conn->prepare("SELECT id FROM types WHERE id = ? LIMIT 1");
        $check->execute([$id]);
        if (!$check->fetch()) {
            return $this->respond(false, 'Type not found', null, ['code' => 404], 404);
        }

        $stmt = $this->conn->prepare("
            UPDATE types SET category_id = ? AND name = ? WHERE id = ?
        ");
        $stmt->execute([$data['category_id'], $id]);
        $stmt->execute([$data['name'], $id]);

        return $this->respond(true, 'Type updated successfully');
    }

    /** ✅ Delete */
    public function delete(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM types WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $this->respond(false, 'Type not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'Type deleted successfully');
    }

    /** ✅ Get one */
    public function getById(int $id)
    {
        $stmt = $this->conn->prepare("SELECT id, name, created_at FROM types WHERE id = ?");
        $stmt->execute([$id]);
        $type = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$type) {
            return $this->respond(false, 'Type not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'Type retrieved successfully', $type);
    }

    /** ✅ Get all (with search & pagination) */
    public function getAll(array $filters = [])
    {
        // استعلام الفئات
        $query = "SELECT id, name FROM type_categories WHERE 1";
        $params = [];

        // 🔍 بحث بالاسم
        if (!empty($filters['search'])) {
            $query .= " AND name LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        // 🔢 Pagination
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 100;
        $offset = isset($filters['offset']) ? (int) $filters['offset'] : 0;

        $query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // لو ماكو كاتيجوريز نرجع فارغة
        if (!$categories) {
            return $this->respond(true, 'No categories found', [
                'total' => 0,
                'limit' => $limit,
                'offset' => $offset,
                'categories' => []
            ]);
        }

        // 🔗 نجلب كل أنواع مرة واحدة لتقليل الاستعلامات
        $typeStmt = $this->conn->query("SELECT id, name, category_id FROM types");
        $allTypes = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

        // نرتب الأنواع حسب الكاتيجوري
        $typesByCategory = [];
        foreach ($allTypes as $type) {
            $typesByCategory[$type['category_id']][] = [
                'id' => $type['id'],
                'name' => $type['name']
            ];
        }

        // نضيف الأنواع داخل كل كاتيجوري
        foreach ($categories as &$category) {
            $category['types'] = $typesByCategory[$category['id']] ?? [];
        }

        // إجمالي السجلات (بدون pagination)
        $totalStmt = $this->conn->query("SELECT COUNT(*) FROM type_categories");
        $total = $totalStmt->fetchColumn();

        return $this->respond(true, 'Categories and types retrieved successfully', [
            'total' => (int) $total,
            'limit' => $limit,
            'offset' => $offset,
            'categories' => $categories
        ]);
    }


}
