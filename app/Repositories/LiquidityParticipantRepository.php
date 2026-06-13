<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LiquidityParticipantRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function findByGameAndUser(int $gameId, int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM liquidity_participants WHERE game_id = :game_id AND user_id = :user_id LIMIT 1');
        $statement->execute(['game_id' => $gameId, 'user_id' => $userId]);
        return $statement->fetch() ?: null;
    }

    public function createPending(int $gameId, int $userId): int
    {
        $statement = $this->pdo->prepare('INSERT INTO liquidity_participants (game_id, user_id, role, status, joined_at) VALUES (:game_id, :user_id, \'player\', \'pending\', NOW())');
        $statement->execute(['game_id' => $gameId, 'user_id' => $userId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function countActiveRequests(int $gameId): int
    {
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM liquidity_participants WHERE game_id = :game_id AND status IN ('pending', 'approved')");
        $statement->execute(['game_id' => $gameId]);
        return (int) $statement->fetchColumn();
    }

    public function getByGame(int $gameId, ?string $status = null): array
    {
        $sql = 'SELECT lp.*, u.email, p.display_name, p.username, lt.name AS team_name FROM liquidity_participants lp INNER JOIN users u ON u.id = lp.user_id LEFT JOIN profiles p ON p.user_id = u.id LEFT JOIN liquidity_teams lt ON lt.id = lp.team_id WHERE lp.game_id = :game_id';
        $params = ['game_id' => $gameId];
        if ($status !== null) { $sql .= ' AND lp.status = :status'; $params['status'] = $status; }
        $sql .= ' ORDER BY lp.joined_at DESC, lp.id DESC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function findInGame(int $gameId, int $participantId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM liquidity_participants WHERE id = :id AND game_id = :game_id LIMIT 1');
        $statement->execute(['id' => $participantId, 'game_id' => $gameId]);
        return $statement->fetch() ?: null;
    }

    public function approve(int $participantId, int $teamId): void
    {
        $statement = $this->pdo->prepare('UPDATE liquidity_participants SET team_id = :team_id, status = \'approved\', approved_at = NOW() WHERE id = :id');
        $statement->execute(['team_id' => $teamId, 'id' => $participantId]);
    }

    public function reject(int $participantId): void
    {
        $statement = $this->pdo->prepare('UPDATE liquidity_participants SET status = \'rejected\' WHERE id = :id');
        $statement->execute(['id' => $participantId]);
    }

    public function getForUser(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT lp.*, lg.title, lg.invite_code, lg.status AS game_status, lt.name AS team_name FROM liquidity_participants lp INNER JOIN liquidity_games lg ON lg.id = lp.game_id LEFT JOIN liquidity_teams lt ON lt.id = lp.team_id WHERE lp.user_id = :user_id ORDER BY lp.joined_at DESC');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }
}
