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
        $query = "
            SELECT e.id, e.section_id, e.name, e.created_at, es.name AS section_name 
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

        $stmt = $this->conn->prepare("INSERT INTO equipments (section_id, name) VALUES (?, ?)");
        $success = $stmt->execute([$data['section_id'], $data['name']]);

        if ($success) {
            $id = $this->conn->lastInsertId();
            return $this->respond(true, 'Equipment created successfully', ['id' => $id]);
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

        $stmt = $this->conn->prepare("UPDATE equipments SET section_id = ?, name = ? WHERE id = ?");
        $success = $stmt->execute([$data['section_id'], $data['name'], $id]);

        if ($success) {
            return $this->respond(true, 'Equipment updated successfully');
        }

        return $this->respond(false, 'Failed to update equipment', null, ['code' => 500], 500);
    }

    public function delete(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM equipments WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success) {
            return $this->respond(true, 'Equipment deleted successfully');
        }

        return $this->respond(false, 'Failed to delete equipment', null, ['code' => 500], 500);
    }
}
