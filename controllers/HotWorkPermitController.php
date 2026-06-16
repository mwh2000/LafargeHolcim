<?php
require_once __DIR__ . '/notificationsController.php';


class HotWorkPermitController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function createPermit($data)
    {
        try {
            $this->db->beginTransaction();

            // 1. Insert main permit
            // Support critical workflow columns if provided
            $stmt = $this->db->prepare("INSERT INTO hot_work_permit (
                permit_no, issuing_date_time, wo, company_name, location, supervisor, 
                equipment_used, maintenance_type, task_start_datetime, finishing_time, creation_date, assigned_to, 
                work_description, created_by, created_at, is_critical, critical_manager_id, critical_supervisor_id, critical_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $isCritical = !empty($data['is_critical']) ? 1 : 0;
            $criticalManager = $data['critical_manager_id'] ?? null;
            $criticalSupervisor = $data['critical_supervisor_id'] ?? null;
            $criticalStatus = $isCritical ? ($data['critical_status'] ?? 'pending_manager') : null;

            $stmt->execute([
                $data['permit_no'],
                date('Y-m-d H:i:s'),
                $data['wo'],
                $data['company_name'],
                $data['location'],
                $data['supervisor'] ?? null,
                $data['equipment_used'] ?? null,
                $data['maintenance_type'] ?? null,
                $data['task_start_datetime'] ?? null,
                $data['finishing_time'] ?? null,
                $data['creation_date'] ?? date('Y-m-d H:i:s'),
                $data['assigned_to'] ?? null,
                $data['work_description'] ?? '',
                $data['created_by'],
                date('Y-m-d H:i:s'),
                $isCritical,
                $criticalManager,
                $criticalSupervisor,
                $criticalStatus
            ]);

            $permitId = $this->db->lastInsertId();

            // 2. Insert Additional Permits
            if (!empty($data['additional_permits'])) {
                $stmtAdd = $this->db->prepare("INSERT INTO additional_hot_permits (
                    hot_work_permit_id, permit_name, permit_number
                ) VALUES (?, ?, ?)");
                foreach ($data['additional_permits'] as $permit) {
                    if (!empty($permit['permit_name'])) {
                        $stmtAdd->execute([
                            $permitId,
                            $permit['permit_name'],
                            $permit['permit_number'] ?? ''
                        ]);
                    }
                }
            }

            // 3. Insert Control Measures
            if (!empty($data['control_measures'])) {
                $stmtCtrl = $this->db->prepare("INSERT INTO hot_work_control_measures (
                    hot_work_permit_id, measure_text, status
                ) VALUES (?, ?, ?)");
                foreach ($data['control_measures'] as $measure) {
                    $stmtCtrl->execute([
                        $permitId,
                        $measure['text'],
                        $measure['status']
                    ]);
                }
            }

            // 4. Insert Performers Check
            if (!empty($data['performers_check'])) {
                $stmtPerf = $this->db->prepare("INSERT INTO hot_work_performers_check (
                    hot_work_permit_id, question_text, answer
                ) VALUES (?, ?, ?)");
                foreach ($data['performers_check'] as $check) {
                    $stmtPerf->execute([
                        $permitId,
                        $check['text'],
                        $check['answer']
                    ]);
                }
            }

            // 5. Insert Approvals
            if (!empty($data['approvals'])) {
                $stmtAppr = $this->db->prepare("INSERT INTO hot_permit_approvals (
                    hot_work_permit_id, role_name, approval_status
                ) VALUES (?, ?, ?)");
                foreach ($data['approvals'] as $approval) {
                    // Storing name and status in approval_status as suggested in plan
                    $statusText = ($approval['name'] ?? 'N/A') . " - " . ($approval['approved'] ? 'Approved' : 'Pending');
                    $stmtAppr->execute([
                        $permitId,
                        $approval['role'],
                        $statusText
                    ]);
                }
            }

            $this->db->commit();

            try {
                $notificationController = new NotificationController($this->db);
                if ($isCritical && $criticalManager) {
                    // For critical permits, notify the assigned manager
                    $title = 'تم انشاء رخصة الاعمال الساخنه الحرجة';
                    $body = 'بأنتظار موافقة قسم السلامة لأكمال العمل';
                    $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                    $notificationController->sendNotification($title, $body, [$criticalManager], $url, $data['created_by']);
                } elseif (!$isCritical && !empty($data['assigned_to'])) {
                    // For normal permits, notify the assigned user
                    $title = 'رخصة عمل ساخن جديدة';
                    $body = 'تم إسناد رخصة عمل ساخن جديدة إليك برقم ' . $data['permit_no'];
                    $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                    $notificationController->sendNotification($title, $body, [$data['assigned_to']], $url, $data['created_by']);
                }
            } catch (Exception $e) {
                // Ignore notification error
            }

            return ['success' => true, 'message' => 'Hot Work Permit created successfully!', 'id' => $permitId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Error creating permit: ' . $e->getMessage()];
        }
    }

    public function getAssignees()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM users WHERE role_id IN (3, 5)");
            $stmt->execute();
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching assignees: ' . $e->getMessage()];
        }
    }

    public function getManagers()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM users WHERE role_id IN (1, 4)");
            $stmt->execute();
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching managers: ' . $e->getMessage()];
        }
    }

    public function getSupervisors()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM users WHERE role_id = 6");
            $stmt->execute();
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching supervisors: ' . $e->getMessage()];
        }
    }

    public function assignSupervisor($permitId, $managerId, $supervisorId)
    {
        try {
            // Set supervisor and change status to pending_supervisor
            $stmt = $this->db->prepare("UPDATE hot_work_permit SET critical_supervisor_id = ?, critical_status = 'pending_supervisor' WHERE id = ? AND critical_manager_id = ?");
            $stmt->execute([$supervisorId, $permitId, $managerId]);

            // Notify supervisor
            try {
                $notificationController = new NotificationController($this->db);
                $title = 'تم إسناد رخصة عمل حرجة للمشرف';
                $body = 'تم إسناد رخصة عمل حرجة برقم إلى المشرف. الرجاء المراجعة.';
                $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                $notificationController->sendNotification($title, $body, [$supervisorId], $url, $managerId);
            } catch (Exception $e) {
                // ignore notification errors
            }

            return ['success' => true, 'message' => 'Supervisor assigned successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to assign supervisor: ' . $e->getMessage()];
        }
    }

    public function markDoneBySupervisor($permitId, $supervisorId)
    {
        try {
            // Verify supervisor
            $stmtCheck = $this->db->prepare("SELECT created_by FROM hot_work_permit WHERE id = ? AND critical_supervisor_id = ?");
            $stmtCheck->execute([$permitId, $supervisorId]);
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['success' => false, 'message' => 'Not authorized or permit not found'];
            }

            // Update status to pending_creator
            $stmt = $this->db->prepare("UPDATE hot_work_permit SET critical_status = 'pending_creator' WHERE id = ?");
            $stmt->execute([$permitId]);

            // Notify creator
            try {
                $creatorId = $row['created_by'];
                $notificationController = new NotificationController($this->db);
                $title = 'المشرف أكمل مهمة الرخصة الحرجة';
                $body = 'يمكنك الآن إكمال بقية خطوات الرخصة.';
                $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                $notificationController->sendNotification($title, $body, [$creatorId], $url, $supervisorId);
            } catch (Exception $e) {
                // ignore
            }

            return ['success' => true, 'message' => 'تمت الموافقة لانشاء رخصة العمل'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to mark done: ' . $e->getMessage()];
        }
    }

    public function completePermit($permitId, $data)
    {
        try {
            $this->db->beginTransaction();

            // Handle empty datetime fields (convert empty string to NULL)
            $taskStartDatetime = !empty($data['task_start_datetime']) ? $data['task_start_datetime'] : null;
            $finishingTime = !empty($data['finishing_time']) ? $data['finishing_time'] : null;

            // Update main permit fields and set status to completed
            $stmt = $this->db->prepare("UPDATE hot_work_permit SET 
                location = ?, supervisor = ?, equipment_used = ?, task_start_datetime = ?, finishing_time = ?, work_description = ?, assigned_to = ?, critical_status = 'completed' WHERE id = ?");
            $stmt->execute([
                $data['location'],
                $data['supervisor'],
                $data['equipment_used'],
                $taskStartDatetime,
                $finishingTime,
                $data['work_description'] ?? '',
                $data['assigned_to'] ?: null,
                $permitId
            ]);

            // Clear and insert additional_permits
            $stmtDelAdd = $this->db->prepare("DELETE FROM additional_hot_permits WHERE hot_work_permit_id = ?");
            $stmtDelAdd->execute([$permitId]);
            if (!empty($data['additional_permits'])) {
                $stmtAdd = $this->db->prepare("INSERT INTO additional_hot_permits (
                    hot_work_permit_id, permit_name, permit_number
                ) VALUES (?, ?, ?)");
                foreach ($data['additional_permits'] as $permit) {
                    if (!empty($permit['permit_name'])) {
                        $stmtAdd->execute([$permitId, $permit['permit_name'], $permit['permit_number'] ?? '']);
                    }
                }
            }

            // Clear and insert control measures
            $stmtDelCtrl = $this->db->prepare("DELETE FROM hot_work_control_measures WHERE hot_work_permit_id = ?");
            $stmtDelCtrl->execute([$permitId]);
            if (!empty($data['control_measures'])) {
                $stmtCtrl = $this->db->prepare("INSERT INTO hot_work_control_measures (
                    hot_work_permit_id, measure_text, status
                ) VALUES (?, ?, ?)");
                foreach ($data['control_measures'] as $measure) {
                    $stmtCtrl->execute([$permitId, $measure['text'], $measure['status']]);
                }
            }

            // Clear and insert performers check
            $stmtDelPerf = $this->db->prepare("DELETE FROM hot_work_performers_check WHERE hot_work_permit_id = ?");
            $stmtDelPerf->execute([$permitId]);
            if (!empty($data['performers_check'])) {
                $stmtPerf = $this->db->prepare("INSERT INTO hot_work_performers_check (
                    hot_work_permit_id, question_text, answer
                ) VALUES (?, ?, ?)");
                foreach ($data['performers_check'] as $check) {
                    $stmtPerf->execute([$permitId, $check['text'], $check['answer']]);
                }
            }

            // Clear and insert approvals
            $stmtDelAppr = $this->db->prepare("DELETE FROM hot_permit_approvals WHERE hot_work_permit_id = ?");
            $stmtDelAppr->execute([$permitId]);
            if (!empty($data['approvals'])) {
                $stmtAppr = $this->db->prepare("INSERT INTO hot_permit_approvals (
                    hot_work_permit_id, role_name, approval_status
                ) VALUES (?, ?, ?)");
                foreach ($data['approvals'] as $approval) {
                    $statusText = ($approval['name'] ?? 'N/A') . " - " . ($approval['approved'] ? 'Approved' : 'Pending');
                    $stmtAppr->execute([$permitId, $approval['role'], $statusText]);
                }
            }

            $this->db->commit();

            // Notify final assignee if any
            try {
                $stmtFinal = $this->db->prepare("SELECT assigned_to, created_by FROM hot_work_permit WHERE id = ?");
                $stmtFinal->execute([$permitId]);
                $row = $stmtFinal->fetch(PDO::FETCH_ASSOC);
                if ($row && $row['assigned_to']) {
                    $notificationController = new NotificationController($this->db);
                    $title = 'تم إكمال رخصة العمل الحرجة';
                    $body = 'تمت إكمال الرخصة وتم إسنادها.';
                    $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                    $notificationController->sendNotification($title, $body, [$row['assigned_to']], $url, $row['created_by']);
                }
            } catch (Exception $e) {
                // ignore
            }

            return ['success' => true, 'message' => 'Permit completed successfully', 'id' => $permitId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to complete permit: ' . $e->getMessage()];
        }
    }

    public function getPermit($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT h.*, u.name as assigned_to_name, c.name as creator_name,
                                                cm.name as critical_manager_name, cs.name as critical_supervisor_name
                                        FROM hot_work_permit h 
                                        LEFT JOIN users u ON h.assigned_to = u.id
                                        LEFT JOIN users c ON h.created_by = c.id
                                        LEFT JOIN users cm ON h.critical_manager_id = cm.id
                                        LEFT JOIN users cs ON h.critical_supervisor_id = cs.id
                                        WHERE h.id = ?");
            $stmt->execute([$id]);
            $permit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$permit) {
                return ['success' => false, 'message' => 'Permit not found'];
            }

            $stmtAdd = $this->db->prepare("SELECT * FROM additional_hot_permits WHERE hot_work_permit_id = ?");
            $stmtAdd->execute([$id]);
            $permit['additional_permits'] = $stmtAdd->fetchAll(PDO::FETCH_ASSOC);

            $stmtCtrl = $this->db->prepare("SELECT * FROM hot_work_control_measures WHERE hot_work_permit_id = ?");
            $stmtCtrl->execute([$id]);
            $permit['control_measures'] = $stmtCtrl->fetchAll(PDO::FETCH_ASSOC);

            $stmtPerf = $this->db->prepare("SELECT * FROM hot_work_performers_check WHERE hot_work_permit_id = ?");
            $stmtPerf->execute([$id]);
            $permit['performers_check'] = $stmtPerf->fetchAll(PDO::FETCH_ASSOC);

            $stmtAppr = $this->db->prepare("SELECT * FROM hot_permit_approvals WHERE hot_work_permit_id = ?");
            $stmtAppr->execute([$id]);
            $permit['approvals'] = $stmtAppr->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $permit];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching permit: ' . $e->getMessage()];
        }
    }

    public function getStatistics($filters)
    {
        try {
            $query = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN finishing_time IS NOT NULL AND finishing_time < NOW() THEN 1 ELSE 0 END) as not_active
            FROM hot_work_permit WHERE 1=1";
            $params = [];

            if (!empty($filters['from_date'])) {
                $query .= " AND DATE(issuing_date_time) >= ?";
                $params[] = $filters['from_date'];
            }
            if (!empty($filters['to_date'])) {
                $query .= " AND DATE(issuing_date_time) <= ?";
                $params[] = $filters['to_date'];
            }

            // Role-based filtering if needed (e.g. only show user's assigned permits unless they are admin/manager)
            $userRole = (int)$filters['role_id'];
            $userId = (int)$filters['user_id'];

            if (!in_array($userRole, [1, 6, 7])) {
                $query .= " AND (assigned_to = ? OR created_by = ?)";
                $params[] = $userId;
                $params[] = $userId;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => [
                'total'      => (int)$stats['total'],
                'not_active' => (int)$stats['not_active']
            ]];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch statistics: ' . $e->getMessage()];
        }
    }

    public function getAll($filters)
    {
        try {
            $page = isset($filters['page']) ? (int)$filters['page'] : 1;
            $limit = isset($filters['limit']) ? (int)$filters['limit'] : 10;
            $offset = ($page - 1) * $limit;

            $where = " WHERE 1=1";
            $params = [];

            if (!empty($filters['from_date'])) {
                $where .= " AND DATE(h.issuing_date_time) >= ?";
                $params[] = $filters['from_date'];
            }
            if (!empty($filters['to_date'])) {
                $where .= " AND DATE(h.issuing_date_time) <= ?";
                $params[] = $filters['to_date'];
            }

            // Role-based filtering
            $userRole = (int)$filters['role_id'];
            $userId = (int)$filters['user_id'];

            if (!in_array($userRole, [1, 6, 7])) {
                $where .= " AND (h.assigned_to = ? OR h.created_by = ?)";
                $params[] = $userId;
                $params[] = $userId;
            }

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM hot_work_permit h $where";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $query = "SELECT h.*, u.name as assigned_to_name, c.name as creator_name
                      FROM hot_work_permit h
                      LEFT JOIN users u ON h.assigned_to = u.id
                      LEFT JOIN users c ON h.created_by = c.id
                      $where
                      ORDER BY h.id DESC
                      LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $permits = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => [
                'permits' => $permits,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ]];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch permits: ' . $e->getMessage()];
        }
    }
}
