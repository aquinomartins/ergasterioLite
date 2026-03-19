<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . '/public' . $path;
$uploadsPrefix = '/storage/uploads/';

if ($path !== '/' && is_file($file)) {
    return false;
}

if (strncmp($path, $uploadsPrefix, strlen($uploadsPrefix)) === 0) {
    $uploadsDirectory = realpath(__DIR__ . '/storage/uploads');
    $relativePath = substr($path, strlen($uploadsPrefix));
    $requestedFile = $relativePath !== false ? basename($relativePath) : '';

    if ($uploadsDirectory !== false && $requestedFile === $relativePath && $requestedFile !== '') {
        $uploadPath = $uploadsDirectory . DIRECTORY_SEPARATOR . $requestedFile;
        $resolvedPath = realpath($uploadPath);

        if ($resolvedPath !== false && strncmp($resolvedPath, $uploadsDirectory . DIRECTORY_SEPARATOR, strlen($uploadsDirectory . DIRECTORY_SEPARATOR)) === 0 && is_file($resolvedPath)) {
            $mimeType = (string) mime_content_type($resolvedPath);

            if (in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                header('Content-Type: ' . $mimeType);
                header('Content-Length: ' . (string) filesize($resolvedPath));
                header('Cache-Control: public, max-age=86400');
                readfile($resolvedPath);
                return true;
            }
        }
    }

    http_response_code(404);
    echo 'Arquivo não encontrado.';
    return true;
}

require __DIR__ . '/public/index.php';
