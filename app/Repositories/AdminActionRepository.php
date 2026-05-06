<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class AdminActionRepository
{
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    private function connection(): PDO
    {
        return $this->pdo ?? Database::connection();
    }

    public function create(int $adminUserId, string $actionType, string $targetType, int $targetId, ?string $justification): int
    {
        $statement = $this->connection()->prepare(
            'INSERT INTO admin_actions (
                admin_user_id, action_type, target_type, target_id, justification, created_at
             ) VALUES (
                :admin_user_id, :action_type, :target_type, :target_id, :justification, NOW()
             )'
        );

        $statement->execute([
            'admin_user_id' => $adminUserId,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'justification' => $justification,
        ]);

        return (int) $this->connection()->lastInsertId();
    }
}
