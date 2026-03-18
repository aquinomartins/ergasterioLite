<?php

namespace App\Http\Requests\Artworks;

use App\Domain\Artworks\Models\Artwork;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArtworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        $artwork = $this->route('artwork');

        return $artwork instanceof Artwork && ($this->user()?->can('update', $artwork) ?? false);
    }

    public function rules(): array
    {
        /** @var Artwork $artwork */
        $artwork = $this->route('artwork');

        return [
            'artist_id' => ['required', 'exists:artists,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('artworks', 'slug')->ignore($artwork->id)],
            'description' => ['nullable', 'string'],
            'artwork_type' => ['required', 'string', 'max:100'],
            'medium' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
