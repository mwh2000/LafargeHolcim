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
            $stmt = $this->db->prepare("INSERT INTO hot_work_permit (
                permit_no, issuing_date_time, company_name, location, supervisor, 
                equipment_used, task_start_datetime, finishing_time, assigned_to, 
                work_description, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $data['permit_no'],
                date('Y-m-d H:i:s'),
                $data['company_name'],
                $data['location'],
                $data['supervisor'],
                $data['equipment_used'],
                $data['task_start_datetime'],
                $data['finishing_time'],
                $data['assigned_to'],
                $data['work_description'] ?? '',
                $data['created_by'],
                date('Y-m-d H:i:s')
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
                $title = 'رخصة عمل ساخن جديدة';
                $body = 'تم إسناد رخصة عمل ساخن جديدة إليك برقم ' . $data['permit_no'];
                $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                $notificationController->sendNotification($title, $body, [$data['assigned_to']], $url, $data['created_by']);
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

    public function getPermit($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT h.*, u.name as assigned_to_name, c.name as creator_name
                                        FROM hot_work_permit h 
                                        LEFT JOIN users u ON h.assigned_to = u.id
                                        LEFT JOIN users c ON h.created_by = c.id
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
}
