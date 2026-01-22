<?php
require_once __DIR__ . '/../controllers/notificationsController.php';

$config = require __DIR__ . '/../config/config.php';

session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$database = new Database($config['db']);
$conn = $database->getConnection();
$controller = new NotificationController($conn);

file_put_contents("log.txt", "Session ID: " . ($_COOKIE['user_id'] ?? 'none') . "\n", FILE_APPEND);


//get user notifications
if ($action === 'get_notifications' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_COOKIE['user_id'])) {
        $user_id = $_COOKIE['user_id'];
        $is_opened = null;
        $day = $_GET['day'] ?? null;
        $notifications = $controller->getUserNotifications($user_id, $is_opened, $day);
        echo json_encode(['success' => true, 'notifications' => $notifications]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    }
}

// getUserNotificationsCount
if ($action === 'get_notifications_count' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_COOKIE['user_id'])) {
        $user_id = $_COOKIE['user_id'];
        $is_opened = $_GET['is_opened'] ?? null;
        if ($is_opened !== null) {
            $is_opened = $is_opened == '1' ? 1 : 0;
        }
        $count = $controller->getUserNotificationsCount($user_id, $is_opened);
        echo json_encode(['success' => true, 'count' => $count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    }
}
// mark notification as opened
if ($action === 'mark_as_opened' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $notification_id = $input['notification_id'];

    if (!empty($notification_id)) {
        $result = $controller->markAsOpened($notification_id);
        if ($result) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark as opened']);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
        exit;
    }
}

// echo json_encode(['success' => false, 'message' => 'Invalid request']);
