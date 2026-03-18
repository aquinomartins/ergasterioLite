<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;

final class RegisterRequest
{
    public function validate(array $input): array
    {
        $data = [
            'email' => filter_var(trim((string) ($input['email'] ?? '')), FILTER_SANITIZE_EMAIL),
            'password' => trim((string) ($input['password'] ?? '')),
            'password_confirmation' => trim((string) ($input['password_confirmation'] ?? '')),
            'display_name' => trim((string) ($input['display_name'] ?? '')),
            'username' => strtolower(trim((string) ($input['username'] ?? ''))),
        ];

        $validator = new Validator();
        $validator->validate($data, [
            'email' => ['required', 'email', 'max:120'],
            'password' => ['required', 'min:8', 'max:255', 'confirmed'],
            'display_name' => ['required', 'min:3', 'max:120'],
            'username' => ['required', 'min:3', 'max:40', 'alphaNumDash'],
        ]);

        return [$validator->errors(), $data];
    }
}
