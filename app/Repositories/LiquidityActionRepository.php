<?php declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LiquidityActionRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function hasTeamActed(int $sessionId, int $roundNumber, int $teamId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM liquidity_team_actions WHERE session_id = ? AND round_number = ? AND team_id = ?');
        $stmt->execute([$sessionId, $roundNumber, $teamId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(int $sessionId, int $roundNumber, int $teamId, string $actionType, float $quantity): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO liquidity_team_actions (session_id, round_number, team_id, action_type, quantity) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$sessionId, $roundNumber, $teamId, $actionType, $quantity]);
    }

    public function getLastActionForTeam(int $sessionId, int $roundNumber, int $teamId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM liquidity_team_actions WHERE session_id = ? AND round_number = ? AND team_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$sessionId, $roundNumber, $teamId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
