<?php
require_once __DIR__ . '/../core/ApiResponseTrait.php';

class GoodPracticeController
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    use ApiResponseTrait;

    /** ✅ Create new Good Practice */
    public function create(array $data, array $files = [])
    {
        /* =========================
         * 1️⃣ Validation
         * ========================= */
        foreach (['assigned_user_id', 'description'] as $field) {
            if (empty($data[$field])) {
                return $this->respond(false, "{$field} is required", null, ['field' => $field], 400);
            }
        }

        /* =========================
         * 2️⃣ Normalize Optional Fields
         * ========================= */
        $startDate = !empty($data['start_date']) ? $data['start_date'] : null;

        /* =========================
         * 3️⃣ Insert Good Practice
         * ========================= */
        $stmt = $this->conn->prepare("
            INSERT INTO good_practice (
                start_date, description, assigned_user_id, created_by
            ) VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $startDate,
            $data['description'],
            (int) $data['assigned_user_id'],
            (int) $data['created_by']
        ]);

        $goodPracticeId = (int) $this->conn->lastInsertId();

        /* =========================
         * 4️⃣ Upload Multiple Images
         * ========================= */
        if (!empty($files['images']) && is_array($files['images']['name'])) {
            $imageStmt = $this->conn->prepare("
                INSERT INTO good_practice_images (good_practice_id, image_path)
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
                    $imageStmt->execute([$goodPracticeId, $imagePath]);
                }
            }
        }

        /* =========================
         * 5️⃣ Response
         * ========================= */
        return $this->respond(true, 'Good practice created successfully', [
            'id' => $goodPracticeId,
            'description' => $data['description'],
            'assigned_user_id' => $data['assigned_user_id']
        ]);
    }

    /** ✅ Get Good Practice by ID */
    public function getById(int $id)
    {
        // 1️⃣ جلب بيانات الأكشن الأساسية
        $stmt = $this->conn->prepare("
            SELECT 
                gp.*, 
                u.name AS assigned_user_name,
                u2.name AS created_by_name
            FROM good_practice gp
            LEFT JOIN users u ON gp.assigned_user_id = u.id
            LEFT JOIN users u2 ON gp.created_by = u2.id
            WHERE gp.id = ?
        ");
        $stmt->execute([$id]);
        $goodPractice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$goodPractice) {
            return $this->respond(false, 'Good practice not found', null, ['code' => 404], 404);
        }

        // 2️⃣ جلب كل الصور المرتبطة بالأكشن
        $stmtImages = $this->conn->prepare("
            SELECT image_path
            FROM good_practice_images
            WHERE good_practice_id = ?
            ORDER BY id ASC
        ");
        $stmtImages->execute([$id]);
        $images = $stmtImages->fetchAll(PDO::FETCH_COLUMN);

        // 3️⃣ إضافة الصور للنتيجة
        $goodPractice['images'] = $images;

        return $this->respond(true, 'Good practice retrieved successfully', $goodPractice);
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
        $rootDir = realpath(__DIR__ . '/../'); 
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
