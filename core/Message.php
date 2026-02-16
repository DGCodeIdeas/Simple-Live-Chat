<?php
/**
 * Message Class
 */

class Message {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function send($senderId, $body, $type = 'text') {
        $microtime = microtime(true);
        $stmt = $this->db->prepare("INSERT INTO messages (sender_id, body, created_at, type) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$senderId, $body, $microtime, $type])) {
            return [
                'id' => $this->db->lastInsertId(),
                'sender_id' => $senderId,
                'body' => $body,
                'created_at' => $microtime,
                'type' => $type
            ];
        }
        return false;
    }

    public function getNew($lastId) {
        $stmt = $this->db->prepare("
            SELECT m.*, u.username, u.avatar_data
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.id > ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$lastId]);
        return $stmt->fetchAll();
    }

    public function countMentions($userId, $username) {
        // Simple mention check: @username
        $pattern = '%@' . $username . '%';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE body LIKE ? AND id > (SELECT COALESCE(MAX(id), 0) - 50 FROM messages)");
        $stmt->execute([$pattern]);
        return $stmt->fetchColumn();
    }
}
