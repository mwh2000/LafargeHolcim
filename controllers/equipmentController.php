<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class EquipmentController
{
    use ApiResponseTrait;

    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(array $filters = [])
    {
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 10;
        $offset = ($page - 1) * $limit;

        $query = "
            SELECT e.id, e.section_id, e.name, e.image, e.created_at, es.name AS section_name 
            FROM equipments e
            LEFT JOIN equipment_sections es ON e.section_id = es.id
            WHERE 1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND (e.name LIKE ? OR es.name LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['section_id'])) {
            $query .= " AND e.section_id = ?";
            $params[] = $filters['section_id'];
        }

        $query .= " ORDER BY e.id DESC";
        $query .= " LIMIT $limit OFFSET $offset";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalQuery = "
            SELECT COUNT(*) 
            FROM equipments e
            LEFT JOIN equipment_sections es ON e.section_id = es.id
            WHERE 1
        ";
        $totalParams = [];
        
        if (!empty($filters['search'])) {
            $totalQuery .= " AND (e.name LIKE ? OR es.name LIKE ?)";
            $totalParams[] = '%' . $filters['search'] . '%';
            $totalParams[] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['section_id'])) {
            $totalQuery .= " AND e.section_id = ?";
            $totalParams[] = $filters['section_id'];
        }

        $totalStmt = $this->conn->prepare($totalQuery);
        $totalStmt->execute($totalParams);
        $total = $totalStmt->fetchColumn();

        return $this->respond(true, 'Equipments retrieved successfully', [
            'total' => (int) $total,
            'page' => $page,
            'limit' => $limit,
            'equipments' => $equipments
        ]);
    }

    public function getById(int $id)
    {
        $stmt = $this->conn->prepare("
            SELECT e.*, es.name AS section_name 
            FROM equipments e 
            LEFT JOIN equipment_sections es ON e.section_id = es.id 
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$equipment) {
            return $this->respond(false, 'Equipment not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'Equipment retrieved successfully', $equipment);
    }

    public function create(array $data)
    {
        if (empty($data['name'])) {
            return $this->respond(false, 'Equipment Name is required', null, ['code' => 400], 400);
        }
        if (empty($data['section_id'])) {
            return $this->respond(false, 'Equipment Section is required', null, ['code' => 400], 400);
        }

        $imagePath = $this->handleUpload($data['image_file'] ?? null);

        $stmt = $this->conn->prepare("INSERT INTO equipments (section_id, name, image) VALUES (?, ?, ?)");
        $success = $stmt->execute([$data['section_id'], $data['name'], $imagePath]);

        if ($success) {
            $id = $this->conn->lastInsertId();
            return $this->respond(true, 'Equipment created successfully', ['id' => $id, 'image' => $imagePath]);
        }

        return $this->respond(false, 'Failed to create equipment', null, ['code' => 500], 500);
    }

    public function update(int $id, array $data)
    {
        if (empty($data['name'])) {
            return $this->respond(false, 'Equipment Name is required', null, ['code' => 400], 400);
        }
        if (empty($data['section_id'])) {
            return $this->respond(false, 'Equipment Section is required', null, ['code' => 400], 400);
        }

        $currentEquipment = $this->getById($id);
        $imagePath = $currentEquipment['data']['image'] ?? null;

        if (!empty($data['image_file'])) {
            $newImage = $this->handleUpload($data['image_file']);
            if ($newImage) {
                // Delete old image if exists
                if ($imagePath && file_exists(__DIR__ . '/../public/' . $imagePath)) {
                    @unlink(__DIR__ . '/../public/' . $imagePath);
                }
                $imagePath = $newImage;
            }
        }

        $stmt = $this->conn->prepare("UPDATE equipments SET section_id = ?, name = ?, image = ? WHERE id = ?");
        $success = $stmt->execute([$data['section_id'], $data['name'], $imagePath, $id]);

        if ($success) {
            return $this->respond(true, 'Equipment updated successfully', ['image' => $imagePath]);
        }

        return $this->respond(false, 'Failed to update equipment', null, ['code' => 500], 500);
    }

    public function delete(int $id)
    {
        $currentEquipment = $this->getById($id);
        $imagePath = $currentEquipment['data']['image'] ?? null;

        $stmt = $this->conn->prepare("DELETE FROM equipments WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success) {
            if ($imagePath && file_exists(__DIR__ . '/../public/' . $imagePath)) {
                @unlink(__DIR__ . '/../public/' . $imagePath);
            }
            return $this->respond(true, 'Equipment deleted successfully');
        }

        return $this->respond(false, 'Failed to delete equipment', null, ['code' => 500], 500);
    }

    private function handleUpload($file)
    {
        if (!$file || !isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return null;
        }

        $uploadDir = __DIR__ . '/../public/uploads/equipments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('eq_') . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/equipments/' . $filename;
        }

        return null;
    }
}
