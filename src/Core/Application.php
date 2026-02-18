<?php

namespace App\Core;

/**
 * Single Entry Point
 *
 * Initializes the Router and Database Connection.
 */
class Application {
    public Router $router;

    public function __construct() {
        $this->router = new Router();
    }

    public function run(): void {
        $this->registerRoutes();
        $this->router->resolve($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    }

    private function registerRoutes(): void {
        $this->router->add('POST', '/api/auth/login', [\App\Controllers\AuthController::class, 'login']);
        $this->router->add('POST', '/api/auth/register', [\App\Controllers\AuthController::class, 'register']);
        $this->router->add('GET', '/api/conversations', [\App\Controllers\ChatController::class, 'getConversations']);
        $this->router->add('GET', '/api/chat/{id}/history', [\App\Controllers\ChatController::class, 'getHistory']);
        $this->router->add('POST', '/api/chat/{id}/send', [\App\Controllers\ChatController::class, 'sendMessage']);
        $this->router->add('POST', '/api/sync/heartbeat', [\App\Controllers\ChatController::class, 'heartbeat']);
    }
}
