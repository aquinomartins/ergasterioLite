<?php

namespace App\Http\Requests\Markets;

use App\Domain\Markets\Models\Market;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $market = $this->route('market');

        return $market instanceof Market && ($this->user()?->can('update', $market) ?? false);
    }

    public function rules(): array
    {
        /** @var Market $market */
        $market = $this->route('market');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('markets', 'slug')->ignore($market->id)],
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
