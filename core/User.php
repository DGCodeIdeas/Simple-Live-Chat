<?php
/**
 * User Class
 */

class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function register($username, $password, $avatar = null) {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $stmt = $this->db->prepare("INSERT INTO users (username, password_hash, avatar_data) VALUES (?, ?, ?)");
        try {
            return $stmt->execute([$username, $hash, $avatar]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $this->updateStatus($user['id'], 'online');
            return $user;
        }
        return false;
    }

    public function updateStatus($userId, $status) {
        $stmt = $this->db->prepare("UPDATE users SET status = ?, last_activity = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$status, $userId]);
    }

    public function getOnlineUsers() {
        // Users active in the last 30 seconds
        $stmt = $this->db->prepare("SELECT id, username, status, avatar_data FROM users WHERE last_activity > (NOW() - INTERVAL 30 SECOND)");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username']
            ];
        }
        return null;
    }
}
