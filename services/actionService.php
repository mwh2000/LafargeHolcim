<?php
class ActionService
{
    private $actionController;
    private $notificationController;
    private $emailController;

    public function __construct($conn)
    {
        $this->actionController = new ActionController($conn);
        $this->notificationController = new NotificationController($conn);
        $this->emailController = new EmailController($conn);
    }

    public function createWithNotifications($data, $files, $senderId)
    {
        $res = $this->actionController->create($data, $files);

        if (!$res['success']) {
            return $res;
        }

        $actionId = $res['data']['id'];
        $assignedUserId = $res['data']['assigned_user_id'];
        $actionTitle = $res['data']['action'];

        /* 🔔 Notification */
        $noti = $this->notificationController->sendNotification(
            "New Action Created",
            $actionTitle,
            [$assignedUserId],
            BASE_URL . "/public/action.php?id=$actionId",
            $senderId
        );

        /* ✉️ Email */
        $mail = $this->emailController->sendEmail(
            "New Action Assigned: $actionTitle",
            "A new action has been assigned to you. please chec the following link: " . BASE_URL . "/public/action.php?id=$actionId",
            [$assignedUserId],
            null,
            true
        );

        return [
            'success' => true,
            'action' => $res,
            'notification' => $noti,
            'email' => $mail
        ];
    }
}
