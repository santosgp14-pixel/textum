<?php
/**
 * TEXTUM - Router simple
 * Mapea GET/POST ?page=... a controladores
 */
class Router {
    private array $routes = [];

    public function add(string $method, string $page, callable $handler): void {
        $this->routes[strtoupper($method)][$page] = $handler;
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $page   = $_GET['page'] ?? 'dashboard';

        if (isset($this->routes[$method][$page])) {
            call_user_func($this->routes[$method][$page]);
        } else {
            // Fallback: intentar GET si existe
            if ($method === 'POST' && isset($this->routes['GET'][$page])) {
                call_user_func($this->routes['GET'][$page]);
            } else {
                http_response_code(404);
                require VIEW_PATH . '/errors/404.php';
            }
        }
    }
}
