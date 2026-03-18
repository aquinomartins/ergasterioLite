<?php

namespace App\Http\Requests\Artists;

use App\Domain\Artists\Models\Artist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArtistRequest extends FormRequest
{
    public function authorize(): bool
    {
        $artist = $this->route('artist');

        return $artist instanceof Artist && ($this->user()?->can('update', $artist) ?? false);
    }

    public function rules(): array
    {
        /** @var Artist $artist */
        $artist = $this->route('artist');

        return [
            'user_id' => ['nullable', 'exists:users,id'],
            'display_name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('artists', 'slug')->ignore($artist->id)],
            'biography' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
