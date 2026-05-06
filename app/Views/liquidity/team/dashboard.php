<?php
$s = $state['session'] ?? [];
$pool = $state['pool'] ?? [];
$ranking = $state['ranking'] ?? [];
$feed = $state['feed'] ?? [];

$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v): string => 'R$ ' . number_format((float)$v, 2, ',', '.');

$teamId = (int)($team['id'] ?? 0);
$alreadyActed = false;
foreach (($state['actions'] ?? []) as $action) {
    if ((int)($action['team_id'] ?? 0) === $teamId && (int)($action['round_number'] ?? -1) === (int)($s['current_round'] ?? 0)) {
        $alreadyActed = true;
        break;
    }
}

$actionMeta = [
    'deposit_nft' => ['label' => 'Entrar na Piscina', 'copy' => 'Você entrega uma NFT ao organismo coletivo, recebe BTC e ganha uma cota.', 'quantity' => false],
    'withdraw_nft_btc' => ['label' => 'Retirar NFT com BTC', 'copy' => 'Você recupera um ativo, mas reduz a profundidade da piscina.', 'quantity' => false],
    'withdraw_nft_cash' => ['label' => 'Retirar NFT com dinheiro', 'copy' => 'Você usa caixa para sair da exposição coletiva.', 'quantity' => false],
    'buy_btc' => ['label' => 'Comprar BTC', 'copy' => 'Você troca caixa por poder de retirada.', 'quantity' => true],
    'sell_btc' => ['label' => 'Vender BTC', 'copy' => 'Você transforma liquidez em caixa imediato.', 'quantity' => true],
    'sell_nft' => ['label' => 'Vender NFT em mãos', 'copy' => 'Você vende um ativo bruto para reforçar o caixa.', 'quantity' => false],
    'sell_share' => ['label' => 'Vender cota', 'copy' => 'Você reduz sua participação no organismo coletivo.', 'quantity' => false],
    'pass' => ['label' => 'Passar a vez', 'copy' => 'Você observa o mercado sem se mover.', 'quantity' => false],
];
?>

<div class="liquidity-shell" id="liquidity-team-dashboard" data-session-id="<?= (int)($s['id'] ?? 0) ?>">
    <header class="liquidity-header">
        <h1><?= $e($s['name'] ?? 'Sessão') ?></h1>
        <p>Rodada <strong><?= (int)($s['current_round'] ?? 0) ?></strong> / <?= (int)($s['total_rounds'] ?? 0) ?> · Status: <strong><?= $e($s['status'] ?? '-') ?></strong></p>
    </header>

    <section class="vitality-grid">
        <article class="vitality-card">
            <h2>Meus recursos</h2>
            <ul>
                <li>Caixa: <?= $money($team['cash_balance'] ?? 0) ?></li>
                <li>BTC: <?= number_format((float)($team['btc_balance'] ?? 0), 4, ',', '.') ?></li>
                <li>NFTs em mãos: <?= (int)($team['nft_balance'] ?? 0) ?></li>
                <li>Cotas da piscina: <?= number_format((float)($team['share_balance'] ?? 0), 4, ',', '.') ?></li>
                <li>Score parcial: <?= number_format((float)($team['score'] ?? 0), 2, ',', '.') ?></li>
            </ul>
        </article>

        <article class="vitality-card pool-card <?= $e('pool-status-' . ($pool['status'] ?? 'stable')) ?>" data-pool-state>
            <h2>Estado da Piscina</h2>
            <ul>
                <li>NFTs depositadas: <span data-pool-nfts><?= (int)($pool['nft_reserve'] ?? 0) ?></span></li>
                <li>Valor total bloqueado: <span data-pool-total><?= $money($pool['total_value_locked'] ?? 0) ?></span></li>
                <li>Cotas emitidas: <span data-pool-shares><?= number_format((float)($pool['share_supply'] ?? 0), 4, ',', '.') ?></span></li>
                <li>Rendimento por cota: <span data-pool-yield><?= number_format((float)($pool['yield_per_share'] ?? 0), 4, ',', '.') ?></span></li>
                <li>Status visual: <strong data-pool-status><?= $e($pool['status'] ?? '-') ?></strong></li>
            </ul>
        </article>
    </section>

    <section class="vitality-card">
        <h2>Decisão da Rodada</h2>
        <?php if ($alreadyActed): ?>
            <p class="warning-text">A decisão desta rodada já foi enviada. Aguarde o professor avançar para a próxima rodada.</p>
        <?php endif; ?>
        <div class="action-grid">
            <?php foreach ($actionMeta as $type => $meta): ?>
                <form method="post" action="/liquidity/team/action" class="action-card" data-liquidity-action-form>
                    <?= \App\Core\Csrf::input() ?>
                    <input type="hidden" name="action_type" value="<?= $e($type) ?>">
                    <h3><?= $e($meta['label']) ?></h3>
                    <p><?= $e($meta['copy']) ?></p>
                    <?php if ($meta['quantity']): ?>
                        <label>Quantidade
                            <input type="number" name="quantity" min="0.0001" step="0.0001" value="1" required <?= $alreadyActed ? 'disabled' : '' ?>>
                        </label>
                    <?php endif; ?>
                    <button type="submit" <?= $alreadyActed ? 'disabled' : '' ?>>Enviar decisão</button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="vitality-grid">
        <article class="vitality-card">
            <h2>Feed Vivo</h2>
            <ul class="feed-list" data-feed-list>
                <?php foreach ($feed as $event): ?>
                    <li><?= $e($event['event_type'] ?? 'evento') ?> — <?= $e($event['description'] ?? '') ?></li>
                <?php endforeach; ?>
            </ul>
        </article>

        <article class="vitality-card">
            <h2>Ranking Parcial</h2>
            <table class="ranking-table" data-ranking-table>
                <thead><tr><th>Posição</th><th>Equipe</th><th>Score parcial</th></tr></thead>
                <tbody>
                <?php foreach ($ranking as $i => $row): ?>
                    <tr><td><?= $i + 1 ?></td><td><?= $e($row['name'] ?? 'Equipe') ?></td><td><?= number_format((float)($row['score'] ?? 0), 2, ',', '.') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </article>
    </section>

    <section class="vitality-card market-arena-card">
        <h2>Mercados da Arena</h2>
        <?php if (!empty($predictionMarkets)): ?>
            <?php foreach ($predictionMarkets as $m): ?>
                <div class="prediction-market-card">
                    <strong><?= $e($m['question'] ?? '') ?></strong>
                    <small>(<?= $e($m['status'] ?? '-') ?>)</small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Mercados da Arena serão ativados na próxima fase.</p>
        <?php endif; ?>
    </section>
</div>
<script src="/assets/js/liquidity-team.js"></script>
