<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); $money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.'); ?>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Arena pública</p>
    <h1><?= $e($game['title'] ?? 'Jogo') ?></h1>
    <p><strong>Rodada atual:</strong> <?= (int)($game['current_round'] ?? 1) ?> · <strong>Status:</strong> <?= $e($game['status'] ?? '') ?></p>
    <p><strong>Total de cotas:</strong> <?= (int)($poolMetrics['total_shares'] ?? 0) ?> · <strong>Valor da piscina:</strong> <?= $money($poolMetrics['pool_value'] ?? 0) ?> · <strong>Dividendo estimado por cota:</strong> <?= $money($poolMetrics['estimated_dividend_per_share'] ?? 0) ?></p>
    <p>Patrimônio estimado usa BTC = R$ 100, NFT = R$ 2.000 e cota = R$ 2.000.</p>
</section>
<section class="liquidity-card">
    <h2>Ranking simples</h2>
    <div class="liquidity-table-wrap"><table class="liquidity-table"><thead><tr><th>#</th><th>Equipe</th><th>Caixa</th><th>BTC</th><th>NFTs</th><th>Cotas</th><th>Patrimônio estimado</th><th>Ação da rodada</th></tr></thead><tbody>
    <?php foreach (($teams ?? []) as $i => $team): $tid=(int)$team['id']; ?><tr><td><?= $i+1 ?></td><td><?= $e($team['name']) ?></td><td><?= $money($team['cash_balance']) ?></td><td><?= $e($team['btc_balance']) ?></td><td><?= (int)$team['nft_balance'] ?></td><td><?= (int)$team['pool_shares'] ?></td><td><?= $money($team['estimated_wealth']) ?></td><td><?= !empty($actedByTeam[$tid]) ? 'já enviada' : 'pendente' ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<section class="liquidity-card"><h2>Feed econômico da rodada</h2><?php if (empty($events)): ?><p>Nenhum evento registrado.</p><?php endif; ?><ul class="liquidity-feed-list"><?php foreach (($events ?? []) as $event): ?><li><span>Rodada <?= (int)$event['round_number'] ?></span><p><?= $e($event['description']) ?></p></li><?php endforeach; ?></ul></section>
