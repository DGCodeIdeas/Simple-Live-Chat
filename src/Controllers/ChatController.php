<?php

namespace App\Controllers;

use App\Core\Database;
use App\Utilities\Security;
use PDO;
use Exception;

/**
 * ChatController
 *
 * Handles logic for message retrieval (long-poll) and submission.
 * Implements the "Burst-Pulse" System.
 */
class ChatController {
    private PDO $db;
    private string $lockFile;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->lockFile = __DIR__ . '/../../storage/chat_lock.meta';

        // Ensure lockfile exists
        if (!file_exists($this->lockFile)) {
            touch($this->lockFile);
        }
    }

    /**
     * GET /api/conversations
     */
    public function getConversations(): void {
        $userData = Security::authenticate();
        if (!$userData) {
            http_response_code(401);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT c.id, c.title, c.type,
                (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_microtime DESC LIMIT 1) as last_message,
                (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND id > p.last_read_message_id) as unread_count
                FROM conversations c
                JOIN participants p ON c.id = p.conversation_id
                WHERE p.user_id = ?
            ");
            $stmt->execute([$userData['user_id']]);
            $conversations = $stmt->fetchAll();

            echo json_encode($conversations);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * GET /api/chat/{id}/history
     */
    public function getHistory(array $params): void {
        $userData = Security::authenticate();
        if (!$userData) {
            http_response_code(401);
            return;
        }

        $conversationId = $params['id'];
        $beforeId = $_GET['before_id'] ?? PHP_INT_MAX;

        try {
            $stmt = $this->db->prepare("
                SELECT m.*, u.username, u.avatar_url
                FROM messages m
                JOIN users u ON m.user_id = u.id
                WHERE m.conversation_id = ? AND m.id < ?
                ORDER BY m.id DESC
                LIMIT 50
            ");
            $stmt->execute([$conversationId, $beforeId]);
            $messages = array_reverse($stmt->fetchAll());

            echo json_encode($messages);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/chat/{id}/send
     */
    public function sendMessage(array $params): void {
        $userData = Security::authenticate();
        if (!$userData) {
            http_response_code(401);
            return;
        }

        // Update last active status
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_active_at = NOW() WHERE id = ?");
            $stmt->execute([$userData['user_id']]);
        } catch (Exception $e) {
            // Silently fail for activity update
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $content = Security::sanitize($input['message'] ?? '');
        $conversationId = $params['id'];
        $microtime = microtime(true);

        if (empty($content)) {
            http_response_code(400);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO messages (conversation_id, user_id, content, created_microtime)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$conversationId, $userData['user_id'], $content, $microtime]);
            $messageId = $this->db->lastInsertId();

            // Trigger the "Burst" by touching the lockfile
            touch($this->lockFile);

            echo json_encode([
                'id' => $messageId,
                'temp_id' => $input['temp_id'] ?? null,
                'status' => 'sent'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/sync/heartbeat
     * THE CORE ENGINE. Polling endpoint.
     */
    public function heartbeat(): void {
        $userData = Security::authenticate();
        if (!$userData) {
            http_response_code(401);
            return;
        }

        // Update last active status
        try {
            $stmt = $this->db->prepare("UPDATE users SET last_active_at = NOW() WHERE id = ?");
            $stmt->execute([$userData['user_id']]);
        } catch (Exception $e) {}

        $input = json_decode(file_get_contents('php://input'), true);
        $lastKnownMicrotime = (float)($input['last_known_microtime'] ?? 0);
        $startTime = time();
        $timeout = 25; // Max execution time safety

        // Long-polling loop
        while (time() - $startTime < $timeout) {
            clearstatcache();
            $lastUpdate = filemtime($this->lockFile);

            if ($lastUpdate > ($input['last_update_check'] ?? 0) || $lastKnownMicrotime == 0) {
                // Check if there are actually new messages for the user's conversations
                $stmt = $this->db->prepare("
                    SELECT m.*, u.username, u.avatar_url, m.conversation_id
                    FROM messages m
                    JOIN users u ON m.user_id = u.id
                    JOIN participants p ON m.conversation_id = p.conversation_id
                    WHERE p.user_id = ? AND m.created_microtime > ?
                    ORDER BY m.created_microtime ASC
                ");
                $stmt->execute([$userData['user_id'], $lastKnownMicrotime]);
                $messages = $stmt->fetchAll();

                if (!empty($messages)) {
                    echo json_encode([
                        'messages' => $messages,
                        'last_update_check' => time()
                    ]);
                    return;
                }
            }

            usleep(500000); // Sleep for 0.5s to reduce CPU load
        }

        // If loop ends without new messages
        http_response_code(204);
    }
}
