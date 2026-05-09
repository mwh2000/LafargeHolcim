<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

session_start();

class ActionController
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    use ApiResponseTrait;

    /** ✅ Create new Action */
    public function create(array $data, array $files = [])
    {
        /* =========================
     * 1️⃣ Validation
     * ========================= */
        foreach (['assigned_user_id', 'expiry_date'] as $field) {
            if (empty($data[$field])) {
                return $this->respond(false, "{$field} is required", null, ['field' => $field], 400);
            }
        }

        /* =========================
     * 2️⃣ Current User Group
     * ========================= */
        $stmt = $this->conn->prepare("
        SELECT `group`
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
        $stmt->execute([$_COOKIE['user_id'] ?? null]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return $this->respond(false, "Unauthorized", null, null, 401);
        }

        $userGroup = $user['group'];

        /* =========================
     * 3️⃣ Normalize Optional Fields
     * ========================= */
        $typeId = isset($data['type_id']) && $data['type_id'] !== ''
            ? (int) $data['type_id']
            : null;

        $startDate = !empty($data['start_date'])
            ? $data['start_date']
            : null;

        /* =========================
     * 4️⃣ Upload Attachment (PDF)
     * ========================= */
        $attachmentPath = $this->uploadFile(
            $files['attachment'] ?? null,
            ['pdf'],
            'uploads/attachments'
        );

        /* =========================
     * 5️⃣ Insert Action
     * ========================= */
        $stmt = $this->conn->prepare("
        INSERT INTO actions (
            type_id, `group`, location, related_topics, incident_classfication, incident,
            visit_duration, environment, area_visited, description,
            action, priority, assigned_user_id, start_date, expiry_date,
            attachment, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        $stmt->execute([
            $typeId,
            $userGroup,
            $data['location'] ?? null,
            $data['related_topics'] ?? null,
            $data['incident_classfication'] ?? null,
            $data['incident'] ?? null,
            $data['visit_duration'] ?? null,
            $data['environment'] ?? null,
            $data['area_visited'] ?? null,
            $data['description'],
            $data['action'],
            $data['priority'],
            (int) $data['assigned_user_id'],
            $startDate,
            $data['expiry_date'],
            $attachmentPath,
            $data['created_by']
        ]);

        $actionId = (int) $this->conn->lastInsertId();

        /* =========================
     * 6️⃣ Upload Multiple Images
     * ========================= */
        if (!empty($files['images']) && is_array($files['images']['name'])) {

            $imageStmt = $this->conn->prepare("
            INSERT INTO action_images (action_id, image_path)
            VALUES (?, ?)
        ");

            foreach ($files['images']['name'] as $index => $name) {

                if ($files['images']['error'][$index] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $singleFile = [
                    'name'     => $files['images']['name'][$index],
                    'type'     => $files['images']['type'][$index],
                    'tmp_name' => $files['images']['tmp_name'][$index],
                    'error'    => $files['images']['error'][$index],
                    'size'     => $files['images']['size'][$index],
                ];

                $imagePath = $this->uploadFile(
                    $singleFile,
                    ['jpg', 'jpeg', 'png'],
                    'uploads/images'
                );

                if ($imagePath) {
                    $imageStmt->execute([$actionId, $imagePath]);
                }
            }
        }

        /* =========================
     * 7️⃣ Response
     * ========================= */
        return $this->respond(true, 'Action created successfully', [
            'id' => $actionId,
            'action' => $data['action'],
            'assigned_user_id' => $data['assigned_user_id']
        ]);
    }

    /** ✅ Update Action */
    public function update(int $id, array $data, array $files = [])
    {
        // تحقق من وجود السجل
        $check = $this->conn->prepare("SELECT * FROM actions WHERE id = ?");
        $check->execute([$id]);
        $action = $check->fetch(PDO::FETCH_ASSOC);
        if (!$action) {
            return $this->respond(false, 'Action not found', null, ['code' => 404], 404);
        }

        $fields = [];
        $values = [];

        foreach (
            [
                'assigned_user_id',
                'start_date',
                'expiry_date',
                'type_id',
                'location',
                'related_topics',
                'incident',
                'visit_duration',
                'environment',
                'area_visited',
                'description',
                'action',
                'priority',
                'incident_classfication'
            ] as $field
        ) {

            if (array_key_exists($field, $data)) {

                $value = $data[$field];

                // ✅ معالجة التواريخ الفارغة
                if (in_array($field, ['start_date', 'expiry_date']) && $value === '') {
                    $value = null;
                }

                $fields[] = "$field = ?";
                $values[] = $value;
            }
        }



        // تحديث الملفات إن وجدت
        if (isset($files['image'])) {
            $imagePath = $this->uploadFile($files['image'], ['jpg', 'jpeg', 'png'], 'uploads/images');
            $fields[] = "image = ?";
            $values[] = $imagePath;
        }

        if (isset($files['attachment'])) {
            $attachmentPath = $this->uploadFile($files['attachment'], ['pdf'], 'uploads/attachments');
            $fields[] = "attachment = ?";
            $values[] = $attachmentPath;
        }

        if (empty($fields)) {
            return $this->respond(false, 'No fields to update', null, ['code' => 400], 400);
        }

        $values[] = $id;
        $sql = "UPDATE actions SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($values);

        return $this->respond(true, 'Action updated successfully');
    }

    /** ✅ Delete Action */
    public function delete(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM actions WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            return $this->respond(false, 'Action not found', null, ['code' => 404], 404);
        }

        return $this->respond(true, 'Action deleted successfully');
    }

    public function updateStatus(int $id, string $status = 'closed', string $note = '')
    {
        // تحقق من وجود السجل
        $check = $this->conn->prepare("SELECT * FROM actions WHERE id = ?");
        $check->execute([$id]);
        $action = $check->fetch(PDO::FETCH_ASSOC);

        if (!$action) {
            return $this->respond(false, 'Action not found', null, ['code' => 404], 404);
        }

        $stmt = $this->conn->prepare("UPDATE actions SET status = ?, note = ? WHERE id = ?");
        $stmt->execute([$status, $note, $id]);

        return $this->respond(true, 'Action status updated successfully');
    }


    /** ✅ Get Action by ID */
    public function getById(int $id)
    {
        // 1️⃣ جلب بيانات الأكشن الأساسية
        $stmt = $this->conn->prepare("
        SELECT 
            a.*, 
            c.name AS category_name,
            t.name AS type_name,
            u.name AS assigned_user_name,
            u2.name AS created_by_name
        FROM actions a
        LEFT JOIN types t ON a.type_id = t.id
        LEFT JOIN type_categories c ON t.category_id = c.id
        LEFT JOIN users u ON a.assigned_user_id = u.id
        LEFT JOIN users u2 ON a.created_by = u2.id
        WHERE a.id = ?
    ");
        $stmt->execute([$id]);
        $action = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$action) {
            return $this->respond(false, 'Action not found', null, ['code' => 404], 404);
        }

        // 2️⃣ جلب كل الصور المرتبطة بالأكشن
        $stmtImages = $this->conn->prepare("
        SELECT image_path
        FROM action_images
        WHERE action_id = ?
        ORDER BY id ASC
    ");
        $stmtImages->execute([$id]);
        $images = $stmtImages->fetchAll(PDO::FETCH_COLUMN);

        // 3️⃣ إضافة الصور للنتيجة
        $action['images'] = $images; // مصفوفة تحتوي كل الصور

        return $this->respond(true, 'Action retrieved successfully', $action);
    }


    /** ✅ Get all Actions (filters + search + pagination) */
    public function getAll(array $filters = [], bool $export = false)
    {
        /* =========================
     * 1️⃣ تجهيز الفلاتر (نفس Statistics)
     * ========================= */
        $baseConditions = [];
        $params = [];

        if (!empty($filters['from_date'])) {
            $baseConditions[] = "a.created_at >= :from_date";
            $params[':from_date'] = $filters['from_date'] . " 00:00:00";
        }

        if (!empty($filters['to_date'])) {
            $baseConditions[] = "a.created_at <= :to_date";
            $params[':to_date'] = $filters['to_date'] . " 23:59:59";
        }

        if (!empty($filters['type_category_id'])) {
            $ids = (array) $filters['type_category_id'];
            $placeholders = [];
            foreach ($ids as $i => $id) {
                $key = ":type_id_$i";
                $placeholders[] = $key;
                $params[$key] = (int)$id;
            }
            $baseConditions[] = "tc.id IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['assigned_user_id'])) {
            $ids = (array) $filters['assigned_user_id'];
            $placeholders = [];
            foreach ($ids as $i => $id) {
                $key = ":assigned_user_$i";
                $placeholders[] = $key;
                $params[$key] = (int)$id;
            }
            $baseConditions[] = "a.assigned_user_id IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['manager_id'])) {
            $baseConditions[] = "u.manager_id = :manager_id";
            $params[':manager_id'] = (int)$filters['manager_id'];
        }

        if (!empty($filters['super_manager_id'])) {
            //     $baseConditions[] = "
            //     u.manager_id IN (
            //         SELECT id FROM users WHERE manager_id = :super_manager_id
            //     )
            // ";
            //     $params[':super_manager_id'] = (int)$filters['super_manager_id'];

            $baseConditions[] = "u.manager_id = :super_manager_id";
            $params[':super_manager_id'] = (int)$filters['super_manager_id'];
        }

        if (!empty($filters['incident_classfication'])) {
            $values = (array)$filters['incident_classfication'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":incident_class_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.incident_classfication IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['incident'])) {
            $values = (array)$filters['incident'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":incident_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.incident IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['environment'])) {
            $values = (array)$filters['environment'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":environment_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.environment IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['group'])) {
            $values = (array)$filters['group'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":group_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.`group` IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['department'])) {
            $values = (array)$filters['department'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":dept_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "u.department IN (" . implode(',', $placeholders) . ")";
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            if ($filters['status'] === 'overdue') {
                $baseConditions[] = "a.status = 'open' AND a.expiry_date < CURDATE()";
            } elseif ($filters['status'] === 'open') {
                $baseConditions[] = "a.status = 'open' AND a.expiry_date >= CURDATE()";
            } else {
                $baseConditions[] = "a.status = :status";
                $params[':status'] = $filters['status'];
            }
        }

        $baseWhere = $baseConditions
            ? " AND " . implode(" AND ", $baseConditions)
            : "";

        /* =========================
     * 2️⃣ Query الأكشنات
     * ========================= */
        /* =========================
         * 2️⃣ Dynamic Sorting
         * ========================= */
        $allowedSortColumns = [
            'id', 'status', 'description', 'action', 'group', 'start_date', 'expiry_date', 
            'visit_duration', 'priority', 'created_at',
            'type_name', 'assigned_user_name', 'assigned_user_group', 'created_by_name'
        ];

        $sortBy = 'a.created_at';
        if (isset($filters['sort_by']) && in_array($filters['sort_by'], $allowedSortColumns)) {
            $map = [
                'type_name' => 't.name',
                'assigned_user_name' => 'u.name',
                'assigned_user_group' => 'u.`group`',
                'created_by_name' => 'u2.name'
            ];
            $sortBy = $map[$filters['sort_by']] ?? 'a.' . $filters['sort_by'];
        }

        $sortOrder = 'DESC';
        if (isset($filters['sort_order']) && strtoupper($filters['sort_order']) === 'ASC') {
            $sortOrder = 'ASC';
        }

        $sql = "
        SELECT 
            a.id, a.status, a.description, a.action, a.`group`, a.start_date, a.expiry_date, visit_duration, description, priority,
            a.image, a.attachment, a.created_at,
            t.name AS type_name,
            u.name AS assigned_user_name,
            u.`group` AS assigned_user_group,
            u2.name AS created_by_name
        FROM actions a
        LEFT JOIN users u ON a.assigned_user_id = u.id
        LEFT JOIN users u2 ON a.created_by = u2.id
        LEFT JOIN types t ON a.type_id = t.id
        LEFT JOIN type_categories tc ON t.category_id = tc.id
        WHERE 1=1
        $baseWhere
        ORDER BY $sortBy $sortOrder
    ";

        if ($export) {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC); // 👈 للـ Excel / CSV
        }

        /* =========================
         * 3️⃣ Pagination & Read
         * ========================= */
        $countSql = "
            SELECT COUNT(*)
            FROM actions a
            LEFT JOIN users u ON a.assigned_user_id = u.id
            LEFT JOIN users u2 ON a.created_by = u2.id
            LEFT JOIN types t ON a.type_id = t.id
            LEFT JOIN type_categories tc ON t.category_id = tc.id
            WHERE 1=1
            $baseWhere
        ";
        $stmtCount = $this->conn->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 15;
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $offset = ($page - 1) * $limit;

        $sql .= " LIMIT $limit OFFSET $offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->respond(true, 'Actions retrieved successfully', [
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
            'actions' => $actions
        ]);
    }


    /**
     * ✅ تجيب الإجراءات التي أنشأها المستخدم (created_by)
     */
    public function getAllByME(int $userId, array $filters = [])
    {
        $query = "
        SELECT 
            a.id, a.description, a.action, a.expiry_date, a.image, a.attachment, a.status, a.created_at,
            t.name AS type_name,
            u.name AS assigned_user_name,
            u2.name AS created_by_name
        FROM actions a
        LEFT JOIN types t ON a.type_id = t.id
        LEFT JOIN users u ON a.assigned_user_id = u.id
        LEFT JOIN users u2 ON a.created_by = u2.id
        WHERE a.created_by = ?
    ";

        // أول قيمة لازم تكون userId
        $params = [$userId];

        // 🔍 البحث بالعنوان أو الوصف
        if (!empty($filters['search'])) {
            $query .= " AND (a.title LIKE ? OR a.description LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        // فلترة حسب النوع
        if (!empty($filters['type_id'])) {
            $query .= " AND a.type_id = ?";
            $params[] = (int) $filters['type_id'];
        }

        // فلترة حسب المستخدم المكلف
        if (!empty($filters['assigned_user_id'])) {
            $query .= " AND a.assigned_user_id = ?";
            $params[] = (int) $filters['assigned_user_id'];
        }

        if (!empty($filters['department'])) {
            $values = (array)$filters['department'];
            $placeholders = str_repeat('?,', count($values) - 1) . '?';
            $query .= " AND u.department IN ($placeholders)";
            $params = array_merge($params, $values);
        }

        // 1️⃣ جلب الإجمالي للمساعدة في الـ Pagination
        $countQuery = "SELECT COUNT(*) FROM actions a LEFT JOIN users u ON a.assigned_user_id = u.id WHERE a.created_by = ?";
        // نحتاج بناء نفس شروط البحث في الـ count query
        $countParams = [$userId];
        $countWhere = "";
        if (!empty($filters['search'])) {
            $countWhere .= " AND (a.action LIKE ? OR a.description LIKE ?)";
            $countParams[] = '%' . $filters['search'] . '%';
            $countParams[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['type_id'])) {
            $countWhere .= " AND a.type_id = ?";
            $countParams[] = (int) $filters['type_id'];
        }
        if (!empty($filters['assigned_user_id'])) {
            $countWhere .= " AND a.assigned_user_id = ?";
            $countParams[] = (int) $filters['assigned_user_id'];
        }

        if (!empty($filters['department'])) {
            $values = (array)$filters['department'];
            $placeholders = str_repeat('?,', count($values) - 1) . '?';
            $countWhere .= " AND u.department IN ($placeholders)";
            $countParams = array_merge($countParams, $values);
        }

        $stmtCount = $this->conn->prepare($countQuery . $countWhere);
        $stmtCount->execute($countParams);
        $total = (int)$stmtCount->fetchColumn();

        // Pagination & Sorting
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 15;
        $page = isset($filters['page']) ? (int) $filters['page'] : 1;
        $offset = ($page - 1) * $limit;

        $allowedSortColumns = [
            'id', 'status', 'description', 'action', 'expiry_date', 'created_at',
            'type_name', 'assigned_user_name', 'created_by_name'
        ];

        $sortBy = 'a.created_at';
        if (isset($filters['sort_by']) && in_array($filters['sort_by'], $allowedSortColumns)) {
            $map = [
                'type_name' => 't.name',
                'assigned_user_name' => 'u.name',
                'created_by_name' => 'u2.name'
            ];
            $sortBy = $map[$filters['sort_by']] ?? 'a.' . $filters['sort_by'];
        }

        $sortOrder = 'DESC';
        if (isset($filters['sort_order']) && strtoupper($filters['sort_order']) === 'ASC') {
            $sortOrder = 'ASC';
        }

        $query .= " ORDER BY $sortBy $sortOrder LIMIT $limit OFFSET $offset";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->respond(true, 'Actions retrieved successfully', [
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
            'actions' => $actions
        ]);
    }


    /**
     * ✅ تجيب الإجراءات المسندة للمستخدم (assigned_user_id)
     */
    public function getAssignedToMe(int $userId, array $filters = [])
    {
        /* =========================
     * 1️⃣ تجهيز الفلاتر (نفس Statistics)
     * ========================= */
        $baseConditions = [];
        $params = [];

        // شرط ثابت: الأكشن مسند للمستخدم
        $baseConditions[] = "a.assigned_user_id = :assigned_user_id";
        $params[':assigned_user_id'] = $userId;

        if (!empty($filters['from_date'])) {
            $baseConditions[] = "a.created_at >= :from_date";
            $params[':from_date'] = $filters['from_date'] . " 00:00:00";
        }

        if (!empty($filters['to_date'])) {
            $baseConditions[] = "a.created_at <= :to_date";
            $params[':to_date'] = $filters['to_date'] . " 23:59:59";
        }

        if (!empty($filters['type_category_id'])) {
            $ids = (array)$filters['type_category_id'];
            $placeholders = [];
            foreach ($ids as $i => $id) {
                $key = ":type_id_$i";
                $placeholders[] = $key;
                $params[$key] = (int)$id;
            }
            $baseConditions[] = "tc.id IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['incident_classfication'])) {
            $values = (array)$filters['incident_classfication'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":incident_class_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.incident_classfication IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['incident'])) {
            $values = (array)$filters['incident'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":incident_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.incident IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['environment'])) {
            $values = (array)$filters['environment'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":environment_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.environment IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['group'])) {
            $values = (array)$filters['group'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":group_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.`group` IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['department'])) {
            $values = (array)$filters['department'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":dept_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "u.department IN (" . implode(',', $placeholders) . ")";
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            if ($filters['status'] === 'overdue') {
                $baseConditions[] = "a.status = 'open' AND a.expiry_date < CURDATE()";
            } elseif ($filters['status'] === 'open') {
                $baseConditions[] = "a.status = 'open' AND a.expiry_date >= CURDATE()";
            } else {
                $baseConditions[] = "a.status = :status";
                $params[':status'] = $filters['status'];
            }
        }

        if (!empty($filters['search'])) {
            $baseConditions[] = "a.description LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $baseWhere = $baseConditions
            ? " AND " . implode(" AND ", $baseConditions)
            : "";

        /* =========================
     * 2️⃣ Query الأكشنات
     * ========================= */
        /* =========================
         * 2️⃣ Dynamic Sorting
         * ========================= */
        $allowedSortColumns = [
            'id', 'status', 'description', 'action', 'group', 'start_date', 'expiry_date', 
            'visit_duration', 'priority', 'created_at',
            'type_name', 'assigned_user_name', 'created_by_name'
        ];

        $sortBy = 'a.created_at';
        if (isset($filters['sort_by']) && in_array($filters['sort_by'], $allowedSortColumns)) {
            $map = [
                'type_name' => 't.name',
                'assigned_user_name' => 'u.name',
                'created_by_name' => 'u2.name'
            ];
            $sortBy = $map[$filters['sort_by']] ?? 'a.' . $filters['sort_by'];
        }

        $sortOrder = 'DESC';
        if (isset($filters['sort_order']) && strtoupper($filters['sort_order']) === 'ASC') {
            $sortOrder = 'ASC';
        }

        $sql = "
        SELECT 
            a.id, a.description, a.action, a.`group`, a.start_date, a.expiry_date,
            a.image, a.attachment, a.status, a.created_at,
            t.name AS type_name,
            u.name AS assigned_user_name,
            u2.name AS created_by_name
        FROM actions a
        LEFT JOIN users u ON a.assigned_user_id = u.id
        LEFT JOIN users u2 ON a.created_by = u2.id
        LEFT JOIN types t ON a.type_id = t.id
        LEFT JOIN type_categories tc ON t.category_id = tc.id
        WHERE 1=1
        $baseWhere
        ORDER BY $sortBy $sortOrder
    ";

        /* =========================
         * 3️⃣ Pagination & Read
         * ========================= */
        $countSql = "
            SELECT COUNT(*)
            FROM actions a
            LEFT JOIN users u ON a.assigned_user_id = u.id
            LEFT JOIN users u2 ON a.created_by = u2.id
            LEFT JOIN types t ON a.type_id = t.id
            LEFT JOIN type_categories tc ON t.category_id = tc.id
            WHERE 1=1
            $baseWhere
        ";
        $stmtCount = $this->conn->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 15;
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $offset = ($page - 1) * $limit;

        $sql .= " LIMIT $limit OFFSET $offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->respond(true, 'Assigned actions retrieved successfully', [
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
            'actions' => $actions
        ]);
    }

    /** ✅ Get Statistics about Actions */
    public function getStatistics(array $filters = [])
    {
        /* =========================
         * 1️⃣ تجهيز الفلاتر العامة
         * ========================= */
        $baseConditions = [];
        $params = [];

        // فلترة حسب تاريخ الإنشاء (من)
        if (!empty($filters['from_date'])) {
            $baseConditions[] = "a.created_at >= :from_date";
            $params[':from_date'] = $filters['from_date'] . " 00:00:00";
        }

        // فلترة حسب تاريخ الإنشاء (إلى)
        if (!empty($filters['to_date'])) {
            $baseConditions[] = "a.created_at <= :to_date";
            $params[':to_date'] = $filters['to_date'] . " 23:59:59";
        }

        // فلترة حسب تصنيف النوع
        if (!empty($filters['type_category_id'])) {
            $ids = (array) $filters['type_category_id'];
            $placeholders = [];
            foreach ($ids as $i => $id) {
                $key = ":type_id_$i";
                $placeholders[] = $key;
                $params[$key] = (int) $id;
            }
            $baseConditions[] = "tc.id IN (" . implode(',', $placeholders) . ")";
        }

        // فلترة حسب المستخدم المسند له الأكشن
        if (!empty($filters['assigned_user_id'])) {
            $ids = (array) $filters['assigned_user_id'];
            $placeholders = [];
            foreach ($ids as $i => $id) {
                $key = ":assigned_user_$i";
                $placeholders[] = $key;
                $params[$key] = (int) $id;
            }
            $baseConditions[] = "a.assigned_user_id IN (" . implode(',', $placeholders) . ")";
        }

        // ✅ فلترة حسب المدير (manager_id)
        if (!empty($filters['manager_id'])) {
            $baseConditions[] = "u.manager_id = :manager_id";
            $params[':manager_id'] = (int) $filters['manager_id'];
        }

        // ✅ فلترة حسب super_manager_id
        if (!empty($filters['super_manager_id'])) {
            // $baseConditions[] = "
            //     u.manager_id IN (
            //         SELECT id
            //         FROM users
            //         WHERE manager_id = :super_manager_id
            //     )
            // ";
            // $params[':super_manager_id'] = (int) $filters['super_manager_id'];

            $baseConditions[] = "u.manager_id = :super_manager_id";
            $params[':super_manager_id'] = (int) $filters['super_manager_id'];
        }


        // Incident classification
        if (!empty($filters['incident_classfication'])) {
            $values = (array) $filters['incident_classfication'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":incident_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.incident_classfication IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($filters['incident'])) {
            $values = (array) $filters['incident'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":incident_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.incident IN (" . implode(',', $placeholders) . ")";
        }

        // Environment
        if (!empty($filters['environment'])) {
            $values = (array) $filters['environment'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":environment_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.environment IN (" . implode(',', $placeholders) . ")";
        }

        // Group
        if (!empty($filters['group'])) {
            $values = (array) $filters['group'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":group_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "a.`group` IN (" . implode(',', $placeholders) . ")";
        }

        // Department
        if (!empty($filters['department'])) {
            $values = (array) $filters['department'];
            $placeholders = [];
            foreach ($values as $i => $v) {
                $key = ":dept_$i";
                $placeholders[] = $key;
                $params[$key] = $v;
            }
            $baseConditions[] = "u.department IN (" . implode(',', $placeholders) . ")";
        }

        $baseWhere = $baseConditions
            ? " AND " . implode(" AND ", $baseConditions)
            : "";

        /* =========================
         * 2️⃣ Total Actions
         * ========================= */
        $totalSql = "
        SELECT COUNT(*)
        FROM actions a
        LEFT JOIN users u ON a.assigned_user_id = u.id
        LEFT JOIN types t ON a.type_id = t.id
        LEFT JOIN type_categories tc ON t.category_id = tc.id
        WHERE 1=1
        $baseWhere
    ";
        $stmt = $this->conn->prepare($totalSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        /* =========================
         * 3️⃣ Open Actions
         * ========================= */
        $openSql = "
        SELECT COUNT(*)
        FROM actions a
        LEFT JOIN users u ON a.assigned_user_id = u.id
        LEFT JOIN types t ON a.type_id = t.id
        LEFT JOIN type_categories tc ON t.category_id = tc.id
        WHERE a.status = 'open'
          AND a.expiry_date >= NOW()
        $baseWhere
    ";
        $stmt = $this->conn->prepare($openSql);
        $stmt->execute($params);
        $openCount = (int) $stmt->fetchColumn();

        /* =========================
         * 4️⃣ Closed Actions
         * ========================= */
        $closedSql = "
        SELECT COUNT(*)
        FROM actions a
        LEFT JOIN users u ON a.assigned_user_id = u.id
        LEFT JOIN types t ON a.type_id = t.id
        LEFT JOIN type_categories tc ON t.category_id = tc.id
        WHERE a.status = 'closed'
        $baseWhere
    ";
        $stmt = $this->conn->prepare($closedSql);
        $stmt->execute($params);
        $closedCount = (int) $stmt->fetchColumn();

        /* =========================
         * 5️⃣ Overdue Actions
         * ========================= */
        $overdueSql = "
        SELECT COUNT(*)
        FROM actions a
        LEFT JOIN users u ON a.assigned_user_id = u.id
        LEFT JOIN types t ON a.type_id = t.id
        LEFT JOIN type_categories tc ON t.category_id = tc.id
        WHERE a.status = 'open'
          AND a.expiry_date < CURDATE()
        $baseWhere
    ";
        $stmt = $this->conn->prepare($overdueSql);
        $stmt->execute($params);
        $overdueCount = (int) $stmt->fetchColumn();

        /* =========================
         * 6️⃣ Actions by Type
         * ========================= */
        $typeSql = "
        SELECT 
            t.name AS type_name,
            COUNT(a.id) AS action_count
        FROM types t
        LEFT JOIN actions a ON a.type_id = t.id
        LEFT JOIN users u ON a.assigned_user_id = u.id
        LEFT JOIN type_categories tc ON t.category_id = tc.id
        WHERE 1=1
        $baseWhere
        GROUP BY t.id, t.name
        ORDER BY t.name ASC
    ";
        $stmt = $this->conn->prepare($typeSql);
        $stmt->execute($params);
        $typeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* =========================
         * 7️⃣ Response
         * ========================= */
        return $this->respond(true, 'Statistics retrieved successfully', [
            'total_actions' => $total,
            'open_actions' => $openCount,
            'closed_actions' => $closedCount,
            'override_actions' => $overdueCount,
            'actions_by_type' => $typeStats
        ]);
    }


    /** 🔹 Private: Upload helper */
    private function uploadFile(?array $file, array $allowedExtensions, string $targetDir)
    {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            throw new Exception("Invalid file type: .$ext");
        }

        // مسار جذر المشروع
        $rootDir = realpath(__DIR__ . '/../'); // عدّل حسب موقع الملف، إذا داخل controllers استخدم ../../
        if (!$rootDir) {
            throw new Exception("Cannot resolve project root directory");
        }

        $fullDir = $rootDir . '/' . $targetDir;

        if (!is_dir($fullDir)) {
            if (!mkdir($fullDir, 0777, true)) {
                throw new Exception("Failed to create directory: $fullDir");
            }
        }

        $fileName = uniqid() . '.' . $ext;
        $path = $fullDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new Exception("Failed to upload file");
        }

        // يرجع المسار النسبي من جذر المشروع
        return $targetDir . '/' . $fileName;
    }
}
