<?php

declare(strict_types=1);

namespace App\Requests;

use DateTimeImmutable;

final class StoreMarketRequest
{
    public function validate(array $input): array
    {
        $options = [];

        foreach ((array) ($input['options'] ?? []) as $index => $option) {
            if (! is_array($option)) {
                continue;
            }

            $options[] = [
                'label' => trim(strip_tags((string) ($option['label'] ?? ''))),
                'artwork_id' => $this->sanitizeId($option['artwork_id'] ?? null),
                'artist_id' => $this->sanitizeId($option['artist_id'] ?? null),
                'sort_order' => $index + 1,
            ];
        }

        $data = [
            'title' => trim(strip_tags((string) ($input['title'] ?? ''))),
            'description' => trim(strip_tags((string) ($input['description'] ?? ''))),
            'market_type' => trim(strip_tags((string) ($input['market_type'] ?? ''))),
            'resolution_mode' => trim(strip_tags((string) ($input['resolution_mode'] ?? 'manual'))),
            'closes_at' => trim((string) ($input['closes_at'] ?? '')),
            'options' => $options,
        ];

        $errors = [];

        if ($data['title'] === '') {
            $errors['title'][] = 'Informe um título para o mercado.';
        }

        if ($data['description'] === '') {
            $errors['description'][] = 'Informe uma descrição para o mercado.';
        }

        if (! in_array($data['market_type'], ['artwork_outcome', 'artist_outcome'], true)) {
            $errors['market_type'][] = 'Selecione um tipo de mercado válido.';
        }

        if ($data['closes_at'] === '') {
            $errors['closes_at'][] = 'Informe a data de fechamento do mercado.';
        } else {
            $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $data['closes_at'])
                ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['closes_at'])
                ?: DateTimeImmutable::createFromFormat('Y-m-d H:i', $data['closes_at']);

            if (! $date instanceof DateTimeImmutable) {
                $errors['closes_at'][] = 'Use uma data de fechamento válida.';
            } elseif ($date <= new DateTimeImmutable('now')) {
                $errors['closes_at'][] = 'A data de fechamento deve estar no futuro.';
            } else {
                $data['closes_at'] = $date->format('Y-m-d H:i:s');
            }
        }

        if (count($data['options']) < 2) {
            $errors['options'][] = 'Cadastre pelo menos duas opções.';
        }

        foreach ($data['options'] as $index => $option) {
            $position = $index + 1;

            if ($option['label'] === '') {
                $errors['options.' . $index . '.label'][] = 'Informe o rótulo da opção ' . $position . '.';
            }

            if ($data['market_type'] === 'artwork_outcome') {
                if (($option['artwork_id'] ?? null) === null) {
                    $errors['options.' . $index . '.artwork_id'][] = 'Associe a opção ' . $position . ' a uma obra.';
                }

                $data['options'][$index]['artist_id'] = null;
            }

            if ($data['market_type'] === 'artist_outcome') {
                if (($option['artist_id'] ?? null) === null) {
                    $errors['options.' . $index . '.artist_id'][] = 'Associe a opção ' . $position . ' a um artista.';
                }

                $data['options'][$index]['artwork_id'] = null;
            }
        }

        return [$errors, $data];
    }

    private function sanitizeId($value): ?int
    {
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
