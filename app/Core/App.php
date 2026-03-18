<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    private Router $router;

    public function __construct(private readonly array $config)
    {
        View::share('app', $this->config['app']);
        $this->router = new Router();
    }

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->router->get($path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->router->post($path, $handler, $middleware);
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $this->router->dispatch($method, $uri);
    }
}
