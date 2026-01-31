<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../config/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController
{
    private $conn;

    // 🔹 نمرر الاتصال بالـ constructor
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function sendEmail(
        string $subject,
        string $body,
        array $target_user_ids = [],
        ?int $target_type = null,
        bool $include_managers = false
    ) {
        if (empty($subject)) {
            return ['success' => false, 'message' => 'Subject is required'];
        }

        $emails = [];
        $userIds = [];

        /* ================= USERS ================= */
        if (empty($target_user_ids)) {

            if (!$target_type) {
                return ['success' => false, 'message' => 'Either target_user_ids or target_type must be provided'];
            }

            $stmt = $this->conn->prepare("
                SELECT id, email
                FROM users
                WHERE role_id = ?
                  AND email IS NOT NULL
            ");
            $stmt->execute([$target_type]);

            $users   = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $userIds = array_column($users, 'id');
            $emails  = array_column($users, 'email');
        } else {

            $in = implode(',', array_fill(0, count($target_user_ids), '?'));

            $stmt = $this->conn->prepare("
                SELECT id, email
                FROM users
                WHERE id IN ($in)
                  AND email IS NOT NULL
            ");
            $stmt->execute($target_user_ids);

            $users   = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $userIds = array_column($users, 'id');
            $emails  = array_column($users, 'email');
        }

        if (empty($emails)) {
            return ['success' => false, 'message' => 'No valid recipients found'];
        }

        /* ================= MANAGERS ================= */
        if ($include_managers && !empty($userIds)) {

            $in = implode(',', array_fill(0, count($userIds), '?'));

            $stmt = $this->conn->prepare("
                SELECT DISTINCT m.email
                FROM users u
                JOIN users m ON u.manager_id = m.id
                WHERE u.id IN ($in)
                  AND m.email IS NOT NULL
            ");
            $stmt->execute($userIds);

            $managerEmails = array_column(
                $stmt->fetchAll(PDO::FETCH_ASSOC),
                'email'
            );

            $emails = array_unique(array_merge($emails, $managerEmails));
        }

        /* ================= MAIL ================= */
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // يمكنك تغييره إلى SMTP استضافتك
            $mail->SMTPAuth = true;
            $mail->Username = 'rano12ran67@gmail.com';  // ايميل الإرسال
            $mail->Password = 'vsjf ngxb cezp amfr';    // App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->CharSet = 'UTF-8'; // ✅ مهم جداً للغة العربية
            $mail->Encoding = 'base64'; // يساعد على عرض النصوص العربية بشكل صحيح

            $mail->setFrom('rano12ran67@gmail.com', 'KCML');

            foreach ($emails as $email) {
                $mail->addAddress($email);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'recipients' => $emails
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $mail->ErrorInfo
            ];
        }
    }
}
