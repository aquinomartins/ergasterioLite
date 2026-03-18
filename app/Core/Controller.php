<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'layouts.main'): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirectTo(string $path): void
    {
        self::redirect($path);
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}
