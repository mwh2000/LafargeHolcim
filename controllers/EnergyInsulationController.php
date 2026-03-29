<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class EnergyInsulationController
{
    use ApiResponseTrait;

    private $conn;
    private $notificationController;
    private $emailController;

    public function __construct(PDO $conn, $notificationController = null, $emailController = null)
    {
        $this->conn = $conn;
        $this->notificationController = $notificationController;
        $this->emailController = $emailController;
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
                $staffStmt = $this->conn->prepare("INSERT INTO energy_insulation_staff (license_id, name) VALUES (?, ?)");
                foreach ($data['staff'] as $staffName) {
                    $staffStmt->execute([$licenseId, $staffName]);
                }
            }

            // Update Area Manager
            if (!empty($data['area_manager_id'])) {
                $updateStmt = $this->conn->prepare("UPDATE energy_insulation_license SET area_manager_id = ? WHERE id = ?");
                $updateStmt->execute([$data['area_manager_id'], $licenseId]);
            }

            $this->conn->commit();

            // Send Notification and Email to Area Manager
            if ($this->notificationController && !empty($data['area_manager_id'])) {
                $title = "رخصة عزل طاقة جديدة - New Energy Insulation License";
                $body = "تم إنشاء رخصة عزل طاقة جديدة للمعدة: " . ($data['equipment_name'] ?? 'N/A');
                $url = BASE_URL . "/public/requester/view_energy_license.php?id=" . $licenseId;
                
                $this->notificationController->sendNotification($title, $body, [$data['area_manager_id']], $url, $data['created_by']);
                
                if ($this->emailController) {
                    $this->emailController->sendEmail($title, $body, [$data['area_manager_id']]);
                }
            }

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

    public function getLicenseById(int $id)
    {
        $stmt = $this->conn->prepare("
            SELECT l.*, u.name AS requester_name, am.name AS area_manager_name, io.name AS isolation_officer_name, sl.name AS shift_leader_name, es.name AS section_name
            FROM energy_insulation_license l
            LEFT JOIN users u ON l.created_by = u.id
            LEFT JOIN users am ON l.area_manager_id = am.id
            LEFT JOIN users io ON l.isolation_officer_id = io.id
            LEFT JOIN users sl ON l.shift_leader_id = sl.id
            LEFT JOIN equipment_sections es ON l.equipment_section_id = es.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$license) {
            return $this->respond(false, 'License not found', null, ['code' => 404], 404);
        }

        // Get Energy Types
        $energyStmt = $this->conn->prepare("
            SELECT et.id, et.name 
            FROM energy_insulation_energy_types liet
            JOIN energy_types et ON liet.energy_type_id = et.id
            WHERE liet.license_id = ?
        ");
        $energyStmt->execute([$id]);
        $license['energy_types'] = $energyStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get Equipments
        $equipStmt = $this->conn->prepare("
            SELECT e.id, e.name, e.image, lie.equipment_no
            FROM energy_insulation_equipments lie
            JOIN equipments e ON lie.equipment_id = e.id
            WHERE lie.license_id = ?
        ");
        $equipStmt->execute([$id]);
        $license['equipments'] = $equipStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get Staff
        $staffStmt = $this->conn->prepare("
            SELECT name
            FROM energy_insulation_staff
            WHERE license_id = ?
        ");
        $staffStmt->execute([$id]);
        $license['staff'] = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->respond(true, 'License retrieved successfully', $license);
    }

    public function getIsolationOfficers()
    {
        $stmt = $this->conn->prepare("SELECT id, name FROM users WHERE role_id = 8 AND is_active = 1");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->respond(true, 'Isolation officers retrieved successfully', $users);
    }

    public function updateIsolationOfficer(int $licenseId, int $officerId, int $updatedBy)
    {
        // Fetch license info for notification
        $stmt = $this->conn->prepare("SELECT equipment_name FROM energy_insulation_license WHERE id = ?");
        $stmt->execute([$licenseId]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->conn->prepare("UPDATE energy_insulation_license SET isolation_officer_id = ?, status = 'approved_by_am' WHERE id = ?");
        $success = $stmt->execute([$officerId, $licenseId]);

        if ($success) {
            // Send Notification and Email to Isolation Officer
            if ($this->notificationController) {
                $title = "رخصة عزل طاقة بانتظار مراجعتك - Energy Insulation License Pending Review";
                $body = "تم تعيينك كمسؤول عزل للرخصة الخاصة بالمعدة: " . ($license['equipment_name'] ?? 'N/A');
                $url = BASE_URL . "/public/requester/view_energy_license.php?id=" . $licenseId;
                
                $this->notificationController->sendNotification($title, $body, [$officerId], $url, $updatedBy);
                
                if ($this->emailController) {
                    $this->emailController->sendEmail($title, $body, [$officerId]);
                }
            }
            return $this->respond(true, 'Isolation officer assigned successfully');
        }
        return $this->respond(false, 'Failed to assign isolation officer', null, ['code' => 500], 500);
    }

    public function getShiftLeaders()
    {
        $stmt = $this->conn->prepare("SELECT id, name FROM users WHERE role_id = 7 AND is_active = 1");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->respond(true, 'Shift leaders retrieved successfully', $users);
    }

    public function confirmByIsolationOfficer(int $licenseId, int $shiftLeaderId, int $updatedBy)
    {
        // Fetch license info for notification
        $stmt = $this->conn->prepare("SELECT equipment_name FROM energy_insulation_license WHERE id = ?");
        $stmt->execute([$licenseId]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->conn->prepare("UPDATE energy_insulation_license SET shift_leader_id = ?, status = 'reviewed_by_io' WHERE id = ?");
        $success = $stmt->execute([$shiftLeaderId, $licenseId]);

        if ($success) {
            // Send Notification and Email to Shift Leader
            if ($this->notificationController) {
                $title = "رخصة عزل طاقة بانتظار تأكيدك - Energy Insulation License Pending Confirmation";
                $body = "تم عزل جميع المعدات  عزل الطاقه المطلوبة  من قبل مسوول العزل وهي بإنتظار تأكيدك على الرخصة";
                $url = BASE_URL . "/public/requester/view_energy_license.php?id=" . $licenseId;
                
                $this->notificationController->sendNotification($title, $body, [$shiftLeaderId], $url, $updatedBy);
                
                if ($this->emailController) {
                    $this->emailController->sendEmail($title, $body, [$shiftLeaderId]);
                }
            }
            return $this->respond(true, 'License confirmed by isolation officer successfully');
        }
        return $this->respond(false, 'Failed to confirm license', null, ['code' => 500], 500);
    }

    public function confirmByShiftLeader(int $licenseId, int $updatedBy)
    {
        // Fetch license info for notifications
        $stmt = $this->conn->prepare("SELECT created_by, area_manager_id, equipment_name FROM energy_insulation_license WHERE id = ?");
        $stmt->execute([$licenseId]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$license) {
            return $this->respond(false, 'License not found', null, ['code' => 404], 404);
        }

        $stmt = $this->conn->prepare("UPDATE energy_insulation_license SET status = 'completed', end_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$licenseId]);

        if ($success) {
            // Send Notification and Email to Area Manager and Requester
            if ($this->notificationController) {
                $title = "رخصة عزل طاقة مكتملة - Energy Insulation License Completed";
                $body = "تم إكمال إجراءات رخصة عزل الطاقة للمعدة: " . ($license['equipment_name'] ?? 'N/A');
                $url = BASE_URL . "/public/requester/view_energy_license.php?id=" . $licenseId;
                
                $recipients = [$license['created_by'], $license['area_manager_id']];
                $this->notificationController->sendNotification($title, $body, $recipients, $url, $updatedBy);
                
                if ($this->emailController) {
                    $this->emailController->sendEmail($title, $body, $recipients);
                }
            }
            return $this->respond(true, 'License completed successfully');
        }
        return $this->respond(false, 'Failed to complete license', null, ['code' => 500], 500);
    }

    public function rejectLicense(int $licenseId, string $reason, int $rejectedBy)
    {
        if (empty($reason)) {
            return $this->respond(false, 'Rejection reason is required', null, ['code' => 400], 400);
        }

        // Fetch license info to notify the requester
        $stmt = $this->conn->prepare("SELECT created_by, equipment_name FROM energy_insulation_license WHERE id = ?");
        $stmt->execute([$licenseId]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$license) {
            return $this->respond(false, 'License not found', null, ['code' => 404], 404);
        }

        $stmt = $this->conn->prepare("UPDATE energy_insulation_license SET reject_reason = ?, status = 'rejected' WHERE id = ?");
        $success = $stmt->execute([$reason, $licenseId]);

        if ($success) {
            // Send Notification and Email to Requester
            if ($this->notificationController) {
                $title = "رخصة عزل طاقة مرفوضة - Energy Insulation License Rejected";
                $body = "تم رفض رخصة عزل الطاقة للمعدة: " . ($license['equipment_name'] ?? 'N/A') . ". السبب: " . $reason;
                $url = BASE_URL . "/public/requester/view_energy_license.php?id=" . $licenseId;
                
                $this->notificationController->sendNotification($title, $body, [$license['created_by']], $url, $rejectedBy);
                
                if ($this->emailController) {
                    $this->emailController->sendEmail($title, $body, [$license['created_by']]);
                }
            }
            return $this->respond(true, 'License rejected successfully');
        }
        return $this->respond(false, 'Failed to reject license', null, ['code' => 500], 500);
    }
}
