<?php

declare(strict_types=1);

namespace App\Requests;

final class StorePositionRequest
{
    public function validate(array $input): array
    {
        $data = [
            'option_id' => (int) ($input['option_id'] ?? 0),
            'shares_amount' => (float) ($input['shares_amount'] ?? 0),
        ];

        $errors = [];

        if ($data['option_id'] <= 0) {
            $errors['option_id'][] = 'Selecione uma opção válida.';
        }

        if ($data['shares_amount'] <= 0) {
            $errors['shares_amount'][] = 'Informe uma quantidade maior que zero.';
        }

        return [$errors, $data];
    }
}
