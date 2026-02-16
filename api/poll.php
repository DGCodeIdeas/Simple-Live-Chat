<?php
require_once '../config/headers.php';
require_once '../config/db.php';
require_once '../core/Message.php';
require_once '../core/User.php';

$user = User::getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = DB::getInstance();
$messageModel = new Message($db);
$userModel = new User($db);

$lastId = (int)($_POST['last_id'] ?? 0);
$startTime = time();
$timeout = 25; // seconds

// Update user activity status once per request to reduce DB stress
$userModel->updateStatus($user['id'], 'online');

$response = [];

while (time() - $startTime < $timeout) {
    $messages = $messageModel->getNew($lastId);

    if (!empty($messages)) {
        $response['messages'] = $messages;
        $response['online_users'] = $userModel->getOnlineUsers();
        $response['pings'] = $messageModel->countMentions($user['id'], $user['username']);
        break;
    }

    // Small sleep to prevent high CPU usage
    usleep(500000); // 0.5s
}

// If no new messages after timeout, still return online users and pings
if (empty($response)) {
    $response['messages'] = [];
    $response['online_users'] = $userModel->getOnlineUsers();
    $response['pings'] = $messageModel->countMentions($user['id'], $user['username']);
}

echo json_encode($response);
