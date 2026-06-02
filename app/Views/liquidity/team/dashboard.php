<?php
$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$money = static fn($v): string => 'R$ ' . number_format((float) $v, 2, ',', '.');
$status = (string) ($pool['status'] ?? 'empty');
$teamId = (int) ($team['id'] ?? 0);
$currentTeamPosition = null;
foreach ($ranking as $index => $entry) {
    if ((int) ($entry['id'] ?? 0) === $teamId) { $currentTeamPosition = $index + 1; break; }
}
$actionMeta = [
    'deposit_nft' => ['label' => 'Entrar na Piscina', 'copy' => 'Você entrega uma NFT ao organismo coletivo, recebe BTC e ganha uma cota.', 'effect' => ['-1 NFT', '+10 BTC', '+1 cota', '+1 NFT na piscina']],
    'withdraw_nft_btc' => ['label' => 'Retirar com BTC', 'copy' => 'Você recupera um ativo, mas reduz a profundidade da piscina.', 'effect' => ['-11 BTC', '-1 cota', '+1 NFT', '-1 NFT da piscina']],
    'withdraw_nft_cash' => ['label' => 'Retirar com dinheiro', 'copy' => 'Você usa caixa para sair da exposição coletiva.', 'effect' => ['-R$ 2.000,00', '-1 cota', '+1 NFT', '-1 NFT da piscina']],
    'buy_btc' => ['label' => 'Comprar BTC de outro time', 'copy' => 'Você compra BTC de outra equipe usando caixa.', 'effect' => ['-R$ preço combinado', '+BTC'], 'market' => true, 'target' => 'Time vendedor'],
    'sell_btc' => ['label' => 'Vender BTC para outro time', 'copy' => 'Você vende BTC para outra equipe e recebe caixa.', 'effect' => ['-BTC', '+R$ preço combinado'], 'market' => true, 'target' => 'Time comprador'],
    'buy_nft' => ['label' => 'Comprar NFT em mãos', 'copy' => 'Você compra NFT em mãos de outra equipe.', 'effect' => ['-R$ preço combinado', '+NFT em mãos'], 'market' => true, 'target' => 'Time vendedor'],
    'sell_nft' => ['label' => 'Vender NFT em mãos', 'copy' => 'Você vende NFT em mãos para outra equipe.', 'effect' => ['-NFT em mãos', '+R$ preço combinado'], 'market' => true, 'target' => 'Time comprador'],
    'buy_share' => ['label' => 'Comprar cota da piscina', 'copy' => 'Você compra cota pertencente a outra equipe.', 'effect' => ['-R$ preço combinado', '+cota'], 'market' => true, 'target' => 'Time vendedor'],
    'sell_share' => ['label' => 'Vender cota da piscina', 'copy' => 'Você vende uma cota sua para outra equipe sem alterar o total da piscina.', 'effect' => ['-cota', '+R$ preço combinado'], 'market' => true, 'target' => 'Time comprador'],
    'pass' => ['label' => 'Passar a vez', 'copy' => 'Você observa o mercado sem se mover.', 'effect' => ['Sem efeito econômico imediato']],
];
?>
<div class="liquidity-dashboard" id="liquidity-team-dashboard">
    <section class="liquidity-header">
        <h1>Piscina de Liquidez — <?= $e($session['name'] ?? 'Sessão') ?></h1>
        <p>Equipe: <strong><?= $e($team['name'] ?? '-') ?></strong></p>
        <p>Rodada <strong><?= (int) $currentRound ?></strong> de <?= (int) ($session['total_rounds'] ?? 0) ?> · Fase: <strong><?= $e($session['session_phase'] ?? 'regular') ?></strong> · Status: <strong><?= $e($session['status'] ?? '-') ?></strong></p>
        <?php if (!empty($team['is_eliminated'])): ?><p class="warning-text">Este time foi eliminado na semifinal e não pode mais realizar ações.</p><?php endif; ?>
        <?php if ($hasActed): ?><p class="warning-text">Este time já usou sua ação nesta rodada. Aguarde o professor avançar para a próxima rodada.</p><?php endif; ?>
    </section>

    <section class="liquidity-vitals">
        <article class="vitality-card"><h3>Caixa em R$</h3><p><?= $money($team['cash_balance'] ?? 0) ?></p></article>
        <article class="vitality-card"><h3>BTC</h3><p><?= number_format((float) ($team['btc_balance'] ?? 0), 2, ',', '.') ?></p></article>
        <article class="vitality-card"><h3>NFTs em mãos</h3><p><?= (int) ($team['nft_balance'] ?? 0) ?></p></article>
        <article class="vitality-card"><h3>Cotas da piscina</h3><p><?= (int) ($team['pool_shares'] ?? 0) ?></p></article>
    </section>

    <section class="payoff-card"><h2>Payoff atual</h2><p><?= $money($partialScore ?? 0) ?></p><?php if ($currentTeamPosition !== null): ?><small>Posição parcial: #<?= (int)$currentTeamPosition ?></small><?php endif; ?></section>

    <section class="pool-state-card <?= $e('pool-status-' . $status) ?>">
        <h2>Estado da piscina</h2>
        <ul>
            <li>NFTs depositadas: <span data-pool-nfts><?= (int) ($pool['pool_nfts'] ?? 0) ?></span></li>
            <li>Total de cotas emitidas: <span data-pool-shares><?= (int) ($pool['total_shares'] ?? 0) ?></span></li>
            <li>Valor total bloqueado: <span data-pool-total><?= $money($pool['total_value'] ?? 0) ?></span></li>
            <li>Rendimento por cota: <span data-pool-yield><?= $money($pool['yield_per_share'] ?? 0) ?></span></li>
            <li>Status: <strong data-pool-status><?= $e($status) ?></strong></li>
        </ul>
    </section>

    <section>
        <h2>Decisão da rodada</h2>
        <?php if ($hasActed): ?><p class="warning-text">Este time já usou sua ação nesta rodada.</p><?php endif; ?>
        <?php if ($lastAction): ?><p>Última ação registrada: <strong><?= $e($lastAction['action_type'] ?? '-') ?></strong> (qtd: <?= number_format((float)($lastAction['quantity'] ?? 0), 2, ',', '.') ?>)</p><?php endif; ?>
        <div class="action-grid">
            <?php foreach ($actionMeta as $type => $meta): ?>
                <form method="POST" action="/liquidity/team/action" class="action-card <?= ($hasActed || !empty($team['is_eliminated'])) ? 'action-disabled' : '' ?>">
                    <?= \App\Core\Csrf::input() ?>
                    <input type="hidden" name="action_type" value="<?= $e($type) ?>">
                    <h3><?= $e($meta['label']) ?></h3>
                    <p><?= $e($meta['copy']) ?></p>
                    <ul class="action-effect"><?php foreach ($meta['effect'] as $effect): ?><li><?= $e($effect) ?></li><?php endforeach; ?></ul>
                    <?php if (!empty($meta['market'])): ?>
                        <input type="number" name="quantity" min="0.01" step="0.01" value="1" placeholder="Quantidade" <?= ($hasActed || !empty($team['is_eliminated'])) ? 'disabled' : '' ?> required>
                        <select name="target_team_id" <?= ($hasActed || !empty($team['is_eliminated'])) ? 'disabled' : '' ?> required>
                            <option value=""><?= $e($meta['target'] ?? 'Time alvo') ?></option>
                            <?php foreach ($ranking as $counterparty): if ((int)($counterparty['id'] ?? 0) === $teamId) continue; ?>
                                <option value="<?= (int)$counterparty['id'] ?>"><?= $e($counterparty['name'] ?? '-') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="price" min="0.01" step="0.01" placeholder="Preço unitário em R$" <?= ($hasActed || !empty($team['is_eliminated'])) ? 'disabled' : '' ?> required>
                    <?php endif; ?>
                    <button type="submit" <?= ($hasActed || !empty($team['is_eliminated'])) ? 'disabled' : '' ?>><?= $e($meta['label']) ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="vitality-grid">
        <article class="feed-card"><h2>Feed vivo</h2><ul><?php if (!$events): ?><li>Nenhum evento registrado ainda.</li><?php endif; ?><?php foreach ($events as $event): ?><li>[R<?= (int)($event['round_number'] ?? 0) ?>] <?= $e($event['event_type'] ?? '') ?> — <?= $e($event['description'] ?? '') ?> (<?= $e($event['created_at'] ?? '') ?>)</li><?php endforeach; ?></ul></article>
        <article class="ranking-card"><h2>Ranking parcial</h2><table><thead><tr><th>#</th><th>Equipe</th><th>Score</th></tr></thead><tbody><?php foreach ($ranking as $i => $row): ?><tr class="<?= (int)($row['id'] ?? 0) === $teamId ? 'current-team-row' : '' ?>"><td><?= $i + 1 ?></td><td><?= $e($row['name'] ?? '-') ?></td><td><?= $money($row['score'] ?? 0) ?></td></tr><?php endforeach; ?></tbody></table></article>
    </section>
</div>
<script src="/assets/js/liquidity-team.js"></script>
