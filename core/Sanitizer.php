<?php
/**
 * Sanitizer Class for Input Cleaning
 */

class Sanitizer {
    /**
     * Clean strings for HTML output
     */
    public static function clean($data) {
        if (is_array($data)) {
            return array_map([self::class, 'clean'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize for Database (though PDO handles this, good for extra safety)
     */
    public static function raw($data) {
        return trim($data);
    }
}
