<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class EnergyInsulationController
{
    use ApiResponseTrait;

    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function createLicense(array $data)
    {
        try {
            $this->conn->beginTransaction();

            $stmt = $this->conn->prepare("
                INSERT INTO energy_insulation_license (
                    equipment_name, equipment_no, date, reason, license_expiry, 
                    execution_exceeds_shift_time, work_permit, equipment_section_id, 
                    created_by, status, exact_location
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['equipment_name'] ?? null,
                $data['equipment_no'] ?? null,
                $data['date'] ?? null,
                $data['reason'] ?? null,
                $data['license_expiry'] ?? null,
                isset($data['execution_exceeds_shift_time']) ? (int)$data['execution_exceeds_shift_time'] : 0,
                $data['work_permit'] ?? null,
                $data['equipment_section_id'] ?? null,
                $data['created_by'] ?? null,
                'pending',
                $data['exact_location'] ?? null
            ]);

            $licenseId = $this->conn->lastInsertId();

            // Insert Energy Types
            if (!empty($data['energy_types'])) {
                $energyStmt = $this->conn->prepare("INSERT INTO energy_insulation_energy_types (license_id, energy_type_id) VALUES (?, ?)");
                foreach ($data['energy_types'] as $typeId) {
                    $energyStmt->execute([$licenseId, $typeId]);
                }
            }

            // Insert Equipments
            if (!empty($data['equipments'])) {
                $equipStmt = $this->conn->prepare("INSERT INTO energy_insulation_equipments (license_id, equipment_id, equipment_no) VALUES (?, ?, ?)");
                foreach ($data['equipments'] as $equip) {
                    $equipStmt->execute([$licenseId, $equip['id'], $equip['no']]);
                }
            }

            // Insert Staff
            if (!empty($data['staff'])) {
                $staffStmt = $this->conn->prepare("INSERT INTO energy_insulation_staff (license_id, user_id) VALUES (?, ?)");
                foreach ($data['staff'] as $userId) {
                    $staffStmt->execute([$licenseId, $userId]);
                }
            }

            // Update Area Manager
            if (!empty($data['area_manager_id'])) {
                $updateStmt = $this->conn->prepare("UPDATE energy_insulation_license SET area_manager_id = ? WHERE id = ?");
                $updateStmt->execute([$data['area_manager_id'], $licenseId]);
            }

            $this->conn->commit();
            return $this->respond(true, 'Energy Insulation License created successfully', ['id' => $licenseId]);
        } catch (Exception $e) {
            $this->conn->rollBack();
            return $this->respond(false, 'Failed to create license: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
    }

    public function getEligibleUsers()
    {
        $stmt = $this->conn->prepare("SELECT id, name FROM users WHERE role_id IN (3, 5, 7) AND is_active = 1");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->respond(true, 'Eligible users retrieved successfully', $users);
    }

    public function getEquipmentsBySection(int $sectionId)
    {
        $stmt = $this->conn->prepare("SELECT id, name, image FROM equipments WHERE section_id = ?");
        $stmt->execute([$sectionId]);
        $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->respond(true, 'Equipments retrieved successfully', $equipments);
    }
}
