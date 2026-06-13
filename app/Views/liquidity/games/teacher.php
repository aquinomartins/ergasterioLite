<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$csrf = \App\Core\Csrf::input();
$actionLabel = static fn($a) => ['deposit_nft'=>'Depositou NFT','withdraw_nft_btc'=>'Retirou NFT com BTC','withdraw_nft_cash'=>'Retirou NFT com caixa','pass'=>'Passou a vez'][$a] ?? (string)$a;
$roundStatus = ($currentRoundState['status'] ?? 'open') === 'closed' ? 'encerrada' : 'aberta';
$activeTeams = array_values(array_filter($teams ?? [], static fn($team) => ($team['status'] ?? 'active') === 'active' && (int)($team['is_eliminated'] ?? 0) === 0));
$actedCount = 0;
foreach ($activeTeams as $team) { if (!empty($actedByTeam[(int)$team['id']])) { $actedCount++; } }
$pendingCount = max(0, count($activeTeams) - $actedCount);
?>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Painel do professor</p>
    <h1><?= $e($game['title']) ?></h1>
    <p><strong>Código de convite:</strong> <?= $e($game['invite_code']) ?></p>
    <p><strong>Status:</strong> <?= $e($game['status']) ?> · <strong>Rodada atual:</strong> <?= (int)$game['current_round'] ?></p>
    <p><a href="/liquidity/games/<?= (int)$game['id'] ?>/arena">Arena pública</a> · <a href="/liquidity/my-games">Meus jogos</a></p>
</section>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Controle da rodada</p>
    <h2>Controle da rodada</h2>
    <div class="liquidity-stat-grid">
        <article class="liquidity-stat"><span>Rodada atual</span><strong><?= (int)$game['current_round'] ?></strong></article>
        <article class="liquidity-stat"><span>Status da rodada</span><strong><?= $e($roundStatus) ?></strong></article>
        <article class="liquidity-stat"><span>Equipes ativas</span><strong><?= count($activeTeams) ?></strong></article>
        <article class="liquidity-stat"><span>Ações enviadas</span><strong><?= $actedCount ?></strong></article>
        <article class="liquidity-stat"><span>Ações pendentes</span><strong><?= $pendingCount ?></strong></article>
    </div>
    <p><strong>Aviso:</strong> Ao encerrar a rodada, o sistema cobrará R$ 100 de cada equipe ativa e distribuirá os dividendos da piscina.</p>
    <form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/advance-round" class="liquidity-inline-control action-card">
        <?= $csrf ?>
        <button type="submit" <?= $roundStatus === 'encerrada' ? 'disabled' : '' ?>>Encerrar rodada e avançar</button>
        <?php if ($roundStatus === 'encerrada'): ?><small>Esta rodada já foi encerrada e não pode ser aplicada novamente.</small><?php endif; ?>
    </form>
</section>
<section class="liquidity-card"><h2>Participantes pendentes</h2>
<?php if (!$pendingParticipants): ?><p>Nenhuma solicitação pendente.</p><?php endif; ?>
<?php foreach ($pendingParticipants as $p): ?><article><?= $e($p['display_name'] ?: $p['username'] ?: $p['email']) ?>
<form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/participants/<?= (int)$p['id'] ?>/approve" class="inline-form"><?= $csrf ?><button>Aprovar</button></form>
<form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/participants/<?= (int)$p['id'] ?>/reject" class="inline-form"><?= $csrf ?><button>Rejeitar</button></form></article><?php endforeach; ?></section>
<section class="liquidity-card"><h2>Participantes aprovados</h2><?php if (!$approvedParticipants): ?><p>Nenhum participante aprovado.</p><?php endif; ?><?php foreach ($approvedParticipants as $p): ?><article><?= $e($p['display_name'] ?: $p['username'] ?: $p['email']) ?> — <?= $e($p['team_name'] ?? 'Equipe') ?></article><?php endforeach; ?></section>
<section class="liquidity-card"><h2>Participantes rejeitados</h2><?php if (!$rejectedParticipants): ?><p>Nenhum participante rejeitado.</p><?php endif; ?><?php foreach ($rejectedParticipants as $p): ?><article><?= $e($p['display_name'] ?: $p['username'] ?: $p['email']) ?></article><?php endforeach; ?></section>
<section class="liquidity-card">
    <h2>Equipes criadas</h2>
    <?php if (empty($teams)): ?><p>Nenhuma equipe criada ainda.</p><?php endif; ?>
    <?php foreach (($teams ?? []) as $team): $tid=(int)$team['id']; $last=$lastActionByTeam[$tid] ?? null; ?>
        <article class="action-card">
            <h3><?= $e($team['name']) ?></h3>
            <p>Caixa: <?= $money($team['cash_balance']) ?> · BTC: <?= $e($team['btc_balance']) ?> · NFTs em mãos: <?= (int)$team['nft_balance'] ?> · Cotas: <?= (int)$team['pool_shares'] ?></p>
            <p>Ação da rodada: <strong><?= !empty($actedByTeam[$tid]) ? 'já enviada' : 'pendente' ?></strong></p>
            <p>Última ação: <?= $last ? $e($actionLabel($last['action_type']) . ' — rodada ' . $last['round_number']) : 'nenhuma' ?></p>
        </article>
    <?php endforeach; ?>
</section>
<section class="liquidity-card"><h2>Feed de eventos</h2><?php if (empty($events)): ?><p>Nenhum evento registrado.</p><?php endif; ?><ul class="liquidity-feed-list"><?php foreach (($events ?? []) as $event): ?><li><span>Rodada <?= (int)$event['round_number'] ?></span><p><?= $e($event['description']) ?></p></li><?php endforeach; ?></ul></section>
