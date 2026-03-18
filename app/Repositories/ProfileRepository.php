<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Profile;
use PDO;

final class ProfileRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    private function connection(): PDO
    {
        return $this->pdo ?? Database::connection();
    }

    public function create(Profile $profile): void
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO profiles (user_id, display_name, username, bio, created_at, updated_at)
             VALUES (:user_id, :display_name, :username, :bio, NOW(), NOW())'
        );
        $statement->execute([
            'user_id' => $profile->userId,
            'display_name' => $profile->displayName,
            'username' => $profile->username,
            'bio' => $profile->bio,
        ]);
    }

    public function findByUserId(int $userId): ?array
    {
        $statement = $this->connection()->prepare('SELECT * FROM profiles WHERE user_id = :user_id LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $profile = $statement->fetch();

        return $profile ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->connection()->prepare('SELECT * FROM profiles WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $profile = $statement->fetch();

        return $profile ?: null;
    }

    public function updateByUserId(int $userId, Profile $profile): void
    {
        $statement = $this->connection()->prepare(
            'UPDATE profiles
             SET display_name = :display_name,
                 username = :username,
                 bio = :bio,
                 updated_at = NOW()
             WHERE user_id = :user_id'
        );
        $statement->execute([
            'display_name' => $profile->displayName,
            'username' => $profile->username,
            'bio' => $profile->bio,
            'user_id' => $userId,
        ]);
    }
}
