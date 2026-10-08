<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

// Admin-managed lookup lists used by the permit forms.
class PermitListController
{
    use ApiResponseTrait;

    public const LISTS = [
        'confined_space_equipment' => ['table' => 'confined_space_equipment', 'label' => 'المعدات المستخدمة - رخصة الأماكن المغلقة', 'has_inspection_date' => false],
        'welders' => ['table' => 'hot_work_welders', 'label' => 'قائمة اللحامين', 'has_inspection_date' => true],
        'fire_sentries' => ['table' => 'hot_work_fire_sentries', 'label' => 'قائمة مراقبي النار', 'has_inspection_date' => true],
    ];

    // A welder / fire sentry is considered expired 18 months after the last inspection.
    private const INSPECTION_VALIDITY = '+18 months';

    private PDO $conn;
    private array $list;

    public function __construct(PDO $conn, string $listKey)
    {
        if (!isset(self::LISTS[$listKey])) {
            throw new InvalidArgumentException('Unknown list');
        }
        $this->conn = $conn;
        $this->list = self::LISTS[$listKey];
    }

    public function getAll(string $search = ''): array
    {
        $query = "SELECT * FROM {$this->list['table']}";
        $params = [];
        if ($search !== '') {
            $query .= ' WHERE name LIKE ?';
            $params[] = '%' . $search . '%';
        }
        $stmt = $this->conn->prepare($query . ' ORDER BY id');
        $stmt->execute($params);
        return $this->respond(true, 'Items retrieved successfully', ['items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // Rows for the permit forms; inspected lists get an `expired` flag.
    public function getOptions(): array
    {
        $rows = $this->conn->query("SELECT * FROM {$this->list['table']} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        if (!$this->list['has_inspection_date']) return $rows;
        $now = new DateTime();
        foreach ($rows as &$row) {
            $row['expired'] = !empty($row['inspection_date'])
                && $now > (new DateTime($row['inspection_date']))->modify(self::INSPECTION_VALIDITY);
        }
        unset($row);
        return $rows;
    }

    public function create(array $data): array
    {
        $values = $this->validate($data);
        if (isset($values['error'])) return $this->respond(false, $values['error'], null, ['code' => 400], 400);
        if ($this->nameExists($values['name'])) return $this->respond(false, 'هذا الاسم موجود مسبقاً', null, ['code' => 409], 409);

        $columns = array_keys($values);
        $stmt = $this->conn->prepare("INSERT INTO {$this->list['table']} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')');
        $stmt->execute(array_values($values));
        return $this->respond(true, 'تمت الإضافة بنجاح', ['id' => (int)$this->conn->lastInsertId()]);
    }

    public function update(int $id, array $data): array
    {
        $values = $this->validate($data);
        if (isset($values['error'])) return $this->respond(false, $values['error'], null, ['code' => 400], 400);
        if ($this->nameExists($values['name'], $id)) return $this->respond(false, 'هذا الاسم موجود مسبقاً', null, ['code' => 409], 409);

        $assignments = implode(', ', array_map(static fn($column) => "{$column} = ?", array_keys($values)));
        $stmt = $this->conn->prepare("UPDATE {$this->list['table']} SET {$assignments} WHERE id = ?");
        $stmt->execute([...array_values($values), $id]);
        return $this->respond(true, 'تم التعديل بنجاح');
    }

    public function delete(int $id): array
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->list['table']} WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->rowCount()) return $this->respond(false, 'العنصر غير موجود', null, ['code' => 404], 404);
        return $this->respond(true, 'تم الحذف بنجاح');
    }

    private function validate(array $data): array
    {
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') return ['error' => 'الاسم مطلوب'];
        $values = ['name' => $name];
        if ($this->list['has_inspection_date']) {
            $date = trim((string)($data['inspection_date'] ?? ''));
            if ($date !== '' && DateTime::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') !== $date) {
                return ['error' => 'تاريخ الفحص غير صالح'];
            }
            $values['inspection_date'] = $date === '' ? null : $date;
        }
        return $values;
    }

    private function nameExists(string $name, int $exceptId = 0): bool
    {
        $stmt = $this->conn->prepare("SELECT 1 FROM {$this->list['table']} WHERE name = ? AND id <> ?");
        $stmt->execute([$name, $exceptId]);
        return (bool)$stmt->fetchColumn();
    }
}
