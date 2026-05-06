<?php
$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v): string => 'R$ ' . number_format((float)$v, 2, ',', '.');
$btcFmt = static fn($v): string => number_format((float)$v, 4, ',', '.');

$lastAction = null;
foreach ($events as $event) {
    if ((int)($event['team_id'] ?? 0) === (int)($team['id'] ?? 0)) { $lastAction = $event; break; }
}

$actionMeta = [
    'deposit_nft' => ['label' => 'Entrar na Piscina', 'desc' => 'Deposita 1 NFT na piscina.', 'effect' => ['-1 NFT em mãos', '+' . $btcFmt($session['btc_deposit_reward'] ?? 0) . ' BTC', '+1 cota', '+1 NFT na piscina']],
    'withdraw_nft_btc' => ['label' => 'Retirar NFT com BTC', 'desc' => 'Retira 1 NFT usando BTC.', 'effect' => ['-' . $btcFmt($session['btc_withdraw_cost'] ?? 0) . ' BTC', '+1 NFT em mãos', '-1 NFT na piscina']],
    'withdraw_nft_cash' => ['label' => 'Retirar NFT com dinheiro', 'desc' => 'Retira 1 NFT usando caixa.', 'effect' => ['-' . $money($session['cash_withdraw_cost'] ?? 0), '+1 NFT em mãos', '-1 NFT na piscina']],
    'buy_btc' => ['label' => 'Comprar BTC', 'desc' => 'Compra BTC com caixa.', 'effect' => ['Caixa - (quantidade × preço de compra)', 'BTC + quantidade'], 'quantity' => true],
    'sell_btc' => ['label' => 'Vender BTC', 'desc' => 'Vende BTC e recebe caixa.', 'effect' => ['BTC - quantidade', 'Caixa + (quantidade × preço de venda)'], 'quantity' => true],
    'sell_nft' => ['label' => 'Vender NFT em mãos', 'desc' => 'Vende 1 NFT em mãos.', 'effect' => ['-1 NFT em mãos', '+' . $money($session['nft_sell_price'] ?? 0)]],
    'sell_share' => ['label' => 'Vender cota', 'desc' => 'Vende 1 cota da piscina.', 'effect' => ['-1 cota', '+' . $money($session['share_sell_price'] ?? 0), '-1 cota total da piscina']],
    'pass' => ['label' => 'Passar a vez', 'desc' => 'Não realiza movimentação econômica.', 'effect' => ['Sem alteração de saldo']],
];
?>
<div class="liquidity-shell" id="liquidity-team-dashboard">
    <header class="liquidity-header">
        <h1><?= $e($session['name'] ?? 'Sessão') ?></h1>
        <p>Rodada <strong><?= (int)$currentRound ?></strong> / <?= (int)($session['total_rounds'] ?? 0) ?> · Status: <strong><?= $e($session['status'] ?? '-') ?></strong></p>
    </header>

    <section class="vitality-grid">
        <article class="vitality-card">
            <h2>Meus recursos</h2>
            <ul>
                <li>Caixa: <?= $money($team['cash_balance'] ?? 0) ?></li>
                <li>BTC: <?= $btcFmt($team['btc_balance'] ?? 0) ?></li>
                <li>NFTs em mãos: <?= (int)($team['nft_balance'] ?? 0) ?></li>
                <li>Cotas da piscina: <?= (int)($team['pool_shares'] ?? 0) ?></li>
            </ul>
            <p class="payoff-badge">Payoff atual: <strong><?= $money($partialScore ?? 0) ?></strong></p>
        </article>

        <article class="vitality-card pool-card <?= $e('pool-status-' . ($pool['status'] ?? 'stable')) ?>">
            <h2>Estado da Piscina</h2>
            <ul>
                <li>NFTs na piscina: <span data-pool-nfts><?= (int)($pool['pool_nfts'] ?? 0) ?></span></li>
                <li>Cotas totais: <span data-pool-shares><?= (int)($pool['total_shares'] ?? 0) ?></span></li>
                <li>Valor total bloqueado: <span data-pool-total><?= $money($pool['total_value'] ?? 0) ?></span></li>
                <li>Rendimento por cota: <span data-pool-yield><?= $money($pool['yield_per_share'] ?? 0) ?></span></li>
                <li>Status: <strong data-pool-status><?= $e($pool['status'] ?? '-') ?></strong></li>
            </ul>
        </article>
    </section>

    <section class="vitality-card">
        <h2>Ações da rodada</h2>
        <?php if ($hasActed): ?>
            <p class="warning-text">A decisão desta rodada já foi enviada. Aguarde o professor avançar para a próxima rodada.</p>
            <?php if ($lastAction): ?><p><strong>Última ação:</strong> <?= $e($lastAction['description'] ?? '-') ?></p><?php endif; ?>
        <?php endif; ?>
        <div class="action-grid">
            <?php foreach ($actionMeta as $type => $meta): ?>
                <form method="post" action="/liquidity/team/action" class="action-card" data-liquidity-action-form>
                    <?= \App\Core\Csrf::input() ?>
                    <input type="hidden" name="action_type" value="<?= $e($type) ?>">
                    <h3><?= $e($meta['label']) ?></h3><p><?= $e($meta['desc']) ?></p>
                    <div class="action-effect"><strong>Efeito esperado:</strong><ul><?php foreach ($meta['effect'] as $line): ?><li><?= $e($line) ?></li><?php endforeach; ?></ul></div>
                    <?php if (!empty($meta['quantity'])): ?><label>Quantidade <input type="number" name="quantity" min="0.0001" step="0.0001" value="1" required <?= $hasActed ? 'disabled' : '' ?>></label><?php endif; ?>
                    <button type="submit" <?= $hasActed ? 'disabled' : '' ?>><?= $e($meta['label']) ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<script src="/assets/js/liquidity-team.js"></script>
