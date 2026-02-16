<?php
require_once '../config/headers.php';
require_once '../config/db.php';
require_once '../core/Message.php';
require_once '../core/User.php';
require_once '../core/Sanitizer.php';

$user = User::getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$db = DB::getInstance();
$messageModel = new Message($db);

$body = $_POST['body'] ?? '';
$type = $_POST['type'] ?? 'text';

if (empty($body)) {
    echo json_encode(['success' => false, 'message' => 'Empty message']);
    exit;
}

$result = $messageModel->send($user['id'], $body, $type);

if ($result) {
    echo json_encode(['success' => true, 'message' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
