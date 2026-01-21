<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/config.php';

class NotificationController
{
    private $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    // use ApiResponseTrait;
    // Send and store notification
    public function sendNotification($title, $body, $target_user_ids = [], $url = '', $sender_id = null, $target_type = null, $include_manager_of_sender = false)
    {


        if (empty($title)) {
            return ['success' => false, 'message' => 'Title is required'];
        }

        // 🧩 تحديد المستلمين
        if (empty($target_user_ids)) {
            if (!$target_type) {
                return ['success' => false, 'message' => 'Either target_user_ids or target_type must be provided'];
            }

            if ($target_type === 'requester') {
                return ['success' => false, 'message' => 'For requester, you must provide target_user_ids'];
            }

            $stmt = $this->conn->prepare("SELECT id FROM users WHERE type = ?");
            $stmt->execute([$target_type]);
            $target_user_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');

            if (empty($target_user_ids)) {
                return ['success' => false, 'message' => "No users found for type: $target_type"];
            }
        }

        $created_at = date('Y-m-d H:i:s');
        $insertStmt = $this->conn->prepare("
        INSERT INTO notifications (title, body, user_id, url, is_opened, created_at, sender_id)
        VALUES (?, ?, ?, ?, 0, ?, ?)
    ");

        // إرسال الإشعار إلى المستخدمين المحددين
        foreach ($target_user_ids as $user_id) {
            $insertStmt->execute([$title, $body, $user_id, $url, $created_at, $sender_id]);
        }

        // 🔹 إرسال إشعار إلى مدير المرسل إذا مفعّل الخيار
        $manager_sent = false;
        if ($include_manager_of_sender && !empty($sender_id)) {
            $managerStmt = $this->conn->prepare("SELECT manager_id FROM users WHERE id = ? LIMIT 1");
            $managerStmt->execute([$sender_id]);
            $manager_id = $managerStmt->fetchColumn();

            if (!empty($manager_id)) {
                $insertStmt->execute([$title, $body, $manager_id, $url, $created_at, $sender_id]);
                $manager_sent = true;
            }
        }

        // 🔹 جلب الرموز FCM (اختياري)
        // $in = str_repeat('?,', count($target_user_ids) - 1) . '?';
        // $tokenStmt = $this->conn->prepare("SELECT token FROM users WHERE id IN ($in) AND token IS NOT NULL");
        // $tokenStmt->execute($target_user_ids);
        // $tokens = array_column($tokenStmt->fetchAll(PDO::FETCH_ASSOC), 'token');

        // if (!empty($tokens)) self::sendToFirebase($tokens, $title, $body, $url);

        return [
            'success' => true,
            'message' => $manager_sent
                ? 'Notifications sent successfully (including sender manager).'
                : 'Notifications sent successfully (no manager found for sender).'
        ];
    }




    // Optional: mark as opened
    public function markAsOpened($notification_id)
    {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_opened = 1 WHERE id = ?");
        $stmt->execute([$notification_id]);

        // return success response
        return true;
    }

    // Optional: get user notifications
    public function getUserNotifications($user_id, $is_opened = null, $day = null)
    {
        // ✅ التوقيت المحلي
        date_default_timezone_set('Asia/Baghdad');

        $query = "
        SELECT n.*, u.name AS sender_name
        FROM notifications n
        LEFT JOIN users u ON n.sender_id = u.id
        WHERE n.user_id = ?
    ";

        $params = [$user_id];

        // ✅ فلترة حسب حالة الفتح (إذا انرسلت)
        if ($is_opened !== null) {
            $query .= " AND n.is_opened = ?";
            $params[] = $is_opened ? 1 : 0;
        }

        // ✅ فلترة حسب اليوم (إذا انرسل)
        if ($day !== null) {
            $query .= " AND DATE(n.created_at) = ?";
            $params[] = $day;
        }

        // ✅ الترتيب: غير المفتوحة أولاً ثم الأحدث
        $query .= " ORDER BY n.is_opened ASC, n.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



}
