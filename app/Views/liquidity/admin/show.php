<?php
$s = $state['session'] ?? [];
$pool = $state['pool'] ?? [];
$ranking = $state['ranking'] ?? [];
$feed = $state['feed'] ?? [];
$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v): string => 'R$ ' . number_format((float)$v, 2, ',', '.');
?>
<div class="liquidity-dashboard">
    <section class="liquidity-header">
        <h1><?= $e($s['name'] ?? 'Sessão') ?></h1>
        <p>Código: <strong><?= $e($s['access_code'] ?? '-') ?></strong> · Rodada <?= (int)($s['current_round'] ?? 0) ?>/<?= (int)($s['total_rounds'] ?? 0) ?> · Fase: <?= (['session_phase'] ?? 'regular') ?> · Status: <?= $e($s['status'] ?? '-') ?></p>
        <p>Parâmetros: BTC compra <?= $money($s['btc_buy_price'] ?? 0) ?> | BTC venda <?= $money($s['btc_sell_price'] ?? 0) ?> | Venda NFT <?= $money($s['nft_sell_price'] ?? 0) ?> | Venda cota <?= $money($s['share_sell_price'] ?? 0) ?></p>
    </section>

    <section class="pool-state-card <?= $e('pool-status-' . ($pool['status'] ?? 'empty')) ?>">
        <h2>Estado da Piscina</h2>
        <p>NFTs: <?= (int)($pool['pool_nfts'] ?? 0) ?> · Cotas: <?= (int)($pool['total_shares'] ?? 0) ?> · Valor bloqueado: <?= $money($pool['total_value'] ?? 0) ?> · Rendimento/cota: <?= $money($pool['yield_per_share'] ?? 0) ?></p>
    </section>

    <section class="vitality-card">
        <h2>Equipes</h2>
        <table><thead><tr><th>Equipe</th><th>Caixa</th><th>BTC</th><th>NFTs</th><th>Cotas</th><th>Payoff</th><th>Status</th><th>Agiu?</th></tr></thead>
        <tbody><?php foreach ($teams as $team): $score = (float)$team['cash_balance'] + ((float)$team['btc_balance']*(float)$s['btc_sell_price']) + ((int)$team['nft_balance']*(float)$s['nft_sell_price']) + ((int)$team['pool_shares']*(float)$s['share_sell_price']); ?><tr><td><?= $e($team['name']) ?></td><td><?= $money($team['cash_balance']) ?></td><td><?= number_format((float)$team['btc_balance'],2,',','.') ?></td><td><?= (int)$team['nft_balance'] ?></td><td><?= (int)$team['pool_shares'] ?></td><td><?= $money($score) ?></td><td><?= !empty($actedByTeam[(int)$team['id']]) ? 'Sim' : 'Não' ?></td></tr><?php endforeach; ?></tbody></table>
    </section>

    <section class="vitality-grid"><article class="ranking-card"><h2>Ranking</h2><ol><?php foreach($ranking as $r): ?><li><?= $e($r['name'] ?? '-') ?> — <?= $money($r['score'] ?? 0) ?></li><?php endforeach; ?></ol></article><article class="feed-card"><h2>Feed</h2><ul><?php foreach($feed as $event): ?><li>[R<?= (int)$event['round_number'] ?>] <?= $e($event['event_type']) ?> — <?= $e($event['description']) ?></li><?php endforeach; ?></ul></article></section>

    <section class="action-grid">
        <a class="action-card" href="/liquidity/<?= (int)$s['id'] ?>/teams"><h3>Gerenciar equipes</h3></a>
        <a class="action-card" target="_blank" href="/liquidity/<?= (int)$s['id'] ?>/projector"><h3>Abrir projetor</h3></a>
        <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/advance-round" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit">Avançar rodada</button></form>
        <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/close" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit">Encerrar sessão</button></form>
    </section>
</div>
<script src="/assets/js/liquidity-admin.js"></script>
