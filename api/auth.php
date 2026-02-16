<?php
require_once '../config/headers.php';
require_once '../config/db.php';
require_once '../core/User.php';
require_once '../core/Sanitizer.php';

$db = DB::getInstance();
$userModel = new User($db);

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'register':
        $username = Sanitizer::raw($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $avatar = $_POST['avatar'] ?? null;

        if (strlen($username) < 3 || strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
            exit;
        }

        if ($userModel->register($username, $password, $avatar)) {
            $userModel->login($username, $password);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Username taken or error.']);
        }
        break;

    case 'login':
        $username = Sanitizer::raw($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = $userModel->login($username, $password);
        if ($user) {
            echo json_encode(['success' => true, 'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username']
            ]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
        }
        break;

    case 'check':
        $user = User::getCurrentUser();
        if ($user) {
            echo json_encode(['success' => true, 'user' => [
                'id' => (int)$user['id'],
                'username' => $user['username']
            ]]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'logout':
        if (isset($_SESSION['user_id'])) {
            $userModel->updateStatus($_SESSION['user_id'], 'offline');
            session_destroy();
        }
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
