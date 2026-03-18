<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;

final class LoginRequest
{
    public function validate(array $input): array
    {
        $data = [
            'email' => filter_var(trim((string) ($input['email'] ?? '')), FILTER_SANITIZE_EMAIL),
            'password' => trim((string) ($input['password'] ?? '')),
        ];

        $validator = new Validator();
        $validator->validate($data, [
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'min:8', 'max:255'],
        ]);

        return [$validator->errors(), $data];
    }
}
