<?php

declare(strict_types=1);

namespace App\Requests;

final class ResolveMarketRequest
{
    public function validate(array $input): array
    {
        $data = [
            'resolved_option_id' => (int) ($input['resolved_option_id'] ?? 0),
        ];

        $errors = [];

        if ($data['resolved_option_id'] <= 0) {
            $errors['resolved_option_id'][] = 'Selecione a opção vencedora do mercado.';
        }

        return [$errors, $data];
    }
}
