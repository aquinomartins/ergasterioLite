<?php

namespace App\Http\Requests\Markets;

use App\Domain\Markets\Models\Market;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Market::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:markets,slug'],
            'description' => ['nullable', 'string'],
            'market_type' => ['required', Rule::in(['multiple_choice'])],
            'status' => ['required', Rule::in(['draft', 'open', 'closed', 'resolved'])],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['required', 'date', 'after:opens_at'],
            'resolution_mode' => ['required', Rule::in(['manual'])],
            'options' => ['required', 'array', 'min:2'],
            'options.*.label' => ['required', 'string', 'max:255'],
            'options.*.artwork_id' => ['nullable', 'exists:artworks,id'],
            'options.*.artist_id' => ['nullable', 'exists:artists,id'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
