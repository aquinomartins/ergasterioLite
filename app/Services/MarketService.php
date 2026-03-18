<?php

namespace App\Services;

use App\Domain\Markets\Models\Market;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MarketService
{
    public function create(array $data): Market
    {
        return DB::transaction(function () use ($data): Market {
            $options = Arr::pull($data, 'options', []);
            $this->validateOptions($options);

            $market = Market::query()->create([
                ...$data,
                'slug' => $data['slug'] ?? Str::slug($data['title']),
            ]);

            foreach (array_values($options) as $index => $option) {
                $market->options()->create([
                    'label' => $option['label'],
                    'artwork_id' => $option['artwork_id'] ?: null,
                    'artist_id' => $option['artist_id'] ?: null,
                    'sort_order' => $option['sort_order'] ?? $index + 1,
                ]);
            }

            return $market->load('options');
        });
    }

    public function update(Market $market, array $data): Market
    {
        return DB::transaction(function () use ($market, $data): Market {
            $options = Arr::pull($data, 'options', []);
            $this->validateOptions($options);

            $market->update([
                ...$data,
                'slug' => $data['slug'] ?? $market->slug,
            ]);

            $market->options()->delete();

            foreach (array_values($options) as $index => $option) {
                $market->options()->create([
                    'label' => $option['label'],
                    'artwork_id' => $option['artwork_id'] ?: null,
                    'artist_id' => $option['artist_id'] ?: null,
                    'sort_order' => $option['sort_order'] ?? $index + 1,
                ]);
            }

            return $market->load('options');
        });
    }

    private function validateOptions(array $options): void
    {
        $valid = array_values(array_filter($options, fn (array $option) => filled($option['label'] ?? null)));

        if (count($valid) < 2) {
            throw new InvalidArgumentException('O mercado deve conter no mínimo duas opções válidas.');
        }
    }
}
