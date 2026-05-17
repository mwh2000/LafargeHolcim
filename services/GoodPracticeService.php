<?php
require_once __DIR__ . '/../controllers/GoodPracticeController.php';
require_once __DIR__ . '/../controllers/notificationsController.php';
require_once __DIR__ . '/../controllers/emailController.php';

class GoodPracticeService
{
    private $goodPracticeController;
    private $notificationController;
    private $emailController;

    public function __construct($conn)
    {
        $this->goodPracticeController = new GoodPracticeController($conn);
        $this->notificationController = new NotificationController($conn);
        $this->emailController = new EmailController($conn);
    }

    public function createWithNotifications($data, $files, $senderId)
    {
        $res = $this->goodPracticeController->create($data, $files);

        if (!$res['success']) {
            return $res;
        }

        $goodPracticeId = $res['data']['id'];
        $assignedUserId = $res['data']['assigned_user_id'];
        $description = $res['data']['description'];

        /* 🔔 Notification */
        $noti = $this->notificationController->sendNotification(
            "New Good Practice Assigned",
            "A new good practice has been recorded and assigned to you.",
            [$assignedUserId],
            BASE_URL . "/public/good_practice.php?id=$goodPracticeId",
            $senderId
        );

        /* ✉️ Email */
        $mail = $this->emailController->sendEmail(
            "New Good Practice Assigned",
            "A new good practice has been assigned to you. Please check the following link: " . BASE_URL . "/public/good_practice.php?id=$goodPracticeId",
            [$assignedUserId],
            null,
            true
        );

        return [
            'success' => true,
            'good_practice' => $res,
            'notification' => $noti,
            'email' => $mail
        ];
    }
}
