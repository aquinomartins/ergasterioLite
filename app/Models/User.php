<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    public ?int $id;
    public string $email;
    public string $passwordHash;
    public string $status;
    public ?string $createdAt;
    public ?string $updatedAt;

    public function __construct(
        ?int $id,
        string $email,
        string $passwordHash,
        string $status,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}
