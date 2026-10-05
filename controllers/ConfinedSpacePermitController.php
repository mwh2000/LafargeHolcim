<?php
require_once __DIR__ . '/../core/LicenseNumberGenerator.php';
require_once __DIR__ . '/notificationsController.php';
require_once __DIR__ . '/emailController.php';

class ConfinedSpacePermitController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createPermit(array $data): array
    {
        $required = ['wo', 'company_name', 'location', 'supervisor', 'maintenance_type', 'task_start_datetime', 'finishing_time', 'issuer_name'];
        foreach ($required as $field) {
            if (trim((string)($data[$field] ?? '')) === '') {
                return ['success' => false, 'message' => 'يرجى تعبئة جميع المعلومات الأساسية المطلوبة'];
            }
        }
        if (empty($data['issuer_declaration']) || empty($data['created_by'])) {
            return ['success' => false, 'message' => 'يجب تأكيد التعهد قبل إصدار الرخصة'];
        }
        if (trim((string)($data['gas_device_model'] ?? '')) === '' || trim((string)($data['gas_qualified_person'] ?? '')) === '') {
            return ['success' => false, 'message' => 'أدخل رقم وموديل الجهاز واسم الشخص المؤهل للقياس'];
        }
        if (!in_array($data['gas_device_calibrated'] ?? null, ['نعم', 'لا'], true) || !in_array($data['continuous_monitoring'] ?? null, ['نعم', 'لا'], true)) {
            return ['success' => false, 'message' => 'حدد نعم أو لا لمعايرة الجهاز والمراقبة المستمرة'];
        }
        if (!in_array($data['rescue_equipment_available'] ?? null, ['نعم', 'لا'], true)) {
            return ['success' => false, 'message' => 'حدد ما إذا كانت معدات الإنقاذ متوفرة قرب موقع العمل'];
        }
        if (empty($data['additional_permits']) || !is_array($data['additional_permits'])) {
            return ['success' => false, 'message' => 'حدد تصريحاً إضافياً واحداً على الأقل'];
        }
        if (!empty($data['assigned_to']) && !$this->isAssignee((int)$data['assigned_to'])) {
            return ['success' => false, 'message' => 'المستخدم المحدد لا يملك دوراً صالحاً للإسناد'];
        }
        $entrants = array_values(array_filter($data['entrants'] ?? [], static fn($entrant) => is_array($entrant) && trim((string)($entrant['name'] ?? '')) !== ''));
        if (!$entrants) {
            return ['success' => false, 'message' => 'أضف اسم شخص واحد على الأقل'];
        }
        $data['entrants'] = $entrants;
        if (empty($data['control_measures'][0]['answer']) || !in_array($data['control_measures'][0]['answer'], ['نعم', 'لا'], true)) {
            return ['success' => false, 'message' => 'أجب عن تقييم المخاطر'];
        }
        $gasError = $this->validateGasMeasurements($data['gas_measurements'] ?? []);
        if ($gasError !== null) return ['success' => false, 'message' => $gasError];
        $controlImages = $data['control_images'] ?? [];
        if (!is_array($controlImages) || !$controlImages) {
            return ['success' => false, 'message' => 'أرفق صورة واحدة على الأقل لتقييم المخاطر'];
        }
        foreach ($controlImages as $imagePath) {
            if (!is_string($imagePath) || !preg_match('#^uploads/confined_space_control/[a-f0-9]{32}\\.(jpg|png|webp)$#', $imagePath)) {
                return ['success' => false, 'message' => 'مسار صورة تقييم المخاطر غير صالح'];
            }
        }

        try {
            $this->db->beginTransaction();
            $permitNo = LicenseNumberGenerator::next($this->db, 'confined_space_permit', 'OHSM-PTW-C.S-', 3);
            $stmt = $this->db->prepare("INSERT INTO confined_space_permit (
                permit_no, issuing_date_time, wo, company_name, location, supervisor,
                maintenance_type, task_start_datetime, finishing_time, work_description,
                ventilation_unit_no, ventilation_within_limits, emergency_responsible,
                rescue_equipment_available, gas_device_model, gas_qualified_person,
                gas_device_calibrated, continuous_monitoring, issuer_id, issuer_name,
                issuer_declaration, created_by, assigned_to, status
            ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')");
            $stmt->execute([
                $permitNo,
                trim($data['wo']),
                trim($data['company_name']),
                trim($data['location']),
                trim($data['supervisor']),
                trim($data['maintenance_type']),
                $this->normalizeDate($data['task_start_datetime']),
                $this->normalizeDate($data['finishing_time']),
                trim((string)($data['work_description'] ?? '')),
                trim((string)($data['ventilation_unit_no'] ?? '')) ?: null,
                !empty($data['ventilation_within_limits']) ? 1 : 0,
                trim((string)($data['emergency_responsible'] ?? '')) ?: null,
                $this->yesNo($data['rescue_equipment_available'] ?? null),
                trim((string)($data['gas_device_model'] ?? '')) ?: null,
                trim((string)($data['gas_qualified_person'] ?? '')) ?: null,
                $this->yesNo($data['gas_device_calibrated'] ?? null),
                $this->yesNo($data['continuous_monitoring'] ?? null),
                (int)$data['created_by'],
                trim($data['issuer_name']),
                1,
                (int)$data['created_by'],
                !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null
            ]);
            $permitId = (int)$this->db->lastInsertId();
            $equipmentStmt = $this->db->prepare('UPDATE confined_space_permit SET equipment_used = ?, issuer_declaration = ? WHERE id = ?');
            $equipment = $data['equipment_used'] ?? [];
            if (!is_array($equipment)) $equipment = [$equipment];
            $equipment = array_values(array_filter(array_map(static fn($value) => trim((string)$value), $equipment)));
            $equipmentStmt->execute([$equipment ? json_encode($equipment, JSON_UNESCAPED_UNICODE) : null, !empty($data['issuer_declaration']) ? 1 : 0, $permitId]);

            $this->insertRows('additional_confined_space_permits', 'permit_name, permit_number', $permitId, $data['additional_permits'] ?? [], function ($item) {
                return [trim((string)($item['permit_name'] ?? '')), trim((string)($item['permit_number'] ?? ''))];
            });
            $controlStmt = $this->db->prepare('INSERT INTO confined_space_control_measures (confined_space_permit_id, measure_text, status) VALUES (?, ?, ?)');
            $controlMeasure = $data['control_measures'][0];
            $controlStmt->execute([$permitId, trim((string)$controlMeasure['text']), $controlMeasure['answer']]);
            $controlMeasureId = (int)$this->db->lastInsertId();
            $imageStmt = $this->db->prepare('INSERT INTO confined_space_control_images (control_measure_id, image_path) VALUES (?, ?)');
            foreach ($controlImages as $imagePath) {
                $imageStmt->execute([$controlMeasureId, $imagePath]);
            }
            $this->insertRows('confined_space_entrants', 'person_name, medically_fit, authorized_to_enter', $permitId, $data['entrants'] ?? [], function ($item) {
                return [trim((string)($item['name'] ?? '')), !empty($item['medically_fit']) ? 1 : 0, !empty($item['authorized_to_enter']) ? 1 : 0];
            });
            $this->insertRows('confined_space_communications', 'communication_method', $permitId, $data['communications'] ?? [], function ($item) {
                return [trim((string)$item)];
            });
            $this->insertRows('confined_space_rescue_equipment', 'equipment_name', $permitId, $data['rescue_equipment'] ?? [], function ($item) {
                return [trim((string)$item)];
            });
            $this->insertRows('confined_space_gas_measurements', 'measurement_time, oxygen_percent, lel_uel_percent, co_ppm, h2s_ppm, added_by', $permitId, $data['gas_measurements'] ?? [], function ($item) use ($data) {
                return array_map(static fn($value) => trim((string)($value ?? '')), [
                    $item['measurement_time'] ?? '',
                    $item['oxygen_percent'] ?? '',
                    $item['lel_uel_percent'] ?? '',
                    $item['co_ppm'] ?? '',
                    $item['h2s_ppm'] ?? '',
                    (string)(int)$data['created_by']
                ]);
            });

            $this->db->commit();
            $this->notifyCreated($permitId, $permitNo, (int)$data['created_by'], !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null);
            return ['success' => true, 'message' => 'تم إنشاء رخصة دخول الأماكن المغلقة بنجاح', 'id' => $permitId, 'permit_no' => $permitNo];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Confined-space permit creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'تعذر حفظ الرخصة'];
        }
    }

    private function normalizeDate($value): string
    {
        $timestamp = strtotime((string)$value);
        if ($timestamp === false) throw new InvalidArgumentException('Invalid permit date');
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function yesNo($value): ?string
    {
        return in_array($value, ['نعم', 'لا'], true) ? $value : null;
    }

    private function isAssignee(int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE id = ? AND role_id IN (3, 5, 7)');
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }

    public function getAssignees(): array
    {
        $stmt = $this->db->query('SELECT id, name FROM users WHERE role_id IN (3, 5, 7) ORDER BY name');
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public function getGasSigner(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT id, name, signature FROM users WHERE id = ? AND role_id = 7');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return ['success' => false, 'message' => 'هذه العملية متاحة لمستخدم role 7 فقط'];
        return ['success' => true, 'data' => $user];
    }

    private function notifyCreated(int $permitId, string $permitNo, int $creatorId, ?int $assigneeId): void
    {
        $url = BASE_URL . '/public/requester/view_confined_space_license.php?id=' . $permitId;
        $targets = array_values(array_unique(array_filter([$creatorId, $assigneeId])));
        try {
            $notifications = new NotificationController($this->db);
            $emails = new EmailController($this->db);
            $title = 'رخصة دخول أماكن مغلقة جديدة';
            foreach ($targets as $targetId) {
                $body = $targetId === $creatorId
                    ? "تم إنشاء الرخصة {$permitNo} وإسنادها بنجاح. اضغط لعرض الرخصة."
                    : "تم إسناد رخصة دخول الأماكن المغلقة {$permitNo} إليك. اضغط لعرض الرخصة.";
                $notifications->sendNotification($title, $body, [$targetId], $url, $creatorId);
            }
            if ($assigneeId) {
                $emails->sendEmail($title, "تم إسناد الرخصة {$permitNo} إليك.<br><a href='{$url}'>فتح الرخصة</a>", [$assigneeId]);
            }
        } catch (Throwable $e) {
            error_log('Confined-space permit notification failed: ' . $e->getMessage());
        }
    }

    public function updatePermit(int $permitId, int $userId, array $data): array
    {
        $owner = $this->db->prepare("SELECT permit_no, assigned_to, status FROM confined_space_permit WHERE id = ? AND created_by = ?");
        $owner->execute([$permitId, $userId]);
        $permit = $owner->fetch(PDO::FETCH_ASSOC);
        if (!$permit) return ['success' => false, 'message' => 'فقط منشئ الرخصة يستطيع تعديلها'];
        if ($permit['status'] === 'closed') return ['success' => false, 'message' => 'لا يمكن تعديل رخصة مغلقة'];
        foreach (['wo', 'company_name', 'location', 'supervisor', 'maintenance_type', 'task_start_datetime', 'finishing_time', 'work_description'] as $field) {
            if (trim((string)($data[$field] ?? '')) === '') return ['success' => false, 'message' => 'أكمل الحقول الأساسية المطلوبة'];
        }
        if (!empty($data['assigned_to']) && !$this->isAssignee((int)$data['assigned_to'])) {
            return ['success' => false, 'message' => 'المستخدم المحدد لا يملك دوراً صالحاً للإسناد'];
        }
        $gasError = $this->validateGasMeasurements($data['gas_measurements'] ?? []);
        if ($gasError !== null) return ['success' => false, 'message' => $gasError];

        try {
            $this->db->beginTransaction();
            $update = $this->db->prepare("UPDATE confined_space_permit SET wo = ?, company_name = ?, location = ?, supervisor = ?, equipment_used = ?, maintenance_type = ?, task_start_datetime = ?, finishing_time = ?, work_description = ?, ventilation_unit_no = ?, ventilation_within_limits = ?, emergency_responsible = ?, rescue_equipment_available = ?, gas_device_model = ?, gas_qualified_person = ?, gas_device_calibrated = ?, continuous_monitoring = ?, issuer_declaration = ?, assigned_to = ? WHERE id = ? AND created_by = ? AND status <> 'closed'");
            $equipment = $data['equipment_used'] ?? [];
            if (!is_array($equipment)) $equipment = [$equipment];
            $update->execute([
                trim($data['wo']),
                trim($data['company_name']),
                trim($data['location']),
                trim($data['supervisor']),
                json_encode(array_values(array_filter($equipment)), JSON_UNESCAPED_UNICODE),
                trim($data['maintenance_type']),
                $this->normalizeDate($data['task_start_datetime']),
                $this->normalizeDate($data['finishing_time']),
                trim($data['work_description']),
                trim((string)($data['ventilation_unit_no'] ?? '')) ?: null,
                !empty($data['ventilation_within_limits']) ? 1 : 0,
                trim((string)($data['emergency_responsible'] ?? '')) ?: null,
                $this->yesNo($data['rescue_equipment_available'] ?? null),
                trim((string)($data['gas_device_model'] ?? '')) ?: null,
                trim((string)($data['gas_qualified_person'] ?? '')) ?: null,
                $this->yesNo($data['gas_device_calibrated'] ?? null),
                $this->yesNo($data['continuous_monitoring'] ?? null),
                !empty($data['issuer_declaration']) ? 1 : 0,
                !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
                $permitId,
                $userId
            ]);

            foreach (['additional_confined_space_permits', 'confined_space_control_measures', 'confined_space_entrants', 'confined_space_communications', 'confined_space_rescue_equipment'] as $table) {
                $delete = $this->db->prepare("DELETE FROM {$table} WHERE confined_space_permit_id = ?");
                $delete->execute([$permitId]);
            }
            $this->insertRows('additional_confined_space_permits', 'permit_name, permit_number', $permitId, $data['additional_permits'] ?? [], static fn($item) => [trim((string)($item['permit_name'] ?? '')), trim((string)($item['permit_number'] ?? ''))]);
            $controlStmt = $this->db->prepare('INSERT INTO confined_space_control_measures (confined_space_permit_id, measure_text, status) VALUES (?, ?, ?)');
            $control = $data['control_measures'][0] ?? ['text' => 'هل تم إعداد تقييم للمخاطر قبل الشروع بالدخول؟', 'answer' => 'غير محدد'];
            $controlStmt->execute([$permitId, trim((string)$control['text']), trim((string)$control['answer'])]);
            $controlId = (int)$this->db->lastInsertId();
            $imageStmt = $this->db->prepare('INSERT INTO confined_space_control_images (control_measure_id, image_path) VALUES (?, ?)');
            foreach (array_unique($data['control_images'] ?? []) as $imagePath) {
                if (is_string($imagePath) && preg_match('#^uploads/confined_space_control/[a-f0-9]{32}\.(jpg|png|webp)$#', $imagePath)) $imageStmt->execute([$controlId, $imagePath]);
            }
            $this->insertRows('confined_space_entrants', 'person_name, medically_fit, authorized_to_enter', $permitId, $data['entrants'] ?? [], static fn($item) => [trim((string)($item['name'] ?? '')), !empty($item['medically_fit']) ? 1 : 0, !empty($item['authorized_to_enter']) ? 1 : 0]);
            $this->insertRows('confined_space_communications', 'communication_method', $permitId, $data['communications'] ?? [], static fn($item) => [trim((string)$item)]);
            $this->insertRows('confined_space_rescue_equipment', 'equipment_name', $permitId, $data['rescue_equipment'] ?? [], static fn($item) => [trim((string)$item)]);

            $existingGasIds = $this->db->prepare('SELECT id FROM confined_space_gas_measurements WHERE confined_space_permit_id = ?');
            $existingGasIds->execute([$permitId]);
            $allowedGasIds = array_map('intval', $existingGasIds->fetchAll(PDO::FETCH_COLUMN));
            $submittedGasIds = array_values(array_filter(array_map(static fn($measurement) => (int)($measurement['id'] ?? 0), $data['gas_measurements']), static fn($id) => $id > 0));
            $removedGasIds = array_values(array_diff($allowedGasIds, $submittedGasIds));
            if ($removedGasIds) {
                $deleteGas = $this->db->prepare('DELETE FROM confined_space_gas_measurements WHERE confined_space_permit_id = ? AND id = ?');
                foreach ($removedGasIds as $removedGasId) $deleteGas->execute([$permitId, $removedGasId]);
            }
            $updateGas = $this->db->prepare('UPDATE confined_space_gas_measurements SET measurement_time = ?, oxygen_percent = ?, lel_uel_percent = ?, co_ppm = ?, h2s_ppm = ? WHERE id = ? AND confined_space_permit_id = ?');
            $insertGas = $this->db->prepare('INSERT INTO confined_space_gas_measurements (confined_space_permit_id, measurement_time, oxygen_percent, lel_uel_percent, co_ppm, h2s_ppm, added_by) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($data['gas_measurements'] as $measurement) {
                $values = [$measurement['measurement_time'], $measurement['oxygen_percent'], $measurement['lel_uel_percent'], $measurement['co_ppm'], $measurement['h2s_ppm']];
                $measurementId = (int)($measurement['id'] ?? 0);
                if ($measurementId && in_array($measurementId, $allowedGasIds, true)) $updateGas->execute([...$values, $measurementId, $permitId]);
                else $insertGas->execute([$permitId, ...$values, $userId]);
            }
            $this->db->commit();

            $newAssigneeId = !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null;
            if ($newAssigneeId && $newAssigneeId !== (int)$permit['assigned_to']) $this->notifyCreated($permitId, $permit['permit_no'], $userId, $newAssigneeId);
            return ['success' => true, 'message' => 'تم تحديث الرخصة بنجاح', 'id' => $permitId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Confined-space permit update failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'تعذر تحديث الرخصة'];
        }
    }

    public function addGasMeasurement(int $permitId, int $userId, array $measurement): array
    {
        $permitStmt = $this->db->prepare("SELECT id FROM confined_space_permit WHERE id = ? AND status = 'open'");
        $permitStmt->execute([$permitId]);
        if (!$permitStmt->fetchColumn()) return ['success' => false, 'message' => 'الرخصة غير موجودة أو مغلقة'];
        $error = $this->validateGasMeasurements([$measurement]);
        if ($error !== null) return ['success' => false, 'message' => $error];
        $userStmt = $this->db->prepare('SELECT signature FROM users WHERE id = ? AND role_id = 7');
        $userStmt->execute([$userId]);
        $signaturePath = $userStmt->fetchColumn();
        $stmt = $this->db->prepare('INSERT INTO confined_space_gas_measurements (confined_space_permit_id, measurement_time, oxygen_percent, lel_uel_percent, co_ppm, h2s_ppm, added_by, added_signature_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$permitId, $measurement['measurement_time'], $measurement['oxygen_percent'], $measurement['lel_uel_percent'], $measurement['co_ppm'], $measurement['h2s_ppm'], $userId, $signaturePath ?: null]);
        return ['success' => true, 'message' => 'تمت إضافة قراءة الغاز', 'id' => (int)$this->db->lastInsertId()];
    }

    public function closePermit(int $permitId, int $userId): array
    {
        $stmt = $this->db->prepare("UPDATE confined_space_permit SET status = 'closed', closed_at = NOW() WHERE id = ? AND created_by = ? AND status = 'open'");
        $stmt->execute([$permitId, $userId]);
        if (!$stmt->rowCount()) return ['success' => false, 'message' => 'الرخصة غير موجودة أو مغلقة مسبقاً أو لا تملك صلاحية إغلاقها'];
        return ['success' => true, 'message' => 'تم إغلاق الرخصة بنجاح'];
    }

    private function validateGasMeasurements(array $measurements): ?string
    {
        if (!$measurements) return 'أضف قراءة غازات واحدة على الأقل';
        foreach ($measurements as $index => $measurement) {
            if (empty($measurement['measurement_time'])) return 'يجب تحديد تاريخ ووقت كل قراءة';
            foreach (['oxygen_percent', 'lel_uel_percent', 'co_ppm', 'h2s_ppm'] as $field) {
                if (!isset($measurement[$field]) || $measurement[$field] === '' || !is_numeric($measurement[$field])) {
                    return 'أدخل قيماً رقمية لجميع الغازات في القراءة رقم ' . ($index + 1);
                }
            }
            $oxygen = (float)$measurement['oxygen_percent'];
            $lel = (float)$measurement['lel_uel_percent'];
            $co = (float)$measurement['co_ppm'];
            $h2s = (float)$measurement['h2s_ppm'];
            if ($oxygen < 19.5 || $oxygen > 23.5) return 'قراءة الأوكسجين يجب أن تكون بين 19.5% و23.5%';
            if ($lel < 0 || $lel >= 10) return 'قراءة LEL/UEL يجب أن تكون أقل من 10%';
            if ($co < 0 || $co >= 5000) return 'قراءة CO يجب أن تكون أقل من 5000 PPM';
            if ($h2s < 0 || $h2s >= 10) return 'قراءة H₂S يجب أن تكون أقل من 10 PPM';
        }
        return null;
    }

    public function uploadControlImages(array $files): array
    {
        if (empty($files['name']) || !is_array($files['name'])) {
            return ['success' => false, 'message' => 'اختر صورة واحدة على الأقل'];
        }
        $uploadDirectory = __DIR__ . '/../public/uploads/confined_space_control/';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            return ['success' => false, 'message' => 'تعذر تجهيز مجلد الصور'];
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $savedPaths = [];
        foreach ($files['name'] as $index => $originalName) {
            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($files['size'][$index] ?? 0) > 8 * 1024 * 1024) {
                foreach ($savedPaths as $savedPath) @unlink(__DIR__ . '/../public/' . $savedPath);
                return ['success' => false, 'message' => 'تعذر رفع إحدى الصور أو تجاوز حجمها 8 ميغابايت'];
            }
            $tmpName = $files['tmp_name'][$index] ?? '';
            $mimeType = is_uploaded_file($tmpName) ? $finfo->file($tmpName) : false;
            if (!isset($extensions[$mimeType])) {
                foreach ($savedPaths as $savedPath) @unlink(__DIR__ . '/../public/' . $savedPath);
                return ['success' => false, 'message' => 'يسمح فقط بصور JPG وPNG وWEBP'];
            }
            $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
            if (!move_uploaded_file($tmpName, $uploadDirectory . $filename)) {
                foreach ($savedPaths as $savedPath) @unlink(__DIR__ . '/../public/' . $savedPath);
                return ['success' => false, 'message' => 'تعذر حفظ إحدى الصور'];
            }
            $savedPaths[] = 'uploads/confined_space_control/' . $filename;
        }
        return ['success' => true, 'data' => ['paths' => $savedPaths]];
    }

    private function insertRows(string $table, string $columns, int $permitId, array $items, callable $values): void
    {
        $columnNames = array_map('trim', explode(',', $columns));
        $sql = "INSERT INTO {$table} (confined_space_permit_id, {$columns}) VALUES (" . implode(', ', array_fill(0, count($columnNames) + 1, '?')) . ')';
        $stmt = $this->db->prepare($sql);
        foreach ($items as $item) {
            $row = $values($item);
            if (count(array_filter($row, static fn($value) => trim((string)$value) !== '')) === 0) continue;
            $stmt->execute(array_merge([$permitId], $row));
        }
    }

    public function getPermit(int $id, int $userId, int $roleId): array
    {
        $stmt = $this->db->prepare("SELECT p.*, u.name AS creator_name, u.signature AS creator_signature, a.name AS assigned_to_name FROM confined_space_permit p LEFT JOIN users u ON u.id = p.created_by LEFT JOIN users a ON a.id = p.assigned_to WHERE p.id = ?");
        $stmt->execute([$id]);
        $permit = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$permit) return ['success' => false, 'message' => 'الرخصة غير موجودة'];
        if ((int)$permit['created_by'] !== $userId && !in_array($roleId, [1, 3, 4, 5, 6, 7], true)) {
            return ['success' => false, 'message' => 'غير مخول لعرض هذه الرخصة'];
        }
        $equipment = json_decode((string)($permit['equipment_used'] ?? ''), true);
        $permit['equipment_used'] = is_array($equipment) ? $equipment : array_filter([(string)($permit['equipment_used'] ?? '')]);

        $collections = [
            'additional_permits' => ['additional_confined_space_permits', 'permit_name, permit_number'],
            'control_measures' => ['confined_space_control_measures', 'id, measure_text, status'],
            'entrants' => ['confined_space_entrants', 'person_name, medically_fit, authorized_to_enter'],
            'communications' => ['confined_space_communications', 'communication_method'],
            'rescue_equipment' => ['confined_space_rescue_equipment', 'equipment_name'],
        ];
        foreach ($collections as $key => [$table, $columns]) {
            $childStmt = $this->db->prepare("SELECT {$columns} FROM {$table} WHERE confined_space_permit_id = ? ORDER BY id");
            $childStmt->execute([$id]);
            $permit[$key] = $childStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $gasStmt = $this->db->prepare('SELECT gm.id, gm.measurement_time, gm.oxygen_percent, gm.lel_uel_percent, gm.co_ppm, gm.h2s_ppm, gm.added_by, gm.added_at, u.name AS added_by_name, u.signature AS added_by_signature FROM confined_space_gas_measurements gm LEFT JOIN users u ON u.id = gm.added_by WHERE gm.confined_space_permit_id = ? ORDER BY gm.id');
        $gasStmt->execute([$id]);
        $permit['gas_measurements'] = $gasStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($permit['control_measures'] as &$measure) {
            $imageQuery = $this->db->prepare('SELECT id, image_path FROM confined_space_control_images WHERE control_measure_id = ? ORDER BY id');
            $imageQuery->execute([(int)$measure['id']]);
            $measure['images'] = $imageQuery->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($measure);
        return ['success' => true, 'data' => $permit];
    }

    public function getAll(int $userId, int $roleId): array
    {
        $sql = "SELECT p.id, p.permit_no, p.issuing_date_time, p.wo, p.company_name, p.location, p.finishing_time, p.issuer_name, p.status, p.assigned_to FROM confined_space_permit p";
        $params = [];
        if (!in_array($roleId, [1, 3, 4, 5, 6, 7], true)) {
            $sql .= ' WHERE p.created_by = ?';
            $params[] = $userId;
        }
        $sql .= ' ORDER BY p.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }
}
