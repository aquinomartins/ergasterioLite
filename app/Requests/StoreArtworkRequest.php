<?php

declare(strict_types=1);

namespace App\Requests;

use App\Core\Validator;

final class StoreArtworkRequest
{
    public function validate(array $input, array $files): array
    {
        $data = [
            'artist_id' => (int) ($input['artist_id'] ?? 0),
            'title' => trim(strip_tags((string) ($input['title'] ?? ''))),
            'description' => trim(strip_tags((string) ($input['description'] ?? ''))),
        ];

        $validator = new Validator();
        $validator->validate($data, [
            'artist_id' => ['required'],
            'title' => ['required', 'min:2', 'max:160'],
            'description' => ['required', 'min:10', 'max:3000'],
        ]);

        if ($data['artist_id'] <= 0) {
            $errors = $validator->errors();
            $errors['artist_id'][] = 'Selecione um artista válido.';

            return [$errors, $data];
        }

        $image = $files['image'] ?? null;

        if (! is_array($image) || (int) ($image['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors = $validator->errors();
            $errors['image'][] = 'Envie uma imagem da obra.';

            return [$errors, $data];
        }

        return [$validator->errors(), $data];
    }
}
