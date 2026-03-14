<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class EquipmentSectionController
{
    use ApiResponseTrait;

    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(array $filters = [])
    {
        $query = "SELECT id, name, created_at FROM equipment_sections WHERE 1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND name LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalStmt = $this->conn->query("SELECT COUNT(*) FROM equipment_sections");
        $total = $totalStmt->fetchColumn();

        return $this->respond(true, 'Equipment sections retrieved successfully', [
            'total' => (int) $total,
            'sections' => $sections
        ]);
    }

    public function getById(int $id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM equipment_sections WHERE id = ?");
        $stmt->execute([$id]);
        $section = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$section) {
            return $this->respond(false, 'Equipment section not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'Equipment section retrieved successfully', $section);
    }

    public function create(array $data)
    {
        if (empty($data['name'])) {
            return $this->respond(false, 'Name is required', null, ['code' => 400], 400);
        }

        $stmt = $this->conn->prepare("INSERT INTO equipment_sections (name) VALUES (?)");
        $success = $stmt->execute([$data['name']]);

        if ($success) {
            $id = $this->conn->lastInsertId();
            return $this->respond(true, 'Equipment section created successfully', ['id' => $id]);
        }

        return $this->respond(false, 'Failed to create equipment section', null, ['code' => 500], 500);
    }

    public function update(int $id, array $data)
    {
        if (empty($data['name'])) {
            return $this->respond(false, 'Name is required', null, ['code' => 400], 400);
        }

        $stmt = $this->conn->prepare("UPDATE equipment_sections SET name = ? WHERE id = ?");
        $success = $stmt->execute([$data['name'], $id]);

        if ($success) {
            return $this->respond(true, 'Equipment section updated successfully');
        }

        return $this->respond(false, 'Failed to update equipment section', null, ['code' => 500], 500);
    }

    public function delete(int $id)
    {
        // Check if there are equipments linked to this section
        $checkStmt = $this->conn->prepare("SELECT COUNT(*) FROM equipments WHERE section_id = ?");
        $checkStmt->execute([$id]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            return $this->respond(false, 'Cannot delete this section because it contains equipments', null, ['code' => 400], 400);
        }

        $stmt = $this->conn->prepare("DELETE FROM equipment_sections WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success) {
            return $this->respond(true, 'Equipment section deleted successfully');
        }

        return $this->respond(false, 'Failed to delete equipment section', null, ['code' => 500], 500);
    }
}
