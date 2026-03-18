<?php

namespace App\Http\Requests\Artworks;

use App\Domain\Artworks\Models\Artwork;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Artwork::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'artist_id' => ['required', 'exists:artists,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:artworks,slug'],
            'description' => ['nullable', 'string'],
            'artwork_type' => ['required', 'string', 'max:100'],
            'medium' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
