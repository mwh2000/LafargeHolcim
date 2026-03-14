<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class EnergyTypeController
{
    use ApiResponseTrait;

    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(array $filters = [])
    {
        $query = "SELECT id, name, created_at FROM energy_types WHERE 1";
        $params = [];

        if (!empty($filters['search'])) {
            $query .= " AND name LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $query .= " ORDER BY id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalStmt = $this->conn->query("SELECT COUNT(*) FROM energy_types");
        $total = $totalStmt->fetchColumn();

        return $this->respond(true, 'Energy types retrieved successfully', [
            'total' => (int) $total,
            'types' => $types
        ]);
    }

    public function getById(int $id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM energy_types WHERE id = ?");
        $stmt->execute([$id]);
        $type = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$type) {
            return $this->respond(false, 'Energy type not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'Energy type retrieved successfully', $type);
    }

    public function create(array $data)
    {
        if (empty($data['name'])) {
            return $this->respond(false, 'Name is required', null, ['code' => 400], 400);
        }

        $stmt = $this->conn->prepare("INSERT INTO energy_types (name) VALUES (?)");
        $success = $stmt->execute([$data['name']]);

        if ($success) {
            $id = $this->conn->lastInsertId();
            return $this->respond(true, 'Energy type created successfully', ['id' => $id]);
        }

        return $this->respond(false, 'Failed to create energy type', null, ['code' => 500], 500);
    }

    public function update(int $id, array $data)
    {
        if (empty($data['name'])) {
            return $this->respond(false, 'Name is required', null, ['code' => 400], 400);
        }

        $stmt = $this->conn->prepare("UPDATE energy_types SET name = ? WHERE id = ?");
        $success = $stmt->execute([$data['name'], $id]);

        if ($success) {
            return $this->respond(true, 'Energy type updated successfully');
        }

        return $this->respond(false, 'Failed to update energy type', null, ['code' => 500], 500);
    }

    public function delete(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM energy_types WHERE id = ?");
        $success = $stmt->execute([$id]);

        if ($success) {
            return $this->respond(true, 'Energy type deleted successfully');
        }

        return $this->respond(false, 'Failed to delete energy type', null, ['code' => 500], 500);
    }
}
