<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    private static array $shared = [];

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $view, array $data = [], string $layout = 'layouts.main'): void
    {
        $viewPath = self::resolve($view);
        $layoutPath = self::resolve($layout);
        $data = array_merge(self::$shared, $data, [
            'currentUser' => Auth::user(),
            'flash' => Session::consumeFlash(),
        ]);

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require $layoutPath;
    }

    public static function partial(string $view, array $data = []): void
    {
        $viewPath = self::resolve($view);
        extract(array_merge(self::$shared, $data), EXTR_SKIP);
        require $viewPath;
    }

    private static function resolve(string $view): string
    {
        $path = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (! is_file($path)) {
            throw new RuntimeException('View não encontrada: ' . $view);
        }

        return $path;
    }
}
