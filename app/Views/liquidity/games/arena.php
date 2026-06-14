<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$qty = static fn($v) => rtrim(rtrim(number_format((float)$v, 2, ',', '.'), '0'), ',');
$ranking = $ranking ?? $teams ?? [];
$poolStats = $poolStats ?? $poolMetrics ?? [];
$roundStats = $roundStats ?? ['acted' => 0, 'total' => count($ranking)];
$recentEvents = $recentEvents ?? $events ?? [];
$executedTrades = $executedTrades ?? [];
$currentRound = (int)($currentRound ?? $game['current_round'] ?? 1);
$maxRounds = (int)($maxRounds ?? $game['max_rounds'] ?? 6);
$statusLabel = (string)($statusLabel ?? $game['status'] ?? 'Em acompanhamento');
$statusClass = 'status-' . preg_replace('/[^a-z0-9_-]+/', '-', strtolower((string)($game['status'] ?? 'waiting')));
$valuationRules = $valuationRules ?? ['btc_value' => 100, 'nft_value' => 2000, 'share_value' => 2000, 'pool_yield_rate' => 0.10];
$actionLabel = static fn($a) => ['deposit_nft'=>'Depositou NFT','withdraw_nft_btc'=>'Retirou NFT com BTC','withdraw_nft_cash'=>'Retirou NFT com caixa','pass'=>'Passou a vez','buy_btc'=>'Comprou BTC','sell_btc'=>'Vendeu BTC'][$a] ?? 'Decisão registrada';
$statusTrade = static fn($s) => ['pending_counterparty'=>'Aguardando resposta','executed'=>'Executada','rejected'=>'Rejeitada','cancelled'=>'Cancelada','master_executed'=>'Executada pelo Master Gold'][$s] ?? 'Atualizada';
$assetLabel = static fn($a) => ['btc'=>'BTC','nft'=>'NFT','share'=>'cotas'][$a] ?? strtoupper((string)$a);
?>
<main class="arena-page">
    <section class="arena-hero">
        <div>
            <p class="arena-kicker">Arena pública</p>
            <h1 class="arena-title">Piscina de Liquidez</h1>
            <p class="arena-subtitle"><?= $e($game['title'] ?? $game['name'] ?? 'Jogo') ?></p>
            <div class="arena-meta">
                <span>Rodada <?= $currentRound ?> de <?= $maxRounds ?></span>
                <span><?= count($ranking) ?> equipe<?= count($ranking) === 1 ? '' : 's' ?></span>
                <span>Atualizado agora</span>
            </div>
        </div>
        <span class="arena-status-badge <?= $e($statusClass) ?>"><?= $e($statusLabel) ?></span>
    </section>

    <section class="arena-section scoreboard">
        <div class="arena-section-header">
            <div><p class="arena-kicker">Liderança atual</p><h2>Placar</h2></div>
            <p class="public-note">Patrimônio estimado considera BTC = <?= $money($valuationRules['btc_value'] ?? 100) ?>, NFT = <?= $money($valuationRules['nft_value'] ?? 2000) ?> e cota = <?= $money($valuationRules['share_value'] ?? 2000) ?>.</p>
        </div>
        <?php if (empty($ranking)): ?>
            <div class="empty-state">Ainda não há equipes aprovadas neste jogo.</div>
        <?php else: ?>
            <?php $leader = $ranking[0]; $leaderId = (int)($leader['id'] ?? 0); ?>
            <article class="scoreboard-leader">
                <span class="score-rank">1º</span>
                <div><strong class="score-team"><?= $e($leader['name'] ?? 'Equipe') ?></strong><span>Liderança atual do placar estimado</span></div>
                <strong class="score-value"><?= $money($leader['estimated_wealth'] ?? 0) ?></strong>
                <span class="decision-status <?= !empty($actedByTeam[$leaderId]) ? 'decision-sent' : 'decision-pending' ?>"><?= !empty($actedByTeam[$leaderId]) ? 'Decisão enviada' : 'Aguardando decisão' ?></span>
            </article>
            <div class="scoreboard-table-wrap"><table class="scoreboard-table"><thead><tr><th>Pos.</th><th>Equipe</th><th>Patrimônio estimado</th><th>Caixa</th><th>BTC</th><th>NFTs</th><th>Cotas</th><th>Ação da rodada</th></tr></thead><tbody>
            <?php foreach ($ranking as $i => $team): $tid = (int)($team['id'] ?? 0); ?>
                <tr><td class="score-rank"><?= $i + 1 ?>º</td><td class="score-team"><?= $e($team['name'] ?? 'Equipe') ?></td><td class="score-value"><?= $money($team['estimated_wealth'] ?? 0) ?></td><td><?= $money($team['cash_balance'] ?? 0) ?></td><td><?= $e($qty($team['btc_balance'] ?? 0)) ?></td><td><?= (int)($team['nft_balance'] ?? 0) ?></td><td><?= (int)($team['pool_shares'] ?? 0) ?></td><td><span class="decision-status <?= !empty($actedByTeam[$tid]) ? 'decision-sent' : 'decision-pending' ?>"><?= !empty($actedByTeam[$tid]) ? 'Decisão enviada' : 'Aguardando decisão' ?></span></td></tr>
            <?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>

    <div class="arena-grid">
        <section class="arena-section">
            <div class="arena-section-header"><div><p class="arena-kicker">Indicadores</p><h2>Estado da Piscina</h2></div></div>
            <div class="pool-metrics">
                <article class="pool-card"><span>Total de cotas</span><strong class="pool-value"><?= (int)($poolStats['total_shares'] ?? 0) ?></strong></article>
                <article class="pool-card"><span>Valor estimado da piscina</span><strong class="pool-value"><?= $money($poolStats['pool_value'] ?? 0) ?></strong></article>
                <article class="pool-card"><span>Dividendo por cota</span><strong class="pool-value"><?= $money($poolStats['estimated_dividend_per_share'] ?? 0) ?></strong></article>
                <article class="pool-card"><span>Dividendo total da rodada</span><strong class="pool-value"><?= $money($poolStats['estimated_dividend_total'] ?? 0) ?></strong></article>
                <article class="pool-card"><span>NFTs estimados na piscina</span><strong class="pool-value"><?= (int)($poolStats['estimated_pool_nfts'] ?? 0) ?></strong></article>
            </div>
            <?php if ((int)($poolStats['total_shares'] ?? 0) === 0): ?><div class="empty-state">A piscina ainda está vazia. Quando equipes depositarem NFTs, as cotas e dividendos aparecerão aqui.</div><?php endif; ?>
            <p class="public-note">Estimativas: NFT = <?= $money($valuationRules['nft_value'] ?? 2000) ?>, cota = <?= $money($valuationRules['share_value'] ?? 2000) ?>, BTC = <?= $money($valuationRules['btc_value'] ?? 100) ?>.</p>
        </section>

        <section class="arena-section round-decisions">
            <div class="arena-section-header"><div><p class="arena-kicker">Rodada <?= $currentRound ?></p><h2>Decisões da rodada</h2></div><strong><?= (int)($roundStats['acted'] ?? 0) ?> de <?= (int)($roundStats['total'] ?? 0) ?></strong></div>
            <?php if (empty($decisions)): ?><div class="empty-state">Nenhuma equipe enviou decisão nesta rodada.</div><?php endif; ?>
            <?php foreach (($decisions ?? []) as $decision): ?>
                <div class="decision-row"><div><strong><?= $e($decision['team_name'] ?? 'Equipe') ?></strong><?php if (!empty($decision['last_action_label'])): ?><small>Última ação: <?= $e($decision['last_action_label']) ?></small><?php endif; ?></div><span class="decision-status <?= !empty($decision['has_acted']) ? 'decision-sent' : 'decision-pending' ?>"><?= !empty($decision['has_acted']) ? 'Decisão enviada' : 'Aguardando decisão' ?></span></div>
            <?php endforeach; ?>
        </section>
    </div>

    <section class="arena-section">
        <div class="arena-section-header"><div><p class="arena-kicker">Transações públicas</p><h2>Mercado</h2></div></div>
        <?php if (empty($executedTrades)): ?><div class="empty-state">Nenhuma transação pública concluída nesta rodada.</div><?php else: ?><div class="market-public-list"><?php foreach ($executedTrades as $trade): ?><article class="event-item"><strong><?= $e($statusTrade($trade['status'] ?? '')) ?></strong><p><?= $e(($trade['proposer_name'] ?? 'Equipe') . ' e ' . ($trade['counterparty_name'] ?? 'Equipe') . ' — ' . $assetLabel($trade['asset_type'] ?? '') . ' · total ' . $money($trade['total_price'] ?? 0)) ?></p></article><?php endforeach; ?></div><?php endif; ?>
    </section>

    <section class="arena-section event-feed">
        <div class="arena-section-header"><div><p class="arena-kicker">Narrativa</p><h2>Feed do jogo</h2></div></div>
        <?php if (empty($recentEvents)): ?><div class="empty-state">O feed ainda está vazio. Os eventos aparecerão conforme as equipes jogarem.</div><?php else: ?><?php foreach ($recentEvents as $event): ?><article class="event-item"><span>Rodada <?= (int)($event['round_number'] ?? 0) ?></span><p><?= $e($event['description'] ?? 'Evento registrado no jogo.') ?></p></article><?php endforeach; ?><?php endif; ?>
    </section>
</main>
