<?php

namespace App\Core;

/**
 * Request Resolver
 *
 * Maps 'METHOD /uri' to [Controller::class, 'method'].
 */
class Router {
    private array $routes = [];

    public function add(string $method, string $route, array $handler): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'route'  => $route,
            'handler' => $handler
        ];
    }

    public function resolve(string $method, string $uri): void {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH);

        foreach ($this->routes as $route) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['route']);
            $pattern = "#^" . $pattern . "$#";

            if ($route['method'] === $method && preg_match($pattern, $path, $matches)) {
                [$controllerClass, $action] = $route['handler'];

                $controller = new $controllerClass();

                $params = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);

                call_user_func_array([$controller, $action], [$params]);
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Route not found']);
    }
}
