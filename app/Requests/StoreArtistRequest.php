<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;

final class StoreArtistRequest
{
    public function validate(array $input): array
    {
        $data = [
            'display_name' => trim(strip_tags((string) ($input['display_name'] ?? ''))),
            'biography' => trim(strip_tags((string) ($input['biography'] ?? ''))),
        ];

        $validator = new Validator();
        $validator->validate($data, [
            'display_name' => ['required', 'min:3', 'max:120'],
            'biography' => ['required', 'min:10', 'max:2000'],
        ]);

        return [$validator->errors(), $data];
    }
}
