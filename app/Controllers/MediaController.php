<?php

declare(strict_types=1);

namespace App\Controllers;

final class MediaController
{
    private const UPLOAD_DIRECTORY = BASE_PATH . '/storage/uploads';
    private const ALLOWED_EXTENSIONS = ['jpg', 'png'];

    public function artwork(string $file): void
    {
        $filename = basename($file);

        if ($filename !== $file || ! $this->isAllowedFilename($filename)) {
            $this->respondNotFound();
            return;
        }

        $path = self::UPLOAD_DIRECTORY . '/' . $filename;

        if (! is_file($path)) {
            $this->respondNotFound();
            return;
        }

        $mimeType = (string) mime_content_type($path);

        if (! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            $this->respondNotFound();
            return;
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: public, max-age=86400');
        readfile($path);
    }

    private function isAllowedFilename(string $filename): bool
    {
        if (! preg_match('/^[a-zA-Z0-9_-]+\.(jpg|png)$/', $filename, $matches)) {
            return false;
        }

        return in_array(strtolower($matches[1]), self::ALLOWED_EXTENSIONS, true);
    }

    private function respondNotFound(): void
    {
        http_response_code(404);
        echo 'Arquivo não encontrado.';
    }
}
