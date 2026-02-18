<?php

namespace App\Controllers;

use App\Core\Database;
use App\Utilities\Security;
use PDO;
use Exception;

/**
 * AuthController
 *
 * Handles user registration and authentication.
 */
class AuthController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * POST /api/auth/register
     */
    public function register(): void {
        $input = json_decode(file_get_contents('php://input'), true);

        $username = Security::sanitize($input['username'] ?? '');
        $email = Security::sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        $uuid = bin2hex(random_bytes(16)); // Simple UUID simulation or use a lib

        try {
            $stmt = $this->db->prepare("INSERT INTO users (uuid, username, email, password_hash) VALUES (?, ?, ?, ?)");
            $stmt->execute([$uuid, $username, $email, $passwordHash]);

            echo json_encode(['success' => true, 'message' => 'User registered successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void {
        $input = json_decode(file_get_contents('php://input'), true);

        $email = Security::sanitize($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT id, uuid, username, password_hash, avatar_url FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $token = Security::generateToken([
                    'user_id' => $user['id'],
                    'uuid' => $user['uuid'],
                    'username' => $user['username']
                ]);

                echo json_encode([
                    'token' => $token,
                    'user' => [
                        'id' => $user['uuid'],
                        'username' => $user['username'],
                        'avatar' => $user['avatar_url']
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Login failed']);
        }
    }
}
