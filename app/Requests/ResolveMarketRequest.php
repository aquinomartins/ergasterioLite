<?php

declare(strict_types=1);

namespace App\Requests;

final class ResolveMarketRequest
{
    public function validate(array $input): array
    {
        $data = [
            'resolved_option_id' => (int) ($input['resolved_option_id'] ?? 0),
            'resolution_notes' => trim(strip_tags((string) ($input['resolution_notes'] ?? ''))),
        ];

        $errors = [];

        if ($data['resolved_option_id'] <= 0) {
            $errors['resolved_option_id'][] = 'Selecione a opção vencedora do mercado.';
        }

        if (mb_strlen($data['resolution_notes']) > 1500) {
            $errors['resolution_notes'][] = 'As observações devem ter no máximo 1500 caracteres.';
        }

        return [$errors, $data];
    }
}
