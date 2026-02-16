<?php
require_once '../config/headers.php';
require_once '../config/db.php';
require_once '../core/User.php';

$user = User::getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['file'];
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF allowed.']);
        exit;
    }

    // Ensure uploads directory exists
    if (!is_dir('../uploads')) {
        mkdir('../uploads', 0755, true);
    }

    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $target = '../uploads/' . $newName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['success' => true, 'url' => 'uploads/' . $newName]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
}
