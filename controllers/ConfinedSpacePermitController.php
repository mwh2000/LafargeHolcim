<?php
require_once __DIR__ . '/../core/LicenseNumberGenerator.php';

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
        if (empty($data['additional_permits']) || !is_array($data['additional_permits'])) {
            return ['success' => false, 'message' => 'حدد تصريحاً إضافياً واحداً على الأقل'];
        }
        $entrants = array_values(array_filter($data['entrants'] ?? [], static fn($entrant) => is_array($entrant) && trim((string)($entrant['name'] ?? '')) !== ''));
        if (!$entrants) {
            return ['success' => false, 'message' => 'أضف اسم شخص واحد على الأقل'];
        }
        $data['entrants'] = $entrants;

        try {
            $this->db->beginTransaction();
            $permitNo = LicenseNumberGenerator::next($this->db, 'confined_space_permit', 'OHSM-CS-00');
            $stmt = $this->db->prepare("INSERT INTO confined_space_permit (
                permit_no, issuing_date_time, wo, company_name, location, supervisor,
                maintenance_type, task_start_datetime, finishing_time, work_description,
                ventilation_unit_no, ventilation_within_limits, emergency_responsible,
                rescue_equipment_available, traffic_control_required, gas_device_model,
                gas_qualified_person, gas_device_calibrated, continuous_monitoring,
                issuer_id, issuer_name, issuer_qualified, issuer_medically_fit,
                issuer_declaration, created_by
            ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1, ?)");
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
                $this->yesNo($data['traffic_control_required'] ?? null),
                trim((string)($data['gas_device_model'] ?? '')) ?: null,
                trim((string)($data['gas_qualified_person'] ?? '')) ?: null,
                $this->yesNo($data['gas_device_calibrated'] ?? null),
                $this->yesNo($data['continuous_monitoring'] ?? null),
                (int)$data['created_by'],
                trim($data['issuer_name']),
                (int)$data['created_by']
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
            $this->insertRows('confined_space_control_measures', 'measure_text, status', $permitId, $data['control_measures'] ?? [], function ($item) {
                return [trim((string)($item['text'] ?? '')), trim((string)($item['answer'] ?? ''))];
            });
            $this->insertRows('confined_space_entrants', 'person_name, medically_fit, authorized_to_enter', $permitId, $data['entrants'] ?? [], function ($item) {
                return [trim((string)($item['name'] ?? '')), !empty($item['medically_fit']) ? 1 : 0, !empty($item['authorized_to_enter']) ? 1 : 0];
            });
            $this->insertRows('confined_space_communications', 'communication_method', $permitId, $data['communications'] ?? [], function ($item) {
                return [trim((string)$item)];
            });
            $this->insertRows('confined_space_rescue_equipment', 'equipment_name', $permitId, $data['rescue_equipment'] ?? [], function ($item) {
                return [trim((string)$item)];
            });
            $this->insertRows('confined_space_gas_measurements', 'measurement_time, oxygen_percent, lel_uel_percent, co_ppm, h2s_ppm', $permitId, $data['gas_measurements'] ?? [], function ($item) {
                return array_map(static fn($value) => trim((string)($value ?? '')), [
                    $item['measurement_time'] ?? '',
                    $item['oxygen_percent'] ?? '',
                    $item['lel_uel_percent'] ?? '',
                    $item['co_ppm'] ?? '',
                    $item['h2s_ppm'] ?? ''
                ]);
            });

            $this->db->commit();
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
        $stmt = $this->db->prepare("SELECT p.*, u.name AS creator_name FROM confined_space_permit p LEFT JOIN users u ON u.id = p.created_by WHERE p.id = ?");
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
            'control_measures' => ['confined_space_control_measures', 'measure_text, status'],
            'entrants' => ['confined_space_entrants', 'person_name, medically_fit, authorized_to_enter'],
            'communications' => ['confined_space_communications', 'communication_method'],
            'rescue_equipment' => ['confined_space_rescue_equipment', 'equipment_name'],
            'gas_measurements' => ['confined_space_gas_measurements', 'measurement_time, oxygen_percent, lel_uel_percent, co_ppm, h2s_ppm'],
        ];
        foreach ($collections as $key => [$table, $columns]) {
            $childStmt = $this->db->prepare("SELECT {$columns} FROM {$table} WHERE confined_space_permit_id = ? ORDER BY id");
            $childStmt->execute([$id]);
            $permit[$key] = $childStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return ['success' => true, 'data' => $permit];
    }

    public function getAll(int $userId, int $roleId): array
    {
        $sql = "SELECT p.id, p.permit_no, p.issuing_date_time, p.wo, p.company_name, p.location, p.finishing_time, p.issuer_name FROM confined_space_permit p";
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
