<?php

/**
 * Router.php - Router Ringan & Fleksibel untuk Unduhanku
 */

namespace App;

class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    private function addRoute(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Dinamis hapus base path (misal: /unduhanku/public atau /zuzu/public)
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = str_replace('\\', '/', $scriptDir);

        if ($scriptDir !== '/' && !empty($scriptDir) && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            $isMatchMethod = ($route['method'] === $method) || ($method === 'HEAD' && $route['method'] === 'GET');
            if (!$isMatchMethod) {
                continue;
            }

            $regex = $this->patternToRegex($route['pattern']);

            if (preg_match($regex, $uri, $matches)) {
                array_shift($matches);
                call_user_func_array($route['handler'], $matches);
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => "Route '{$method} {$uri}' tidak ditemukan",
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function patternToRegex(string $pattern): string
    {
        $regex = preg_replace('/\{id\}/', '([0-9]+)', $pattern);
        $regex = preg_replace('/\{[a-zA-Z0-9_-]+\}/', '([a-zA-Z0-9_\.-]+)', $regex);
        return '#^' . $regex . '$#';
    }
}
