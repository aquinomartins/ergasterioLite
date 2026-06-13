<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); $money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.'); ?>
<section class="liquidity-card arena-scoreboard-header">
    <p class="liquidity-eyebrow">Arena pública</p>
    <h1><?= $e($game['title'] ?? 'Jogo') ?></h1>
    <div class="liquidity-meta-grid">
        <span><strong>Rodada atual:</strong> <?= (int)($game['current_round'] ?? 1) ?></span>
        <span><strong>Status:</strong> <?= $e($game['status'] ?? '') ?></span>
    </div>
</section>
<section class="liquidity-card arena-pool-state">
    <h2>Estado da piscina</h2>
    <div class="liquidity-stat-grid">
        <article class="liquidity-stat"><span>Total de cotas</span><strong><?= (int)($poolMetrics['total_shares'] ?? 0) ?></strong></article>
        <article class="liquidity-stat"><span>Valor da piscina</span><strong><?= $money($poolMetrics['pool_value'] ?? 0) ?></strong></article>
        <article class="liquidity-stat"><span>Dividendo estimado por cota</span><strong><?= $money($poolMetrics['estimated_dividend_per_share'] ?? 0) ?></strong></article>
    </div>
    <p>Patrimônio estimado usa BTC = R$ 100, NFT = R$ 2.000 e cota = R$ 2.000.</p>
</section>
<section class="liquidity-card arena-ranking-card">
    <h2>Ranking</h2>
    <div class="liquidity-table-wrap"><table class="liquidity-table arena-table"><thead><tr><th>Posição</th><th>Equipe</th><th>Patrimônio estimado</th><th>Caixa</th><th>BTC</th><th>NFTs</th><th>Cotas</th><th>Ação da rodada</th></tr></thead><tbody>
    <?php if (empty($teams)): ?><tr><td colspan="8">Nenhuma equipe aprovada ainda.</td></tr><?php endif; ?>
    <?php foreach (($teams ?? []) as $i => $team): $tid=(int)$team['id']; ?><tr><td><strong><?= $i+1 ?>º</strong></td><td><?= $e($team['name']) ?></td><td><strong><?= $money($team['estimated_wealth']) ?></strong></td><td><?= $money($team['cash_balance']) ?></td><td><?= $e($team['btc_balance']) ?></td><td><?= (int)$team['nft_balance'] ?></td><td><?= (int)$team['pool_shares'] ?></td><td><span class="liquidity-badge <?= !empty($actedByTeam[$tid]) ? 'status-qualified' : 'status-pending' ?>"><?= !empty($actedByTeam[$tid]) ? 'ação enviada' : 'aguardando' ?></span></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<section class="liquidity-card arena-feed-card"><h2>Feed narrativo</h2><?php if (empty($events)): ?><p>Nenhum evento registrado.</p><?php endif; ?><ul class="liquidity-feed-list"><?php foreach (($events ?? []) as $event): ?><li><span class="liquidity-event-round">R<?= (int)$event['round_number'] ?></span><p><?= $e($event['description']) ?></p></li><?php endforeach; ?></ul></section>
