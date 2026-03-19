<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Router
{
    private array $routes = [];

    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, $handler, array $middleware = []): void
    {
        $normalizedPath = rtrim($path, '/') ?: '/';

        $this->routes[strtoupper($method)][] = [
            'path' => $normalizedPath,
            'handler' => $handler,
            'middleware' => $middleware,
            'pattern' => $this->compilePattern($normalizedPath),
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        $method = strtoupper($method);
        $route = $this->match($method, $path);

        if ($route === null) {
            http_response_code(404);
            echo 'Página não encontrada.';
            return;
        }

        $this->runMiddleware($route['middleware']);

        $handler = $route['handler'];

        if ($handler instanceof \Closure) {
            $handler(...$route['parameters']);
            return;
        }

        if (! is_array($handler) || count($handler) !== 2) {
            throw new RuntimeException('Handler de rota inválido.');
        }

        [$controllerClass, $action] = $handler;

        if (! class_exists($controllerClass)) {
            throw new RuntimeException('Controller não encontrado: ' . $controllerClass);
        }

        $controller = new $controllerClass();

        if (! method_exists($controller, $action)) {
            throw new RuntimeException('Action não encontrada: ' . $action);
        }

        $controller->{$action}(...$route['parameters']);
    }

    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $item) {
            if ($item === 'auth' && ! Auth::check()) {
                Session::flash('error', 'Faça login para acessar esta área.');
                Controller::redirect('/login');
            }

            if ($item === 'guest' && Auth::check()) {
                Controller::redirect('/dashboard');
            }
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ! Csrf::verifyFromRequest()) {
            http_response_code(419);
            exit('CSRF token inválido.');
        }
    }

    private function compilePattern(string $path): string
    {
        if ($path === '/') {
            return '#^/$#';
        }

        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
            $path
        );

        return '#^' . $pattern . '$#';
    }

    private function match(string $method, string $path): ?array
    {
        foreach ($this->routes[$method] ?? [] as $route) {
            if (! preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $parameters = [];

            foreach ($matches as $key => $value) {
                if (! is_int($key)) {
                    $parameters[] = $value;
                }
            }

            $route['parameters'] = $parameters;

            return $route;
        }

        return null;
    }
}
