<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, array|callable $handler, array $middleware = []): void
    {
        $this->routes[strtoupper($method)][rtrim($path, '/') ?: '/'] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';
        $method = strtoupper($method);
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            http_response_code(404);
            echo 'Página não encontrada.';
            return;
        }

        $this->runMiddleware($route['middleware']);

        $handler = $route['handler'];

        if (is_callable($handler)) {
            $handler();
            return;
        }

        [$controllerClass, $action] = $handler;

        if (! class_exists($controllerClass)) {
            throw new RuntimeException('Controller não encontrado: ' . $controllerClass);
        }

        $controller = new $controllerClass();

        if (! method_exists($controller, $action)) {
            throw new RuntimeException('Action não encontrada: ' . $action);
        }

        $controller->{$action}();
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
}
