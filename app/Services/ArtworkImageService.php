<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ArtworkImageService
{
    public function store(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store('artworks', 'public');
    }

    public function replace(?string $currentPath, ?UploadedFile $file): ?string
    {
        if (! $file) {
            return $currentPath;
        }

        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
        }

        return $this->store($file);
    }
}
