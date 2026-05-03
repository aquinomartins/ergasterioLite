<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Market;
use App\Models\MarketOption;
use App\Models\MarketSnapshot;
use App\Repositories\ArtistRepository;
use App\Repositories\ArtworkRepository;
use App\Repositories\MarketOptionRepository;
use App\Repositories\MarketRepository;
use App\Repositories\MarketSnapshotRepository;
use DateTimeImmutable;
use DomainException;
use PDO;

final class MarketService
{
    private PDO $pdo;
    private MarketRepository $markets;
    private MarketOptionRepository $options;
    private MarketSnapshotRepository $snapshots;
    private ArtworkRepository $artworks;
    private ArtistRepository $artists;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
        $this->markets = new MarketRepository($this->pdo);
        $this->options = new MarketOptionRepository($this->pdo);
        $this->snapshots = new MarketSnapshotRepository($this->pdo);
        $this->artworks = new ArtworkRepository($this->pdo);
        $this->artists = new ArtistRepository($this->pdo);
    }

    public function createMarket(array $data, ?int $userId): array
    {
        if ($userId === null) {
            throw new DomainException('Faça login para criar um mercado.');
        }

        if (count($data['options'] ?? []) < 2) {
            throw new DomainException('O mercado deve ter pelo menos duas opções.');
        }

        $closeDate = new DateTimeImmutable((string) $data['closes_at']);

        if ($closeDate <= new DateTimeImmutable('now')) {
            throw new DomainException('A data de fechamento deve estar no futuro.');
        }

        $slug = $this->generateUniqueSlug((string) $data['title']);

        $this->pdo->beginTransaction();

        try {
            $marketId = $this->markets->create(new Market(
                null,
                (string) $data['title'],
                $slug,
                (string) $data['description'],
                (string) $data['market_type'],
                'draft',
                (string) ($data['resolution_mode'] ?? 'manual'),
                null,
                $closeDate->format('Y-m-d H:i:s'),
                null,
                $userId
            ));

            $optionModels = [];
            foreach ($data['options'] as $index => $option) {
                $normalized = $this->normalizeOption((string) $data['market_type'], $option);
                $optionModels[] = new MarketOption(
                    null,
                    $marketId,
                    (string) $data['market_type'],
                    $normalized['artwork_id'],
                    $normalized['artist_id'],
                    $normalized['label'],
                    1.0,
                    0.0,
                    $index + 1
                );
            }

            $this->options->createMany($optionModels);
            $this->recalculateProbabilities($marketId);
            $market = $this->getMarketById($marketId);

            if ($market === null) {
                throw new DomainException('Não foi possível carregar o mercado criado.');
            }

            $this->pdo->commit();

            return $market;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function publishMarket(int $id): array
    {
        $market = $this->getMarketById($id);

        if ($market === null) {
            throw new DomainException('Mercado não encontrado.');
        }

        if ((string) $market['status'] === 'resolved') {
            throw new DomainException('Mercados resolvidos não podem voltar para aberto.');
        }

        if ((string) $market['status'] === 'cancelled') {
            throw new DomainException('Mercados cancelados não podem ser publicados.');
        }

        $this->markets->update($id, [
            'status' => 'open',
            'opens_at' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
        ]);

        return $this->getMarketById($id) ?? $market;
    }

    public function getMarketById(int $id): ?array
    {
        $market = $this->markets->findById($id);

        if ($market === null) {
            return null;
        }

        $market['options'] = $this->options->getByMarketId($id);
        $market['snapshots'] = $this->hydrateSnapshots($this->snapshots->getByMarketId($id));

        return $market;
    }

    public function getMarketBySlug(string $slug): ?array
    {
        $market = $this->markets->findBySlug($slug);

        if ($market === null) {
            return null;
        }

        $market['options'] = $this->options->getByMarketId((int) $market['id']);
        $market['snapshots'] = $this->hydrateSnapshots($this->snapshots->getByMarketId((int) $market['id']));

        return $market;
    }

    public function listMarkets(): array
    {
        return $this->markets->getAll();
    }

    public function listOpenMarkets(): array
    {
        return $this->markets->getOpenMarkets();
    }

    public function closeMarket(int $id): array
    {
        $market = $this->getMarketById($id);

        if ($market === null) {
            throw new DomainException('Mercado não encontrado.');
        }

        if ((string) $market['status'] === 'resolved') {
            throw new DomainException('Mercados resolvidos não podem ser fechados novamente.');
        }

        if ((string) $market['status'] !== 'open') {
            throw new DomainException('Apenas mercados abertos podem ser fechados.');
        }

        $this->markets->setStatus($id, 'closed');

        return $this->getMarketById($id) ?? $market;
    }

    public function resolveMarket(int $id, int $resolvedOptionId): array
    {
        $market = $this->getMarketById($id);

        if ($market === null) {
            throw new DomainException('Mercado não encontrado.');
        }

        if ((string) $market['status'] === 'resolved') {
            throw new DomainException('Este mercado já foi resolvido.');
        }

        if (! in_array((string) $market['status'], ['open', 'closed'], true)) {
            throw new DomainException('Apenas mercados abertos ou fechados podem ser resolvidos.');
        }

        $option = $this->options->findById($resolvedOptionId);

        if ($option === null || (int) $option['market_id'] !== $id) {
            throw new DomainException('A opção selecionada não pertence a este mercado.');
        }

        $this->pdo->beginTransaction();

        try {
            if ((string) $market['status'] === 'open') {
                $this->markets->setStatus($id, 'closed');
            }

            $this->markets->setResolvedOption($id, $resolvedOptionId);
            $this->createSnapshot($id);

            $resolved = $this->getMarketById($id);
            $this->pdo->commit();

            return $resolved ?? $market;
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function recalculateProbabilities(int $marketId): array
    {
        $options = $this->options->getByMarketId($marketId);

        if ($options === []) {
            return [];
        }

        $totalWeight = 0.0;
        foreach ($options as $option) {
            $totalWeight += (float) $option['weight_value'];
        }

        if ($totalWeight <= 0.0) {
            $totalWeight = (float) count($options);
        }

        foreach ($options as &$option) {
            $weight = max(1.0, (float) $option['weight_value']);
            $option['weight_value'] = number_format($weight, 4, '.', '');
            $option['probability_value'] = number_format($weight / $totalWeight, 6, '.', '');
        }
        unset($option);

        $this->options->updateWeightsAndProbabilities($options);

        return $this->options->getByMarketId($marketId);
    }

    public function createSnapshot(int $marketId): void
    {
        $options = $this->options->getByMarketId($marketId);
        $snapshot = [
            'market_id' => $marketId,
            'options' => array_map(static function (array $option): array {
                return [
                    'id' => (int) $option['id'],
                    'label' => (string) $option['label'],
                    'weight' => (float) $option['weight_value'],
                    'probability' => (float) $option['probability_value'],
                ];
            }, $options),
        ];

        $this->snapshots->createSnapshot(new MarketSnapshot(
            null,
            $marketId,
            (string) json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ));
    }

    public function listEntitiesForType(string $marketType): array
    {
        if ($marketType === 'artwork_outcome') {
            return [
                'artworks' => $this->artworks->getAll(),
                'artists' => [],
            ];
        }

        return [
            'artworks' => [],
            'artists' => $this->artists->getAll(),
        ];
    }

    public function getCreationDependencies(): array
    {
        return [
            'artworks' => $this->artworks->getAll(),
            'artists' => $this->artists->getAll(),
        ];
    }

    private function hydrateSnapshots(array $snapshots): array
    {
        foreach ($snapshots as &$snapshot) {
            $snapshot['decoded_snapshot'] = json_decode((string) $snapshot['snapshot_json'], true) ?? [];
        }
        unset($snapshot);

        return $snapshots;
    }

    private function normalizeOption(string $marketType, array $option): array
    {
        $label = trim((string) ($option['label'] ?? ''));
        $artworkId = isset($option['artwork_id']) ? (int) $option['artwork_id'] : 0;
        $artistId = isset($option['artist_id']) ? (int) $option['artist_id'] : 0;

        if ($label === '') {
            throw new DomainException('Todas as opções devem possuir rótulo.');
        }

        if ($marketType === 'artwork_outcome') {
            $artwork = $this->artworks->findById($artworkId);

            if ($artwork === null) {
                throw new DomainException('Cada opção deve apontar para uma obra válida.');
            }

            return [
                'label' => $label,
                'artwork_id' => $artworkId,
                'artist_id' => null,
            ];
        }

        $artist = $this->artists->findById($artistId);

        if ($artist === null) {
            throw new DomainException('Cada opção deve apontar para um artista válido.');
        }

        return [
            'label' => $label,
            'artwork_id' => null,
            'artist_id' => $artistId,
        ];
    }

    private function generateUniqueSlug(string $title): string
    {
        $baseSlug = $this->slugify($title);
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->markets->slugExists($slug)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugify(string $value): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $normalized));
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'mercado';
    }
}
