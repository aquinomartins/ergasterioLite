<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LiquidityGameRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO liquidity_games (id, title, invite_code, owner_user_id, status, mode, max_participants, max_rounds, current_round, created_at, updated_at)
             VALUES (:id, :title, :invite_code, :owner_user_id, :status, :mode, :max_participants, :max_rounds, :current_round, NOW(), NOW())'
        );
        $statement->execute([
            'id' => $data['id'],
            'title' => $data['title'],
            'invite_code' => $data['invite_code'],
            'owner_user_id' => $data['owner_user_id'],
            'status' => $data['status'] ?? 'waiting',
            'mode' => $data['mode'] ?? 'individual',
            'max_participants' => $data['max_participants'] ?? null,
            'max_rounds' => $data['max_rounds'] ?? 6,
            'current_round' => $data['current_round'] ?? 1,
        ]);
        return (int) $data['id'];
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM liquidity_games WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function findByInviteCode(string $code): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM liquidity_games WHERE invite_code = :code LIMIT 1');
        $statement->execute(['code' => strtoupper(trim($code))]);
        return $statement->fetch() ?: null;
    }

    public function inviteCodeExists(string $code): bool
    {
        return $this->findByInviteCode($code) !== null;
    }

    public function getOwnedByUser(int $userId): array
    {
        $statement = $this->pdo->prepare("
            SELECT lg.*,
                   (
                       SELECT COUNT(*)
                       FROM liquidity_participants lp
                       WHERE lp.game_id = lg.id AND lp.status = 'pending'
                   ) AS pending_participants_count,
                   (
                       SELECT COUNT(*)
                       FROM liquidity_participants lp
                       WHERE lp.game_id = lg.id AND lp.status = 'approved'
                   ) AS approved_participants_count,
                   (
                       SELECT COUNT(*)
                       FROM liquidity_teams lt
                       WHERE lt.game_id = lg.id OR (lt.game_id IS NULL AND lt.session_id = lg.id)
                   ) AS teams_count
            FROM liquidity_games lg
            WHERE lg.owner_user_id = :user_id
            ORDER BY lg.id DESC
        ");
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function getPublicArenas(): array
    {
        $statement = $this->pdo->query('SELECT * FROM liquidity_games ORDER BY id DESC LIMIT 50');
        return $statement->fetchAll();
    }
}
