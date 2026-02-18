<?php

namespace App\Utilities;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * Security Utilities
 */
class Security {
    private static ?string $appKey = null;

    private static function getAppKey(): string {
        if (self::$appKey === null) {
            $config = require __DIR__ . '/../../app/config/env.php';
            self::$appKey = $config['APP_KEY'];
        }
        return self::$appKey;
    }

    public static function sanitize(string $str): string {
        return strip_tags(trim($str));
    }

    /**
     * CSRF Validation
     * Note: JWT in Authorization header inherently protects against CSRF.
     * This is a placeholder for session-based CSRF if needed.
     */
    public static function validateCSRF(): bool {
        // In a JWT-only system, the presence of a valid JWT in the header is enough.
        return self::getBearerToken() !== null;
    }

    public static function generateToken(array $payload): string {
        $key = self::getAppKey();
        $payload['iat'] = time();
        $payload['exp'] = time() + (60 * 60 * 24);
        return JWT::encode($payload, $key, 'HS256');
    }

    public static function decodeToken(string $token): ?array {
        try {
            $key = self::getAppKey();
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function getBearerToken(): ?string {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    public static function authenticate(): ?array {
        $token = self::getBearerToken();
        if (!$token) return null;
        return self::decodeToken($token);
    }
}
