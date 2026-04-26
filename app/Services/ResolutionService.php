<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Payout;
use App\Policies\MarketPolicy;
use App\Repositories\MarketOptionRepository;
use App\Repositories\MarketRepository;
use App\Repositories\MarketResolutionRepository;
use App\Repositories\PayoutRepository;
use App\Repositories\PositionRepository;
use App\Repositories\RankingRepository;
use App\Repositories\ReputationLogRepository;
use App\Repositories\UserBalanceRepository;
use App\Repositories\UserRepository;
use DomainException;
use PDO;
use Throwable;

final class ResolutionService
{
    private PDO $pdo;
    private MarketRepository $markets;
    private MarketOptionRepository $options;
    private PositionRepository $positions;
    private MarketResolutionRepository $resolutions;
    private PayoutRepository $payouts;
    private RankingRepository $rankings;
    private ReputationLogRepository $reputationLogs;
    private UserBalanceRepository $balances;
    private UserRepository $users;
    private MarketPolicy $policy;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
        $this->markets = new MarketRepository($this->pdo);
        $this->options = new MarketOptionRepository($this->pdo);
        $this->positions = new PositionRepository($this->pdo);
        $this->resolutions = new MarketResolutionRepository($this->pdo);
        $this->payouts = new PayoutRepository($this->pdo);
        $this->rankings = new RankingRepository($this->pdo);
        $this->reputationLogs = new ReputationLogRepository($this->pdo);
        $this->balances = new UserBalanceRepository($this->pdo);
        $this->users = new UserRepository($this->pdo);
        $this->policy = new MarketPolicy();
    }

    public function closeMarket(int $marketId, int $actorUserId): array
    {
        $this->assertCanManage($actorUserId);
        $market = $this->requireMarket($marketId);

        if ((string) $market['status'] === 'resolved') {
            throw new DomainException('Mercados resolvidos não podem ser fechados novamente.');
        }

        if ((string) $market['status'] === 'cancelled') {
            throw new DomainException('Mercados cancelados não podem ser fechados.');
        }

        if ((string) $market['status'] !== 'open') {
            throw new DomainException('Apenas mercados abertos podem ser fechados.');
        }

        $this->markets->setStatus($marketId, 'closed');
        $this->logAdminAction($actorUserId, $marketId, 'close_market', 'Fechamento manual de mercado');

        return $this->markets->findById($marketId) ?? $market;
    }

    public function resolveMarket(int $marketId, int $winningOptionId, int $actorUserId, ?string $notes): array
    {
        $this->assertCanManage($actorUserId);

        $market = $this->requireMarket($marketId);

        if ((string) $market['status'] === 'resolved' || $this->resolutions->findByMarketId($marketId) !== null) {
            throw new DomainException('Este mercado já foi resolvido.');
        }

        if ((string) $market['status'] === 'cancelled') {
            throw new DomainException('Mercados cancelados não podem ser resolvidos.');
        }

        if (! in_array((string) $market['status'], ['open', 'closed'], true)) {
            throw new DomainException('Apenas mercados abertos ou fechados podem ser resolvidos.');
        }

        $option = $this->options->findById($winningOptionId);
        if ($option === null || (int) $option['market_id'] !== $marketId) {
            throw new DomainException('A opção vencedora não pertence ao mercado.');
        }

        $this->pdo->beginTransaction();

        try {
            if ((string) $market['status'] === 'open') {
                $this->markets->setStatus($marketId, 'closed');
            }

            $this->resolutions->create($marketId, $winningOptionId, $actorUserId, $notes);
            $this->markets->setResolvedOption($marketId, $winningOptionId);

            $this->executePayouts($marketId, $winningOptionId);
            $this->updateRankingsAfterResolution($marketId, $winningOptionId);
            $this->logAdminAction($actorUserId, $marketId, 'resolve_market', 'Mercado resolvido manualmente');

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }

        return $this->markets->findById($marketId) ?? $market;
    }

    public function executePayouts(int $marketId, int $winningOptionId): void
    {
        $existingPayouts = $this->payouts->getByMarketId($marketId);
        if ($existingPayouts !== []) {
            throw new DomainException('Já existem payouts para este mercado.');
        }

        $winningPositions = $this->positions->getWinningPositions($marketId, $winningOptionId);

        foreach ($winningPositions as $position) {
            $shares = (float) $position['shares_amount'];
            if ($shares <= 0) {
                continue;
            }

            $grossAmount = round($shares * 2, 2);
            $feeAmount = 0.0;
            $netAmount = $grossAmount;

            $this->payouts->create(new Payout(
                null,
                (int) $position['user_id'],
                $marketId,
                isset($position['id']) ? (int) $position['id'] : null,
                $winningOptionId,
                $shares,
                $grossAmount,
                $feeAmount,
                $netAmount,
                'executed'
            ));

            if ($this->tableExists('user_balances')) {
                $this->balances->increaseBalance((int) $position['user_id'], $netAmount);
            }
        }
    }

    public function updateRankingsAfterResolution(int $marketId, int $winningOptionId): void
    {
        $participants = $this->positions->getByMarketId($marketId);
        if ($participants === []) {
            return;
        }

        $participantsByUser = [];
        foreach ($participants as $position) {
            $participantsByUser[(int) $position['user_id']] = true;
        }

        $payoutsByUser = [];
        foreach ($this->payouts->getByMarketId($marketId) as $payout) {
            $userId = (int) $payout['user_id'];
            $payoutsByUser[$userId] = ($payoutsByUser[$userId] ?? 0.0) + (float) $payout['net_amount'];
        }

        foreach (array_keys($participantsByUser) as $userId) {
            $this->rankings->createIfNotExists($userId);
            $this->rankings->incrementMarketsParticipated($userId);

            if (isset($payoutsByUser[$userId]) && $payoutsByUser[$userId] > 0) {
                $this->rankings->incrementPayoff($userId, $payoutsByUser[$userId]);
                $this->rankings->incrementMarketsWon($userId);
                $this->rankings->updateReputationScore($userId, 10.0);

                if ($this->tableExists('reputation_logs')) {
                    $this->reputationLogs->create($userId, $marketId, 'market_win', 10.0);
                }
            } else {
                $this->rankings->updateReputationScore($userId, 1.0);

                if ($this->tableExists('reputation_logs')) {
                    $this->reputationLogs->create($userId, $marketId, 'market_participation', 1.0);
                }
            }
        }
    }

    private function assertCanManage(int $actorUserId): void
    {
        $actor = $this->users->findWithProfileById($actorUserId);

        if (! $this->policy->canManage($actor)) {
            throw new DomainException('Usuário sem permissão para gerenciar mercados.');
        }
    }

    private function requireMarket(int $marketId): array
    {
        $market = $this->markets->findById($marketId);

        if ($market === null) {
            throw new DomainException('Mercado não encontrado.');
        }

        return $market;
    }

    private function tableExists(string $table): bool
    {
        try {
            $statement = $this->pdo->query("SHOW TABLES LIKE '" . $table . "'");
            return $statement !== false && $statement->fetch() !== false;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private function logAdminAction(int $userId, int $marketId, string $action, string $description): void
    {
        if (! $this->tableExists('admin_actions')) {
            return;
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO admin_actions (user_id, action, context_type, context_id, description, created_at)
             VALUES (:user_id, :action, :context_type, :context_id, :description, NOW())'
        );

        try {
            $statement->execute([
                'user_id' => $userId,
                'action' => $action,
                'context_type' => 'market',
                'context_id' => $marketId,
                'description' => $description,
            ]);
        } catch (Throwable $exception) {
            // ignora schemas diferentes
        }
    }
}
