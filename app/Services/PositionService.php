<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Position;
use App\Models\Trade;
use App\Models\UserBalance;
use App\Repositories\MarketOptionRepository;
use App\Repositories\MarketRepository;
use App\Repositories\PositionRepository;
use App\Repositories\TradeRepository;
use App\Repositories\UserBalanceRepository;
use DomainException;
use PDO;
use Throwable;

final class PositionService
{
    private PDO $pdo;
    private MarketRepository $markets;
    private MarketOptionRepository $options;
    private PositionRepository $positions;
    private TradeRepository $trades;
    private UserBalanceRepository $balances;
    private MarketService $marketService;
    private ?bool $userBalancesEnabled = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
        $this->markets = new MarketRepository($this->pdo);
        $this->options = new MarketOptionRepository($this->pdo);
        $this->positions = new PositionRepository($this->pdo);
        $this->trades = new TradeRepository($this->pdo);
        $this->balances = new UserBalanceRepository($this->pdo);
        $this->marketService = new MarketService($this->pdo);
    }

    public function openPosition(int $userId, int $marketId, int $optionId, float $sharesAmount): array
    {
        $this->validateParticipation($userId, $marketId, $optionId, $sharesAmount);

        $this->pdo->beginTransaction();

        try {
            $this->updateUserBalance($userId, $sharesAmount);
            $this->createOrUpdatePosition($userId, $marketId, $optionId, $sharesAmount);
            $this->registerTrade($userId, $marketId, $optionId, $sharesAmount);

            $this->options->incrementWeight($optionId, $sharesAmount);
            $options = $this->recalculateProbabilities($marketId);
            $this->createSnapshot($marketId);

            $this->pdo->commit();

            return $options;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function validateParticipation(int $userId, int $marketId, int $optionId, float $sharesAmount): void
    {
        if ($userId <= 0) {
            throw new DomainException('Faça login para participar.');
        }

        if ($sharesAmount <= 0) {
            throw new DomainException('Informe uma quantidade válida para participar.');
        }

        $market = $this->markets->findById($marketId);

        if ($market === null) {
            throw new DomainException('Mercado não encontrado.');
        }

        if ((string) $market['status'] !== 'open') {
            throw new DomainException('Não é possível participar de mercados fechados ou em rascunho.');
        }

        if (! $this->options->belongsToMarket($optionId, $marketId)) {
            throw new DomainException('A opção selecionada não pertence ao mercado.');
        }

        if ($this->isUserBalanceEnabled()) {
            $balance = $this->ensureUserBalance($userId);

            if ((float) $balance['balance'] < $sharesAmount) {
                throw new DomainException('Saldo insuficiente para concluir a participação.');
            }
        }
    }

    public function recalculateProbabilities(int $marketId): array
    {
        return $this->marketService->recalculateProbabilities($marketId);
    }

    public function registerTrade(int $userId, int $marketId, int $optionId, float $sharesAmount): void
    {
        $this->trades->create(new Trade(null, $userId, $marketId, $optionId, $sharesAmount));
    }

    public function updateUserBalance(int $userId, float $amount): void
    {
        if (! $this->isUserBalanceEnabled()) {
            return;
        }

        $success = $this->balances->decreaseBalance($userId, $amount);

        if (! $success) {
            throw new DomainException('Não foi possível debitar o saldo. Verifique se você possui saldo suficiente.');
        }
    }

    public function createSnapshot(int $marketId): void
    {
        $this->marketService->createSnapshot($marketId);
    }

    private function createOrUpdatePosition(int $userId, int $marketId, int $optionId, float $sharesAmount): void
    {
        $existingPosition = $this->positions->findByUserMarketOption($userId, $marketId, $optionId);

        if ($existingPosition === null) {
            $this->positions->create(new Position(null, $userId, $marketId, $optionId, $sharesAmount));
            return;
        }

        $this->positions->increaseShares((int) $existingPosition['id'], $sharesAmount);
    }

    public function getUserBalance(int $userId): float
    {
        if (! $this->isUserBalanceEnabled()) {
            return 0.0;
        }

        $balance = $this->ensureUserBalance($userId);

        return (float) $balance['balance'];
    }

    public function getMarketTrades(int $marketId): array
    {
        return $this->trades->getByMarketId($marketId);
    }

    private function ensureUserBalance(int $userId): array
    {
        $balance = $this->balances->getByUserId($userId);

        if ($balance !== null) {
            return $balance;
        }

        $this->balances->create(new UserBalance(null, $userId, 1000.00));

        return $this->balances->getByUserId($userId) ?? ['balance' => 1000.00];
    }

    private function isUserBalanceEnabled(): bool
    {
        if ($this->userBalancesEnabled !== null) {
            return $this->userBalancesEnabled;
        }

        try {
            $statement = $this->pdo->query("SHOW TABLES LIKE 'user_balances'");
            $this->userBalancesEnabled = $statement !== false && $statement->fetch() !== false;
        } catch (Throwable $exception) {
            $this->userBalancesEnabled = false;
        }

        return $this->userBalancesEnabled;
    }
}
