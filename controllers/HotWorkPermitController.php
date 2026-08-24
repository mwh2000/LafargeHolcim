<?php
require_once __DIR__ . '/notificationsController.php';
require_once __DIR__ . '/emailController.php';
require_once __DIR__ . '/LicensePdfController.php';


class HotWorkPermitController
{
    private $db;

    // TODO: fill in once the recipient list is decided — notified in addition to
    // the assignee whenever a normal (non-critical) permit is approved by Safety.
    private $safetyApprovalExtraNotifyUserIds = [189, 191, 149];

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Builds a fresh PDF snapshot of the permit as an email attachment. PDF
     * generation failures must never block the underlying workflow action or
     * the notification email itself, so any error just yields no attachment.
     */
    private function buildPermitPdfAttachment($permitId)
    {
        try {
            $pdf = LicensePdfController::generateHotWorkPermitPdf($this->db, (int)$permitId);
            return [['content' => $pdf, 'filename' => "hot-work-permit-{$permitId}.pdf"]];
        } catch (\Throwable $e) {
            // Catches fatal errors too (e.g. a PDF dependency missing on this
            // environment) so a broken attachment never takes down the request.
            error_log('PDF attachment generation failed for hot work permit #' . $permitId . ': ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Appends a "view permit" link to an email body, since these emails now
     * also carry a PDF snapshot and the recipient may want the live page too.
     */
    private function withPermitLink($body, $permitId)
    {
        $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
        return $body . "<br><br><a href='{$url}'>اضغط هنا لعرض الرخصة</a>";
    }

    public function createPermit($data)
    {
        try {
            $this->db->beginTransaction();

            // 1. Insert main permit
            // Support critical workflow columns if provided
            $stmt = $this->db->prepare("INSERT INTO hot_work_permit (
                permit_no, issuing_date_time, wo, company_name, location, supervisor,
                maintenance_type, task_start_datetime, finishing_time, assigned_to,
                safety_reviewer_id, safety_status,
                work_description, created_by, created_at, is_critical, critical_manager_id, critical_supervisor_id, critical_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $isCritical = !empty($data['is_critical']) ? 1 : 0;
            $criticalManager = $data['critical_manager_id'] ?? null;
            $criticalSupervisor = $data['critical_supervisor_id'] ?? null;
            $criticalStatus = $isCritical ? ($data['critical_status'] ?? 'pending_manager') : null;
            $safetyReviewerId = $data['safety_reviewer_id'] ?? null;
            $safetyStatus = 'pending';

            $stmt->execute([
                $data['permit_no'],
                date('Y-m-d H:i:s'),
                $data['wo'],
                $data['company_name'],
                $data['location'],
                $data['supervisor'] ?? null,
                $data['maintenance_type'] ?? null,
                $data['task_start_datetime'] ?? null,
                $data['finishing_time'] ?? null,
                $data['assigned_to'] ?? null,
                $safetyReviewerId,
                $safetyStatus,
                $data['work_description'] ?? '',
                $data['created_by'],
                date('Y-m-d H:i:s'),
                $isCritical,
                $criticalManager,
                $criticalSupervisor,
                $criticalStatus
            ]);

            $permitId = $this->db->lastInsertId();

            // 2. Insert Equipment Used
            if (!empty($data['equipment_used'])) {
                $stmtEquip = $this->db->prepare("INSERT INTO hot_work_equipment_used (
                    hot_work_permit_id, equipment_name
                ) VALUES (?, ?)");
                foreach ((array)$data['equipment_used'] as $equipmentName) {
                    if ($equipmentName !== '' && $equipmentName !== null) {
                        $stmtEquip->execute([$permitId, $equipmentName]);
                    }
                }
            }

            // 3. Insert Additional Permits
            if (!empty($data['additional_permits'])) {
                $stmtAdd = $this->db->prepare("INSERT INTO additional_hot_permits (
                    hot_work_permit_id, permit_name, permit_number, image
                ) VALUES (?, ?, ?, ?)");
                foreach ($data['additional_permits'] as $permit) {
                    if (!empty($permit['permit_name'])) {
                        $stmtAdd->execute([
                            $permitId,
                            $permit['permit_name'],
                            $permit['permit_number'] ?? '',
                            $permit['image'] ?? null
                        ]);
                    }
                }
            }

            // 4. Insert Control Measures
            if (!empty($data['control_measures'])) {
                $stmtControl = $this->db->prepare("INSERT INTO hot_work_control_measures (
                    hot_work_permit_id, measure_text, status, image
                ) VALUES (?, ?, ?, ?)");
                foreach ($data['control_measures'] as $measure) {
                    $stmtControl->execute([
                        $permitId,
                        $measure['text'],
                        $measure['answer'],
                        $measure['image'] ?? null
                    ]);
                }
            }

            // 5. Insert Performers Check
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

            // 6. Insert Approvals
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
                $emailController = new EmailController($this->db);
                if (!empty($safetyReviewerId)) {
                    $creatorId = $data['created_by'] ?? null;
                    $creatorName = 'N/A';
                    $creatorDepartment = 'N/A';

                    if (!empty($creatorId)) {
                        $creatorStmt = $this->db->prepare("SELECT name, department FROM users WHERE id = ?");
                        $creatorStmt->execute([$creatorId]);
                        $creatorUser = $creatorStmt->fetch(PDO::FETCH_ASSOC);

                        if ($creatorUser) {
                            $creatorName = $creatorUser['name'] ?? 'N/A';
                            $creatorDepartment = $creatorUser['department'] ?? 'N/A';
                        }
                    }

                    $title = 'بأنتظار موافقة قسم السلامة';
                    $body = "{$creatorName} | {$creatorDepartment}";
                    $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                    $notificationController->sendNotification($title, $body, [$safetyReviewerId], $url, $creatorId);
                    $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), [$safetyReviewerId], null, false, $this->buildPermitPdfAttachment($permitId));
                }
            } catch (Exception $e) {
                // Ignore notification/email errors
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
            $stmt = $this->db->prepare("SELECT id, name FROM users WHERE role_id IN (3, 5, 7)");
            $stmt->execute();
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching assignees: ' . $e->getMessage()];
        }
    }

    public function getManagers()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM users WHERE role_id = 6");
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

    public function getSafetyReviewers()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM users WHERE role_id IN (1, 4)");
            $stmt->execute();
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching safety reviewers: ' . $e->getMessage()];
        }
    }

    public function getShiftLeaders()
    {
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM users WHERE role_id = 7");
            $stmt->execute();
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error fetching shift leaders: ' . $e->getMessage()];
        }
    }

    public function approveBySafety($permitId, $safetyUserId, $criticalManagerId = null)
    {
        try {
            $stmt = $this->db->prepare("SELECT created_by, assigned_to, is_critical, critical_manager_id, safety_reviewer_id, safety_status FROM hot_work_permit WHERE id = ?");
            $stmt->execute([$permitId]);
            $permit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$permit) {
                return ['success' => false, 'message' => 'Permit not found'];
            }
            if ((int)$permit['safety_reviewer_id'] !== (int)$safetyUserId) {
                return ['success' => false, 'message' => 'Not authorized to review this permit'];
            }
            if ($permit['safety_status'] !== 'pending') {
                return ['success' => false, 'message' => 'This permit has already been reviewed'];
            }

            $selectedCriticalManagerId = null;
            if ((int)$permit['is_critical'] === 1) {
                $selectedCriticalManagerId = !empty($criticalManagerId) ? (int)$criticalManagerId : (!empty($permit['critical_manager_id']) ? (int)$permit['critical_manager_id'] : null);
                if (empty($selectedCriticalManagerId)) {
                    return ['success' => false, 'message' => 'يجب اختيار مدير المصنع قبل الموافقة'];
                }
            }

            $nextCriticalStatus = ((int)$permit['is_critical'] === 1) ? 'pending_manager' : 'completed';
            $stmt = $this->db->prepare("UPDATE hot_work_permit SET safety_status = 'approved', safety_reviewed_at = ?, critical_manager_id = ?, critical_status = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), $selectedCriticalManagerId, $nextCriticalStatus, $permitId]);

            try {
                $notificationController = new NotificationController($this->db);
                $emailController = new EmailController($this->db);
                $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;

                if ((int)$permit['is_critical'] === 1 && !empty($selectedCriticalManagerId)) {
                    $title = 'بأنتظار موافقة مدير المصنع';
                    $body = 'تمت موافقة قسم السلامة على الرخصة ويحتاج موافقة مدير المصنع';
                    $notificationController->sendNotification($title, $body, [$selectedCriticalManagerId], $url, $safetyUserId);
                    $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), [$selectedCriticalManagerId], null, false, $this->buildPermitPdfAttachment($permitId));
                } elseif (!empty($permit['assigned_to'])) {
                    $title = 'رخصة عمل ساخن جديدة';
                    $body = 'وافق السيفتي على رخصة العمل الساخن وتم إسنادها إليك';
                    $notificationController->sendNotification($title, $body, [$permit['assigned_to']], $url, $safetyUserId);
                    $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), [$permit['assigned_to']], null, false, $this->buildPermitPdfAttachment($permitId));

                    if (!empty($permit['created_by']) && (int)$permit['created_by'] !== (int)$permit['assigned_to']) {
                        $creatorTitle = 'تمت الموافقة على رخصة عمل ساخن';
                        $creatorBody = 'وافق قسم السلامة على رخصة العمل الساخن';
                        $notificationController->sendNotification($creatorTitle, $creatorBody, [$permit['created_by']], $url, $safetyUserId);
                        $emailController->sendEmail($creatorTitle, $this->withPermitLink($creatorBody, $permitId), [$permit['created_by']], null, false, $this->buildPermitPdfAttachment($permitId));
                    }
                }

                if (!empty($this->safetyApprovalExtraNotifyUserIds) && (int)$permit['is_critical'] !== 1) {
                    $title = 'تمت الموافقة على رخصة عمل ساخن';
                    $body = 'وافق السيفتي على رخصة عمل ساخن';
                    $notificationController->sendNotification($title, $body, $this->safetyApprovalExtraNotifyUserIds, $url, $safetyUserId);
                    $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), $this->safetyApprovalExtraNotifyUserIds, null, false, $this->buildPermitPdfAttachment($permitId));
                }
            } catch (Exception $e) {
                // Ignore notification/email errors
            }

            return ['success' => true, 'message' => 'تمت الموافقة على الرخصة بنجاح'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to approve permit: ' . $e->getMessage()];
        }
    }

    public function approveByManager($permitId, $managerUserId)
    {
        try {
            $stmt = $this->db->prepare("SELECT created_by, assigned_to, critical_manager_id, is_critical, safety_status, critical_status FROM hot_work_permit WHERE id = ?");
            $stmt->execute([$permitId]);
            $permit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$permit || (int)$permit['is_critical'] !== 1) {
                return ['success' => false, 'message' => 'Permit not found'];
            }
            if ((int)$permit['critical_manager_id'] !== (int)$managerUserId) {
                return ['success' => false, 'message' => 'Not authorized to approve this permit'];
            }
            if ($permit['safety_status'] !== 'approved') {
                return ['success' => false, 'message' => 'The permit is not ready for plant manager approval'];
            }
            if ($permit['critical_status'] === 'completed') {
                return ['success' => false, 'message' => 'This permit has already been approved'];
            }

            $stmt = $this->db->prepare("UPDATE hot_work_permit SET critical_status = 'completed' WHERE id = ?");
            $stmt->execute([$permitId]);

            try {
                $notificationController = new NotificationController($this->db);
                $emailController = new EmailController($this->db);
                $title = 'تمت الموافقة على الرخصة الحرجة';
                $body = 'تمت الموافقة من مدير المصنع على الرخصة الحرجة';
                $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                $recipients = array_filter(array_unique([(int)($permit['created_by'] ?? 0), (int)($permit['assigned_to'] ?? 0)]));
                if (!empty($recipients)) {
                    $notificationController->sendNotification($title, $body, array_values($recipients), $url, $managerUserId);
                    $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), array_values($recipients), null, false, $this->buildPermitPdfAttachment($permitId));
                }
            } catch (Exception $e) {
                // Ignore notification/email errors
            }

            return ['success' => true, 'message' => 'تمت الموافقة على الرخصة من مدير المصنع بنجاح'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to approve permit by manager: ' . $e->getMessage()];
        }
    }

    public function rejectBySafety($permitId, $safetyUserId, $comment)
    {
        try {
            if (empty(trim($comment ?? ''))) {
                return ['success' => false, 'message' => 'سبب الرفض مطلوب'];
            }

            $stmt = $this->db->prepare("SELECT created_by, assigned_to, safety_reviewer_id, safety_status FROM hot_work_permit WHERE id = ?");
            $stmt->execute([$permitId]);
            $permit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$permit) {
                return ['success' => false, 'message' => 'Permit not found'];
            }
            if ((int)$permit['safety_reviewer_id'] !== (int)$safetyUserId) {
                return ['success' => false, 'message' => 'Not authorized to review this permit'];
            }
            if ($permit['safety_status'] !== 'pending') {
                return ['success' => false, 'message' => 'This permit has already been reviewed'];
            }

            $stmt = $this->db->prepare("UPDATE hot_work_permit SET safety_status = 'rejected', safety_comment = ?, safety_reviewed_at = ? WHERE id = ?");
            $stmt->execute([$comment, date('Y-m-d H:i:s'), $permitId]);

            try {
                $notificationController = new NotificationController($this->db);
                $emailController = new EmailController($this->db);
                $title = 'قام قسم السلامة بعدم قبول الرخصة يرجى المراجعة';
                $body = 'قام قسم السلامة برفض الرخصة. السبب: ' . $comment;
                $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;

                $recipients = array_filter(array_unique([$permit['created_by'], $permit['assigned_to']]));
                if (!empty($recipients)) {
                    $notificationController->sendNotification($title, $body, array_values($recipients), $url, $safetyUserId);
                    $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), array_values($recipients), null, false, $this->buildPermitPdfAttachment($permitId));
                }
            } catch (Exception $e) {
                // Ignore notification/email errors
            }

            return ['success' => true, 'message' => 'تم رفض الرخصة'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to reject permit: ' . $e->getMessage()];
        }
    }

    public function resubmitPermit($permitId, $data, $creatorId)
    {
        try {
            $stmt = $this->db->prepare("SELECT created_by, safety_status FROM hot_work_permit WHERE id = ?");
            $stmt->execute([$permitId]);
            $permit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$permit) {
                return ['success' => false, 'message' => 'Permit not found'];
            }
            if ((int)$permit['created_by'] !== (int)$creatorId) {
                return ['success' => false, 'message' => 'Not authorized to edit this permit'];
            }
            if ($permit['safety_status'] !== 'rejected') {
                return ['success' => false, 'message' => 'يمكن تعديل وإعادة إرسال الرخصة فقط بعد رفضها'];
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE hot_work_permit SET
                wo = ?, company_name = ?, location = ?, supervisor = ?,
                maintenance_type = ?, task_start_datetime = ?, finishing_time = ?,
                assigned_to = ?, safety_reviewer_id = ?, work_description = ?,
                safety_status = 'pending', safety_comment = NULL, safety_reviewed_at = NULL
                WHERE id = ?");
            $stmt->execute([
                $data['wo'],
                $data['company_name'],
                $data['location'],
                $data['supervisor'] ?? null,
                $data['maintenance_type'] ?? null,
                $data['task_start_datetime'] ?? null,
                $data['finishing_time'] ?? null,
                $data['assigned_to'] ?? null,
                $data['safety_reviewer_id'] ?? null,
                $data['work_description'] ?? '',
                $permitId
            ]);

            $stmtDelEquip = $this->db->prepare("DELETE FROM hot_work_equipment_used WHERE hot_work_permit_id = ?");
            $stmtDelEquip->execute([$permitId]);
            if (!empty($data['equipment_used'])) {
                $stmtEquip = $this->db->prepare("INSERT INTO hot_work_equipment_used (
                    hot_work_permit_id, equipment_name
                ) VALUES (?, ?)");
                foreach ((array)$data['equipment_used'] as $equipmentName) {
                    if ($equipmentName !== '' && $equipmentName !== null) {
                        $stmtEquip->execute([$permitId, $equipmentName]);
                    }
                }
            }

            $stmtDelAdd = $this->db->prepare("DELETE FROM additional_hot_permits WHERE hot_work_permit_id = ?");
            $stmtDelAdd->execute([$permitId]);
            if (!empty($data['additional_permits'])) {
                $stmtAdd = $this->db->prepare("INSERT INTO additional_hot_permits (
                    hot_work_permit_id, permit_name, permit_number, image
                ) VALUES (?, ?, ?, ?)");
                foreach ($data['additional_permits'] as $permitRow) {
                    if (!empty($permitRow['permit_name'])) {
                        $stmtAdd->execute([$permitId, $permitRow['permit_name'], $permitRow['permit_number'] ?? '', $permitRow['image'] ?? null]);
                    }
                }
            }

            $stmtDelControl = $this->db->prepare("DELETE FROM hot_work_control_measures WHERE hot_work_permit_id = ?");
            $stmtDelControl->execute([$permitId]);
            if (!empty($data['control_measures'])) {
                $stmtControl = $this->db->prepare("INSERT INTO hot_work_control_measures (
                    hot_work_permit_id, measure_text, status, image
                ) VALUES (?, ?, ?, ?)");
                foreach ($data['control_measures'] as $measure) {
                    $stmtControl->execute([$permitId, $measure['text'], $measure['answer'], $measure['image'] ?? null]);
                }
            }

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

            try {
                if (!empty($data['safety_reviewer_id'])) {
                    $notificationController = new NotificationController($this->db);
                    $emailController = new EmailController($this->db);
                    $title = 'قام مقدم الطلب بتعديل رخصة العمل الساخن بعد رفضها وإعادة إرسالها، وهي بانتظار مراجعتك';
                    $body = '';
                    $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                    $notificationController->sendNotification($title, $body, [$data['safety_reviewer_id']], $url, $creatorId);
                    $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), [$data['safety_reviewer_id']], null, false, $this->buildPermitPdfAttachment($permitId));
                }
            } catch (Exception $e) {
                // Ignore notification/email errors
            }

            return ['success' => true, 'message' => 'تم تعديل الرخصة وإعادة إرسالها بنجاح', 'id' => $permitId];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to resubmit permit: ' . $e->getMessage()];
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
                $emailController = new EmailController($this->db);
                $title = 'تم إسناد رخصة عمل حرجة للمشرف';
                $body = 'تم إسناد رخصة عمل حرجة برقم إلى المشرف. الرجاء المراجعة.';
                $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                $notificationController->sendNotification($title, $body, [$supervisorId], $url, $managerId);
                $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), [$supervisorId], null, false, $this->buildPermitPdfAttachment($permitId));
            } catch (Exception $e) {
                // ignore notification/email errors
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
                $emailController = new EmailController($this->db);
                $title = 'المشرف أكمل مهمة الرخصة الحرجة';
                $body = 'يمكنك الآن إكمال بقية خطوات الرخصة.';
                $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                $notificationController->sendNotification($title, $body, [$creatorId], $url, $supervisorId);
                $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), [$creatorId], null, false, $this->buildPermitPdfAttachment($permitId));
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
                location = ?, supervisor = ?, task_start_datetime = ?, finishing_time = ?, work_description = ?, assigned_to = ?, critical_status = 'completed' WHERE id = ?");
            $stmt->execute([
                $data['location'],
                $data['supervisor'],
                $taskStartDatetime,
                $finishingTime,
                $data['work_description'] ?? '',
                $data['assigned_to'] ?: null,
                $permitId
            ]);

            // Clear and insert equipment_used
            $stmtDelEquip = $this->db->prepare("DELETE FROM hot_work_equipment_used WHERE hot_work_permit_id = ?");
            $stmtDelEquip->execute([$permitId]);
            if (!empty($data['equipment_used'])) {
                $stmtEquip = $this->db->prepare("INSERT INTO hot_work_equipment_used (
                    hot_work_permit_id, equipment_name
                ) VALUES (?, ?)");
                foreach ((array)$data['equipment_used'] as $equipmentName) {
                    if ($equipmentName !== '' && $equipmentName !== null) {
                        $stmtEquip->execute([$permitId, $equipmentName]);
                    }
                }
            }

            // Clear and insert additional_permits
            $stmtDelAdd = $this->db->prepare("DELETE FROM additional_hot_permits WHERE hot_work_permit_id = ?");
            $stmtDelAdd->execute([$permitId]);
            if (!empty($data['additional_permits'])) {
                $stmtAdd = $this->db->prepare("INSERT INTO additional_hot_permits (
                    hot_work_permit_id, permit_name, permit_number, image
                ) VALUES (?, ?, ?, ?)");
                foreach ($data['additional_permits'] as $permit) {
                    if (!empty($permit['permit_name'])) {
                        $stmtAdd->execute([$permitId, $permit['permit_name'], $permit['permit_number'] ?? '', $permit['image'] ?? null]);
                    }
                }
            }

            // Clear and insert control measures
            $stmtDelControl = $this->db->prepare("DELETE FROM hot_work_control_measures WHERE hot_work_permit_id = ?");
            $stmtDelControl->execute([$permitId]);
            if (!empty($data['control_measures'])) {
                $stmtControl = $this->db->prepare("INSERT INTO hot_work_control_measures (
                    hot_work_permit_id, measure_text, status, image
                ) VALUES (?, ?, ?, ?)");
                foreach ($data['control_measures'] as $measure) {
                    $stmtControl->execute([$permitId, $measure['text'], $measure['answer'], $measure['image'] ?? null]);
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
                    $emailController = new EmailController($this->db);
                    $title = 'تم إكمال رخصة العمل الحرجة';
                    $body = 'تمت إكمال الرخصة وتم إسنادها.';
                    $url = BASE_URL . "/public/requester/view_hot_work_license.php?id=" . $permitId;
                    $notificationController->sendNotification($title, $body, [$row['assigned_to']], $url, $row['created_by']);
                    $emailController->sendEmail($title, $this->withPermitLink($body, $permitId), [$row['assigned_to']], null, false, $this->buildPermitPdfAttachment($permitId));
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
                                                c.signature as creator_signature,
                                                cm.name as critical_manager_name, cs.name as critical_supervisor_name,
                                                ft.name as finishing_time_updated_by, sr.name as safety_reviewer_name,
                                                sr.signature as safety_reviewer_signature, h.safety_reviewed_at
                                        FROM hot_work_permit h
                                        LEFT JOIN users u ON h.assigned_to = u.id
                                        LEFT JOIN users c ON h.created_by = c.id
                                        LEFT JOIN users cm ON h.critical_manager_id = cm.id
                                        LEFT JOIN users cs ON h.critical_supervisor_id = cs.id
                                        LEFT JOIN users ft ON h.finishing_time_updated_by = ft.id
                                        LEFT JOIN users sr ON h.safety_reviewer_id = sr.id
                                        WHERE h.id = ?");
            $stmt->execute([$id]);
            $permit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$permit) {
                return ['success' => false, 'message' => 'Permit not found'];
            }

            $stmtEquip = $this->db->prepare("SELECT equipment_name FROM hot_work_equipment_used WHERE hot_work_permit_id = ?");
            $stmtEquip->execute([$id]);
            $equipmentList = $stmtEquip->fetchAll(PDO::FETCH_COLUMN);
            $permit['equipment_used'] = !empty($equipmentList) ? $equipmentList : (!empty($permit['equipment_used']) ? [$permit['equipment_used']] : []);

            $stmtAdd = $this->db->prepare("SELECT * FROM additional_hot_permits WHERE hot_work_permit_id = ?");
            $stmtAdd->execute([$id]);
            $permit['additional_permits'] = $stmtAdd->fetchAll(PDO::FETCH_ASSOC);

            $stmtControl = $this->db->prepare("SELECT * FROM hot_work_control_measures WHERE hot_work_permit_id = ?");
            $stmtControl->execute([$id]);
            $permit['control_measures'] = $stmtControl->fetchAll(PDO::FETCH_ASSOC);

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

    public function uploadAdditionalPermitImage($file)
    {
        if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'لم يتم اختيار صورة صالحة'];
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            return ['success' => false, 'message' => 'نوع الملف غير مدعوم'];
        }

        $uploadDir = __DIR__ . '/../public/uploads/additional_permits/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = uniqid('permit_') . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'فشل حفظ الصورة'];
        }

        return ['success' => true, 'path' => 'uploads/additional_permits/' . $filename];
    }

    public function updateFinishingTime($id, $finishingTime, $updatedBy)
    {
        try {
            $stmt = $this->db->prepare("UPDATE hot_work_permit SET finishing_time = ?, finishing_time_updated_by = ? WHERE id = ?");
            $stmt->execute([$finishingTime, $updatedBy, $id]);
            return ['success' => true, 'message' => 'Finishing time updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update: ' . $e->getMessage()];
        }
    }

    public function markPermitDone($permitId, $userId)
    {
        try {
            $stmt = $this->db->prepare("SELECT created_by, done_at FROM hot_work_permit WHERE id = ?");
            $stmt->execute([$permitId]);
            $permit = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$permit) {
                return ['success' => false, 'message' => 'Permit not found'];
            }

            if ((int)$permit['created_by'] !== (int)$userId) {
                return ['success' => false, 'message' => 'Not authorized to close this permit'];
            }

            if (!empty($permit['done_at'])) {
                return ['success' => false, 'message' => 'This permit has already been closed'];
            }

            $stmt = $this->db->prepare("UPDATE hot_work_permit SET done_at = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), $permitId]);

            return ['success' => true, 'message' => 'تم إغلاق الرخصة بنجاح'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to close permit: ' . $e->getMessage()];
        }
    }

    public function getStatistics($filters)
    {
        try {
            $query = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN done_at IS NOT NULL AND (finishing_time IS NULL OR done_at <= finishing_time) THEN 1 ELSE 0 END) as close,
                SUM(CASE WHEN done_at IS NULL AND (finishing_time IS NULL OR finishing_time >= NOW()) THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN (done_at IS NULL AND finishing_time IS NOT NULL AND finishing_time < NOW()) OR (done_at IS NOT NULL AND finishing_time IS NOT NULL AND done_at > finishing_time) THEN 1 ELSE 0 END) as not_active
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
                'not_active' => (int)$stats['not_active'],
                'open'       => (int)$stats['open'],
                'close'      => (int)$stats['close']
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
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'open') {
                    $where .= " AND h.done_at IS NULL AND (h.finishing_time IS NULL OR h.finishing_time >= NOW())";
                } elseif ($filters['status'] === 'not_active') {
                    $where .= " AND ((h.done_at IS NULL AND h.finishing_time IS NOT NULL AND h.finishing_time < NOW()) OR (h.done_at IS NOT NULL AND h.finishing_time IS NOT NULL AND h.done_at > h.finishing_time))";
                } elseif ($filters['status'] === 'close') {
                    $where .= " AND h.done_at IS NOT NULL AND (h.finishing_time IS NULL OR h.done_at <= h.finishing_time)";
                }
            }
            if (!empty($filters['permit_no'])) {
                $where .= " AND h.permit_no LIKE ?";
                $params[] = '%' . $filters['permit_no'] . '%';
            }
            if (!empty($filters['location'])) {
                $where .= " AND h.location = ?";
                $params[] = $filters['location'];
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

    public function deletePermit(int $permitId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM hot_work_permit WHERE id = ?");
            $stmt->execute([$permitId]);
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return ['success' => false, 'message' => 'Permit not found'];
            }

            // Collect uploaded image paths before the rows (and their images columns) are gone
            $imagePaths = [];
            $stmtAddImg = $this->db->prepare("SELECT image FROM additional_hot_permits WHERE hot_work_permit_id = ? AND image IS NOT NULL AND image <> ''");
            $stmtAddImg->execute([$permitId]);
            $imagePaths = array_merge($imagePaths, array_column($stmtAddImg->fetchAll(PDO::FETCH_ASSOC), 'image'));

            $stmtControlImg = $this->db->prepare("SELECT image FROM hot_work_control_measures WHERE hot_work_permit_id = ? AND image IS NOT NULL AND image <> ''");
            $stmtControlImg->execute([$permitId]);
            $imagePaths = array_merge($imagePaths, array_column($stmtControlImg->fetchAll(PDO::FETCH_ASSOC), 'image'));

            $this->db->beginTransaction();
            // Child tables (additional_hot_permits, hot_permit_approvals, hot_work_control_measures,
            // hot_work_equipment_used, hot_work_performers_check) all cascade on delete.
            $stmt = $this->db->prepare("DELETE FROM hot_work_permit WHERE id = ?");
            $stmt->execute([$permitId]);
            $this->db->commit();

            foreach ($imagePaths as $path) {
                $fullPath = __DIR__ . '/../public/' . $path;
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            return ['success' => true, 'message' => 'Hot Work Permit deleted successfully'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to delete permit: ' . $e->getMessage()];
        }
    }
}
