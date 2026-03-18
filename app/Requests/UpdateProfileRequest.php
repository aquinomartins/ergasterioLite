<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;

final class UpdateProfileRequest
{
    public function validate(array $input): array
    {
        $data = [
            'display_name' => trim((string) ($input['display_name'] ?? '')),
            'username' => strtolower(trim((string) ($input['username'] ?? ''))),
            'bio' => trim(strip_tags((string) ($input['bio'] ?? ''))),
        ];

        $validator = new Validator();
        $validator->validate($data, [
            'display_name' => ['required', 'min:3', 'max:120'],
            'username' => ['required', 'min:3', 'max:40', 'alphaNumDash'],
            'bio' => ['max:500'],
        ]);

        return [$validator->errors(), $data];
    }
}
