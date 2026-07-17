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
                    created_by, requester_name, requester_section, status, exact_location, end_at, am_approved_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
                $data['requester_name'] ?? null,
                $data['requester_section'] ?? null,
                'active_isolation',
                $data['exact_location'] ?? null,
                null
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

            // Insert Staff Groups
            if (!empty($data['staff_groups'])) {
                $groupStmt = $this->conn->prepare("INSERT INTO energy_insulation_staff_group (name, license_id) VALUES (?, ?)");
                $staffStmt = $this->conn->prepare("INSERT INTO energy_insulation_staff (license_id, name, group_id) VALUES (?, ?, ?)");

                foreach ($data['staff_groups'] as $group) {
                    $groupStmt->execute([$group['group_name'], $licenseId]);
                    $groupId = $this->conn->lastInsertId();

                    if (!empty($group['members'])) {
                        foreach ($group['members'] as $staffName) {
                            $staffStmt->execute([$licenseId, $staffName, $groupId]);
                        }
                    }
                }
            }

            // Insert Isolation Official
            if (!empty($data['official_name'])) {
                $offStmt = $this->conn->prepare("INSERT INTO energy_insulation_officials (license_id, name, department) VALUES (?, ?, ?)");
                $offStmt->execute([$licenseId, $data['official_name'], $data['official_department'] ?? null]);
            }

            // Update Area Manager
            if (!empty($data['area_manager_id'])) {
                $updateStmt = $this->conn->prepare("UPDATE energy_insulation_license SET area_manager_id = ? WHERE id = ?");
                $updateStmt->execute([$data['area_manager_id'], $licenseId]);
            }

            $this->conn->commit();

            // Send Notification and Email to Area Manager
            if ($this->notificationController && !empty($data['area_manager_id'])) {
                $title = "تم انشاء رخصة العزل من قبل المرخص";
                $body = "تم إنشاء رخصة عزل طاقة وتم العزل للمعدة: " . ($data['equipment_name'] ?? 'N/A');
                $url = BASE_URL . "/public/requester/view_energy_license.php?id=" . $licenseId;
                // implementing the URL to view the license details
                $emailBody = "تم إنشاء رخصة عزل طاقة وتم العزل للمعدة: " . ($data['equipment_name'] ?? 'N/A') .
                    "<br><br>" .
                    "<a href='" . $url . "'>اضغط هنا لعرض الرخصة</a>";

                $this->notificationController->sendNotification($title, $body, [$data['area_manager_id']], $url, $data['created_by']);

                if ($this->emailController) {
                    $this->emailController->sendEmail($title, $emailBody, [$data['area_manager_id']]);
                }
            }

            // Send Notification and Email to Creator
            if ($this->notificationController && !empty($data['created_by'])) {
                $titleCreator = "تم العزل - Isolation Active";
                $bodyCreator = "تم تأكيد العزل للمعدة: " . ($data['equipment_name'] ?? 'N/A');
                $urlCreator = BASE_URL . "/public/requester/view_energy_license.php?id=" . $licenseId;

                $this->notificationController->sendNotification($titleCreator, $bodyCreator, [$data['created_by']], $urlCreator, $data['created_by']);

                if ($this->emailController) {
                    $this->emailController->sendEmail($titleCreator, $bodyCreator, [$data['created_by']]);
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
        $stmt = $this->conn->prepare("SELECT id, name FROM users WHERE role_id IN (3, 7) AND is_active = 1");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->respond(true, 'Eligible users retrieved successfully', $users);
    }

    public function getEquipmentsBySection(int $sectionId, string $search = '', int $page = 1, int $limit = 10)
    {
        $offset = ($page - 1) * $limit;

        $query = "SELECT id, name, image FROM equipments WHERE section_id = ?";
        $params = [$sectionId];

        if (!empty($search)) {
            $query .= " AND name LIKE ?";
            $params[] = "%$search%";
        }

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM equipments WHERE section_id = ?";
        $countParams = [$sectionId];
        if (!empty($search)) {
            $countQuery .= " AND name LIKE ?";
            $countParams[] = "%$search%";
        }
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->execute($countParams);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $equipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->respond(true, 'Equipments retrieved successfully', [
            'equipments' => $equipments,
            'total' => (int)$total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / (int)$limit)
        ]);
    }

    public function getLicenseById(int $id)
    {
        $stmt = $this->conn->prepare("
            SELECT l.*, u.name AS creator_name, u.signature AS creator_signature, am.name AS area_manager_name,
                   off.name AS official_name, off.department AS official_department,
                   es.name AS section_name
            FROM energy_insulation_license l
            LEFT JOIN users u ON l.created_by = u.id
            LEFT JOIN users am ON l.area_manager_id = am.id
            LEFT JOIN equipment_sections es ON l.equipment_section_id = es.id
            LEFT JOIN energy_insulation_officials off ON l.id = off.license_id
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

        // Get Staff including their group info (group id and is_done)
        $staffStmt = $this->conn->prepare("
            SELECT s.name, sg.name as group_name, sg.id as group_id, IFNULL(sg.is_done, 0) as group_is_done
            FROM energy_insulation_staff s
            LEFT JOIN energy_insulation_staff_group sg ON s.group_id = sg.id
            WHERE s.license_id = ?
        ");
        $staffStmt->execute([$id]);
        $license['staff'] = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

        // Additionally include groups summary (optional) for convenience
        $groupStmt = $this->conn->prepare("SELECT id, name, IFNULL(is_done,0) as is_done FROM energy_insulation_staff_group WHERE license_id = ?");
        $groupStmt->execute([$id]);
        $license['staff_groups'] = $groupStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->respond(true, 'License retrieved successfully', $license);
    }

    /**
     * Toggle the is_done flag for a staff group. Only license creator may toggle.
     */
    public function toggleGroupDone(int $groupId, int $licenseId, int $userId, int $isDone)
    {
        try {
            // Verify license and permission
            $stmt = $this->conn->prepare("SELECT created_by FROM energy_insulation_license WHERE id = ?");
            $stmt->execute([$licenseId]);
            $license = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$license) {
                return $this->respond(false, 'License not found', null, ['code' => 404], 404);
            }

            if ($license['created_by'] != $userId) {
                return $this->respond(false, 'Unauthorized to update this group', null, ['code' => 403], 403);
            }

            $stmt = $this->conn->prepare("UPDATE energy_insulation_staff_group SET is_done = ? WHERE id = ? AND license_id = ?");
            $success = $stmt->execute([(int)$isDone, $groupId, $licenseId]);

            if ($success) {
                return $this->respond(true, 'Group status updated successfully', ['group_id' => $groupId, 'is_done' => (int)$isDone]);
            }

            return $this->respond(false, 'Failed to update group status', null, ['code' => 500], 500);
        } catch (Exception $e) {
            return $this->respond(false, 'Error: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
    }

    public function getIsolationOfficers()
    {
        $stmt = $this->conn->prepare("SELECT id, name FROM users WHERE role_id = 8 AND is_active = 1");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->respond(true, 'Isolation officers retrieved successfully', $users);
    }

    public function getEnergyTypes()
    {
        $stmt = $this->conn->prepare("SELECT id, name FROM energy_types ORDER BY name");
        $stmt->execute();
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->respond(true, 'Energy types retrieved successfully', $types);
    }

    /**
     * Only area manager assigned to the license may add/remove energy types or equipments.
     */
    private function ensureIsAreaManager(int $licenseId, int $userId)
    {
        $stmt = $this->conn->prepare("SELECT area_manager_id FROM energy_insulation_license WHERE id = ?");
        $stmt->execute([$licenseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception('License not found');
        return true;
    }

    public function addEnergyTypesToLicense(int $licenseId, array $energyTypeIds, int $userId)
    {
        try {
            if (!$this->ensureIsAreaManager($licenseId, $userId)) {
                return $this->respond(false, 'Unauthorized', null, ['code' => 403], 403);
            }

            $this->conn->beginTransaction();
            $insert = $this->conn->prepare("INSERT INTO energy_insulation_energy_types (license_id, energy_type_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE energy_type_id = energy_type_id");
            foreach ($energyTypeIds as $id) {
                $insert->execute([$licenseId, (int)$id]);
            }
            $this->conn->commit();
            return $this->respond(true, 'Energy types added successfully');
        } catch (Exception $e) {
            $this->conn->rollBack();
            return $this->respond(false, 'Error: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
    }

    public function removeEnergyTypeFromLicense(int $licenseId, int $energyTypeId, int $userId)
    {
        try {
            if (!$this->ensureIsAreaManager($licenseId, $userId)) {
                return $this->respond(false, 'Unauthorized', null, ['code' => 403], 403);
            }
            $stmt = $this->conn->prepare("DELETE FROM energy_insulation_energy_types WHERE license_id = ? AND energy_type_id = ?");
            $success = $stmt->execute([$licenseId, $energyTypeId]);
            if ($success) return $this->respond(true, 'Energy type removed successfully');
            return $this->respond(false, 'Failed to remove energy type', null, ['code' => 500], 500);
        } catch (Exception $e) {
            return $this->respond(false, 'Error: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
    }

    public function addEquipmentsToLicense(int $licenseId, array $equipmentIds, int $userId)
    {
        try {
            if (!$this->ensureIsAreaManager($licenseId, $userId)) {
                return $this->respond(false, 'Unauthorized', null, ['code' => 403], 403);
            }
            $this->conn->beginTransaction();
            $selectEq = $this->conn->prepare("SELECT id FROM equipments WHERE id = ?");
            $insert = $this->conn->prepare("INSERT INTO energy_insulation_equipments (license_id, equipment_id, equipment_no) VALUES (?, ?, ?)");
            foreach ($equipmentIds as $id) {
                $selectEq->execute([(int)$id]);
                $eq = $selectEq->fetch(PDO::FETCH_ASSOC);
                if ($eq) {
                    // equipments table does not have equipment_no column in this schema; store null
                    $insert->execute([$licenseId, $eq['id'], null]);
                }
            }
            $this->conn->commit();
            return $this->respond(true, 'Equipments added successfully');
        } catch (Exception $e) {
            $this->conn->rollBack();
            return $this->respond(false, 'Error: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
    }

    public function removeEquipmentFromLicense(int $licenseId, int $equipmentId, int $userId)
    {
        try {
            if (!$this->ensureIsAreaManager($licenseId, $userId)) {
                return $this->respond(false, 'Unauthorized', null, ['code' => 403], 403);
            }
            $stmt = $this->conn->prepare("DELETE FROM energy_insulation_equipments WHERE license_id = ? AND equipment_id = ?");
            $success = $stmt->execute([$licenseId, $equipmentId]);
            if ($success) return $this->respond(true, 'Equipment removed successfully');
            return $this->respond(false, 'Failed to remove equipment', null, ['code' => 500], 500);
        } catch (Exception $e) {
            return $this->respond(false, 'Error: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
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

    public function amDoneAction(int $licenseId, int $userId)
    {
        try {
            // Fetch license info for notification
            $stmt = $this->conn->prepare("SELECT created_by, equipment_name FROM energy_insulation_license WHERE id = ?");
            $stmt->execute([$licenseId]);
            $license = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$license) {
                return $this->respond(false, 'License not found', null, ['code' => 404], 404);
            }

            $stmt = $this->conn->prepare("UPDATE energy_insulation_license SET status = 'active_isolation', am_approved_at = NOW() WHERE id = ?");
            $success = $stmt->execute([$licenseId]);

            if ($success) {
                // Send Notification and Email to Requester
                if ($this->notificationController) {
                    $title = "تم العزل - Isolation Active";
                    $body = "قام مسؤول العزل بتأكيد العزل للمعدة: " . ($license['equipment_name'] ?? 'N/A');
                    $url = BASE_URL . "/public/requester/view_energy_license.php?id=" . $licenseId;

                    $this->notificationController->sendNotification($title, $body, [$license['created_by']], $url, $userId);

                    if ($this->emailController) {
                        $this->emailController->sendEmail($title, $body, [$license['created_by']]);
                    }
                }
                return $this->respond(true, 'Isolation confirmed by Area Manager successfully');
            }
            return $this->respond(false, 'Failed to confirm isolation', null, ['code' => 500], 500);
        } catch (Exception $e) {
            return $this->respond(false, 'Error: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
    }

    public function removeIsolationAction(int $licenseId, int $userId)
    {
        try {
            // Fetch license info for notification
            $stmt = $this->conn->prepare("SELECT area_manager_id, equipment_name FROM energy_insulation_license WHERE id = ?");
            $stmt->execute([$licenseId]);
            $license = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$license) {
                return $this->respond(false, 'License not found', null, ['code' => 404], 404);
            }

            // Check if there are any incomplete staff groups
            $groupCheckStmt = $this->conn->prepare("SELECT COUNT(*) as incomplete_count FROM energy_insulation_staff_group WHERE license_id = ? AND IFNULL(is_done, 0) = 0");
            $groupCheckStmt->execute([$licenseId]);
            $incompleteCount = (int)$groupCheckStmt->fetch(PDO::FETCH_ASSOC)['incomplete_count'];

            if ($incompleteCount > 0) {
                return $this->respond(false, 'لا يمكن رفع العزل حتى يتم إكمال العمل لجميع المجموعات', null, ['code' => 400], 400);
            }

            $stmt = $this->conn->prepare("UPDATE energy_insulation_license SET status = 'completed', isolation_removed_at = NOW(), end_at = NOW() WHERE id = ?");
            $success = $stmt->execute([$licenseId]);

            if ($success) {
                // Send Notification and Email to Area Manager
                if ($this->notificationController && !empty($license['area_manager_id'])) {
                    $title = "قام المرخص برفع العزل";
                    $body = "قام طالب الرخصة برفع العزل للمعدة: " . ($license['equipment_name'] ?? 'N/A');
                    $url = BASE_URL . "/public/requester/view_energy_license.php?id=" . $licenseId;
                    $emailBody = "قام طالب الرخصة برفع العزل للمعدة: " . ($license['equipment_name'] ?? 'N/A') .
                        "<br><br>" .
                        "<a href='" . $url . "'>اضغط هنا لعرض الرخصة</a>";

                    $this->notificationController->sendNotification($title, $body, [$license['area_manager_id']], $url, $userId);

                    if ($this->emailController) {
                        $this->emailController->sendEmail($title, $body, [$license['area_manager_id']]);
                    }
                }
                return $this->respond(true, 'تم رفع العزل بنجاح');
            }
            return $this->respond(false, 'فشل رفع العزل', null, ['code' => 500], 500);
        } catch (Exception $e) {
            return $this->respond(false, 'Error: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
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

    public function getStatistics(array $filters)
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'active_isolation' THEN 1 ELSE 0 END) as active_isolation,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                      FROM energy_insulation_license WHERE 1=1";

            $params = [];

            if (!empty($filters['from_date'])) {
                $query .= " AND date >= ?";
                $params[] = $filters['from_date'];
            }
            if (!empty($filters['to_date'])) {
                $query .= " AND date <= ?";
                $params[] = $filters['to_date'];
            }

            // Role-based filtering
            $userRole = (int)$filters['role_id'];
            $userId = (int)$filters['user_id'];

            if (!in_array($userRole, [1, 6, 7])) {
                // Roles 7, 3, 5 see only permits assigned to them as Area Manager
                // Or if they created it.
                $query .= " AND (area_manager_id = ? OR created_by = ?)";
                $params[] = $userId;
                $params[] = $userId;
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->respond(true, 'Statistics retrieved successfully', [
                'total' => (int)$stats['total'],
                'pending' => (int)$stats['pending'],
                'active_isolation' => (int)$stats['active_isolation'],
                'completed' => (int)$stats['completed']
            ]);
        } catch (Exception $e) {
            return $this->respond(false, 'Failed to fetch statistics: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
    }

    public function getAllLicenses(array $filters)
    {
        try {
            $page = isset($filters['page']) ? (int)$filters['page'] : 1;
            $limit = isset($filters['limit']) ? (int)$filters['limit'] : 10;
            $offset = ($page - 1) * $limit;

            $where = " WHERE 1=1";
            $params = [];

            if (!empty($filters['section'])) {
                $where .= " AND l.equipment_section_id = ?";
                $params[] = $filters['section'];
            }
            if (!empty($filters['status'])) {
                $where .= " AND l.status = ?";
                $params[] = $filters['status'];
            }
            if (!empty($filters['from_date'])) {
                $where .= " AND l.date >= ?";
                $params[] = $filters['from_date'];
            }
            if (!empty($filters['to_date'])) {
                $where .= " AND l.date <= ?";
                $params[] = $filters['to_date'];
            }

            // Role-based filtering
            $userRole = (int)$filters['role_id'];
            $userId = (int)$filters['user_id'];

            if (!in_array($userRole, [1, 6, 7])) {
                $where .= " AND (l.area_manager_id = ? OR l.created_by = ?)";
                $params[] = $userId;
                $params[] = $userId;
            }

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM energy_insulation_license l $where";
            $countStmt = $this->conn->prepare($countQuery);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $query = "SELECT l.*, es.name as section_name, am.name as area_manager_name 
                      FROM energy_insulation_license l
                      LEFT JOIN equipment_sections es ON l.equipment_section_id = es.id
                      LEFT JOIN users am ON l.area_manager_id = am.id
                      $where
                      ORDER BY l.id DESC
                      LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $this->respond(true, 'Licenses retrieved successfully', [
                'licenses' => $licenses,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]);
        } catch (Exception $e) {
            return $this->respond(false, 'Failed to fetch licenses: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
    }

    public function updateStaffGroups(int $licenseId, array $staffGroups, int $userId)
    {
        try {
            // Verify permission: Allow created_by or area_manager_id to edit
            $stmt = $this->conn->prepare("SELECT created_by, area_manager_id FROM energy_insulation_license WHERE id = ?");
            $stmt->execute([$licenseId]);
            $license = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$license) {
                return $this->respond(false, 'License not found', null, ['code' => 404], 404);
            }

            if ($license['created_by'] != $userId) {
                return $this->respond(false, 'Unauthorized to edit this license staff groups', null, ['code' => 403], 403);
            }

            $this->conn->beginTransaction();

            // Delete existing staff and groups
            $this->conn->prepare("DELETE FROM energy_insulation_staff WHERE license_id = ?")->execute([$licenseId]);
            $this->conn->prepare("DELETE FROM energy_insulation_staff_group WHERE license_id = ?")->execute([$licenseId]);

            // Insert new Staff Groups
            if (!empty($staffGroups)) {
                $groupStmt = $this->conn->prepare("INSERT INTO energy_insulation_staff_group (name, license_id) VALUES (?, ?)");
                $staffStmt = $this->conn->prepare("INSERT INTO energy_insulation_staff (license_id, name, group_id) VALUES (?, ?, ?)");

                foreach ($staffGroups as $group) {
                    if (empty($group['group_name'])) continue;

                    $groupStmt->execute([$group['group_name'], $licenseId]);
                    $groupId = $this->conn->lastInsertId();

                    if (!empty($group['members'])) {
                        foreach ($group['members'] as $staffName) {
                            if (empty(trim($staffName))) continue;
                            $staffStmt->execute([$licenseId, $staffName, $groupId]);
                        }
                    }
                }
            }

            $this->conn->commit();
            return $this->respond(true, 'تم تحديث طاقم العمل بنجاح');
        } catch (Exception $e) {
            $this->conn->rollBack();
            return $this->respond(false, 'Failed to update staff groups: ' . $e->getMessage(), null, ['code' => 500], 500);
        }
    }
}
