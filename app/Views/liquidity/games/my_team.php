<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$qty = static fn($v) => rtrim(rtrim(number_format((float)$v, 4, ',', '.'), '0'), ',');
$game = $game ?? [];
$participant = $participant ?? [];
$team = $team ?? [];
$currentRound = (int)($currentRound ?? ($game['current_round'] ?? 1));
$roundState = $roundState ?? ['status' => 'open'];
$roundOpen = ($roundState['status'] ?? 'open') === 'open';
$hasActionThisRound = (bool)($hasActionThisRound ?? ($hasActed ?? false));
$availableTeams = $availableTeams ?? ($teams ?? []);
$receivedProposals = $receivedProposals ?? [];
$sentProposals = $sentProposals ?? [];
$recentEvents = $recentEvents ?? [];
$tradeHistory = $tradeHistory ?? [];
$estimatedWealth = (float)($team['cash_balance'] ?? 0) + ((float)($team['btc_balance'] ?? 0) * 100) + ((int)($team['nft_balance'] ?? 0) * 2000) + ((int)($team['pool_shares'] ?? 0) * 2000);
$statusLabel = static fn($s) => [
    'pending_counterparty' => 'Aguardando resposta',
    'executed' => 'Executada',
    'rejected' => 'Rejeitada',
    'cancelled' => 'Cancelada',
    'master_executed' => 'Executada pelo Master Gold',
    'open' => 'Em andamento',
    'active' => 'Em andamento',
    'finished' => 'Encerrado',
][$s] ?? (string)$s;
$assetLabel = static fn($a) => ['btc' => 'BTC', 'nft' => 'NFT', 'share' => 'cotas'][$a] ?? strtoupper((string)$a);
$operationLabel = static fn($a) => ['buy' => 'Comprar', 'sell' => 'Vender'][$a] ?? (string)$a;
$actionLabel = static fn($a) => [
    'deposit_nft' => 'Depositar NFT na piscina',
    'withdraw_nft_btc' => 'Retirar NFT com BTC',
    'withdraw_nft_cash' => 'Retirar NFT com dinheiro',
    'market_proposal' => 'Enviar proposta de mercado',
    'pass' => 'Passar a vez',
][$a] ?? (string)$a;
$proposalSentence = static function (array $p) use ($e, $qty, $money, $assetLabel): string {
    $teamName = $e($p['proposer_name'] ?? 'Outra equipe');
    $action = ($p['action_type'] ?? '') === 'sell' ? 'quer vender' : 'quer comprar';
    return $teamName . ' ' . $action . ' ' . $e($qty($p['quantity'] ?? 0)) . ' ' . $e($assetLabel($p['asset_type'] ?? '')) . ' da sua equipe por ' . $money($p['unit_price'] ?? 0) . ' cada.';
};
$gameId = (int)($game['id'] ?? 0);
$disabledAction = (!$roundOpen || $hasActionThisRound);
?>
<div class="team-dashboard">
    <header class="team-dashboard-header">
        <div>
            <p class="team-kicker">Painel da Equipe</p>
            <h1 class="team-title"><?= $e($game['title'] ?? 'Jogo sem título') ?></h1>
            <p class="team-subtitle"><?= $e($team['name'] ?? 'Equipe não vinculada') ?> · Rodada <?= $currentRound ?> · <?= $e($statusLabel($game['status'] ?? 'active')) ?></p>
        </div>
        <nav class="team-toolbar" aria-label="Atalhos do painel">
            <a class="button-secondary" href="/liquidity/my-games">Meus jogos</a>
            <?php if ($gameId > 0): ?><a class="button-primary" href="/liquidity/games/<?= $gameId ?>/arena">Arena pública</a><?php endif; ?>
        </nav>
    </header>

    <?php if (!empty($message)): ?>
        <section class="empty-state"><strong><?= $e($message) ?></strong></section>
    <?php else: ?>
        <section class="resource-grid" aria-label="Recursos da equipe">
            <article class="resource-card"><span class="resource-label">Caixa</span><strong class="resource-value"><?= $money($team['cash_balance'] ?? 0) ?></strong><small class="resource-help">Usado para comprar ativos e pagar taxas.</small></article>
            <article class="resource-card"><span class="resource-label">BTC</span><strong class="resource-value"><?= $e($qty($team['btc_balance'] ?? 0)) ?> BTC</strong><small class="resource-help">Usado para retirar NFTs da piscina.</small></article>
            <article class="resource-card"><span class="resource-label">NFTs em mãos</span><strong class="resource-value"><?= (int)($team['nft_balance'] ?? 0) ?></strong><small class="resource-help">Necessário para sobreviver à semifinal.</small></article>
            <article class="resource-card"><span class="resource-label">Cotas</span><strong class="resource-value"><?= (int)($team['pool_shares'] ?? 0) ?></strong><small class="resource-help">Geram dividendos quando a rodada encerra.</small></article>
            <article class="resource-card"><span class="resource-label">Patrimônio estimado</span><strong class="resource-value"><?= $money($estimatedWealth) ?></strong><small class="resource-help">Estimativa para acompanhar o ranking.</small></article>
        </section>

        <section class="decision-alert <?= $hasActionThisRound ? 'decision-alert--done' : 'decision-alert--pending' ?>">
            <div><h2><?= $hasActionThisRound ? 'Ação da rodada enviada.' : 'Sua equipe ainda precisa decidir.' ?></h2>
            <p><?= $hasActionThisRound ? 'Sua equipe já concluiu a decisão desta rodada. Aguarde o professor encerrar a rodada.' : 'Escolha uma ação individual ou envie uma proposta de mercado. Sua equipe só pode concluir uma ação por rodada.' ?></p></div>
            <span class="status-badge <?= $hasActionThisRound ? 'status-approved' : '' ?>"><?= $hasActionThisRound ? 'Concluída' : ($roundOpen ? 'Pendente' : 'Rodada encerrada') ?></span>
        </section>

        <section class="action-nav-grid" aria-label="Navegação interna">
            <article class="action-nav-card"><h3>Ação individual</h3><p>Depositar, retirar NFT ou passar a vez.</p><a href="#acoes">Ir para ações</a></article>
            <article class="action-nav-card"><h3>Mercado entre equipes</h3><p>Comprar ou vender BTC, NFT e cotas com outra equipe.</p><a href="#mercado">Ir para mercado</a></article>
            <article class="action-nav-card"><h3>Propostas recebidas</h3><p>Aprove ou rejeite propostas enviadas por outras equipes.</p><a href="#propostas">Ver propostas</a></article>
        </section>

        <section class="dashboard-section" id="propostas">
            <div class="section-header"><div><p class="team-kicker">Decisão imediata</p><h2>Propostas recebidas</h2></div><p>Avalie as propostas enviadas por outras equipes.</p></div>
            <?php if (empty($receivedProposals)): ?><div class="empty-state">Você não tem propostas pendentes.</div><?php else: ?>
                <div class="proposal-grid"><?php foreach ($receivedProposals as $p): $status = $p['status'] ?? 'pending_counterparty'; ?>
                    <article class="proposal-card"><p class="proposal-summary"><?= $proposalSentence($p) ?></p><dl class="proposal-details"><div><dt>Operação</dt><dd><?= $e($operationLabel($p['action_type'] ?? '')) ?></dd></div><div><dt>Ativo</dt><dd><?= $e($assetLabel($p['asset_type'] ?? '')) ?></dd></div><div><dt>Quantidade</dt><dd><?= $e($qty($p['quantity'] ?? 0)) ?></dd></div><div><dt>Preço unitário</dt><dd><?= $money($p['unit_price'] ?? 0) ?></dd></div><div><dt>Total</dt><dd><?= $money($p['total_price'] ?? 0) ?></dd></div><div><dt>Status</dt><dd><span class="status-badge"><?= $e($statusLabel($status)) ?></span></dd></div></dl><?php if ($status === 'pending_counterparty'): ?><div class="proposal-actions"><form method="post" action="/liquidity/games/<?= $gameId ?>/trades/<?= (int)($p['id'] ?? 0) ?>/approve"><?= \App\Core\Csrf::input() ?><button class="button-primary" type="submit">Aprovar transação</button></form><form method="post" action="/liquidity/games/<?= $gameId ?>/trades/<?= (int)($p['id'] ?? 0) ?>/reject"><?= \App\Core\Csrf::input() ?><button class="button-danger" type="submit">Rejeitar</button></form></div><?php endif; ?></article>
                <?php endforeach; ?></div>
            <?php endif; ?>
        </section>

        <section class="dashboard-section" id="acoes">
            <div class="section-header"><div><p class="team-kicker">Decisão da rodada</p><h2>Ações individuais</h2></div><p>Escolha uma ação direta da sua equipe.</p></div>
            <?php if (!$roundOpen): ?><div class="empty-state">A rodada atual já foi encerrada. Aguarde o início da próxima rodada.</div><?php elseif ($hasActionThisRound): ?><div class="empty-state">Sua ação da rodada já foi enviada.</div><?php endif; ?>
            <?php $actions = [
                ['deposit_nft', 'Depositar NFT', 'Você entrega NFT para a piscina, recebe BTC e ganha cotas.', 'Para cada 1 NFT: +10 BTC e +1 cota.', 'Depositar NFT'],
                ['withdraw_nft_btc', 'Retirar NFT com BTC', 'Você usa cotas e paga BTC para recuperar NFT da piscina.', 'Para cada 1 NFT: -11 BTC, -1 cota, +1 NFT.', 'Retirar com BTC'],
                ['withdraw_nft_cash', 'Retirar NFT com dinheiro', 'Você usa cotas e paga R$ 2.000 para recuperar NFT da piscina.', 'Para cada 1 NFT: -R$ 2.000, -1 cota, +1 NFT.', 'Retirar com dinheiro'],
                ['pass', 'Passar a vez', 'Registra que sua equipe decidiu não agir nesta rodada.', 'Não altera caixa, BTC, NFT ou cotas.', 'Passar a vez'],
            ]; ?>
            <div class="action-grid"><?php foreach ($actions as [$type, $label, $help, $effect, $button]): ?>
                <form method="post" action="/liquidity/games/<?= $gameId ?>/my-team/action" class="action-card"><?= \App\Core\Csrf::input() ?><input type="hidden" name="action_type" value="<?= $e($type) ?>"><h3><?= $e($label) ?></h3><p><?= $e($help) ?></p><strong class="effect-pill"><?= $e($effect) ?></strong><?php if ($type !== 'pass'): ?><label>Quantidade <input type="number" name="quantity" min="1" step="1" value="1"></label><?php endif; ?><button class="button-primary" type="submit" <?= $disabledAction ? 'disabled' : '' ?>><?= $e($button) ?></button></form>
            <?php endforeach; ?></div>
        </section>

        <section class="dashboard-section" id="mercado">
            <div class="section-header"><div><p class="team-kicker">Negociação</p><h2>Mercado entre equipes</h2></div><p>Envie uma proposta para outra equipe. A transação só será executada se a outra equipe aprovar.</p></div>
            <?php if (empty($availableTeams)): ?><div class="empty-state">Não há outras equipes disponíveis para negociar.</div><?php endif; ?>
            <?php $marketGroups = ['BTC' => [['buy','btc','Comprar BTC'], ['sell','btc','Vender BTC']], 'NFT' => [['buy','nft','Comprar NFT'], ['sell','nft','Vender NFT']], 'Cotas' => [['buy','share','Comprar cotas'], ['sell','share','Vender cotas']]]; ?>
            <div class="market-grid"><?php foreach ($marketGroups as $group => $items): ?><article class="market-group"><h3><?= $e($group) ?></h3><?php foreach ($items as [$act,$asset,$label]): ?><form method="post" action="/liquidity/games/<?= $gameId ?>/my-team/trades" class="market-form"><?= \App\Core\Csrf::input() ?><input type="hidden" name="action_type" value="<?= $e($act) ?>"><input type="hidden" name="asset_type" value="<?= $e($asset) ?>"><h4><?= $e($label) ?></h4><label>Equipe contraparte <select name="counterparty_team_id" required><?php foreach ($availableTeams as $other): ?><option value="<?= (int)($other['id'] ?? 0) ?>"><?= $e($other['name'] ?? 'Equipe') ?></option><?php endforeach; ?></select></label><label>Quantidade <input type="number" name="quantity" min="0.0001" step="0.0001" value="1" required></label><label>Preço unitário (R$) <input type="number" name="unit_price" min="0.01" step="0.01" value="100" required></label><button class="button-secondary" type="submit" <?= (!$roundOpen || $hasActionThisRound || empty($availableTeams)) ? 'disabled' : '' ?>>Enviar proposta</button></form><?php endforeach; ?></article><?php endforeach; ?></div>
        </section>

        <section class="dashboard-section"><div class="section-header"><div><p class="team-kicker">Acompanhamento</p><h2>Minhas propostas enviadas</h2></div></div><?php if (empty($sentProposals)): ?><div class="empty-state">Você ainda não enviou propostas.</div><?php else: ?><div class="liquidity-table-wrap"><table class="liquidity-table team-table"><tr><th>Contraparte</th><th>Operação</th><th>Ativo</th><th>Qtd.</th><th>Unitário</th><th>Total</th><th>Status</th></tr><?php foreach ($sentProposals as $p): ?><tr><td><?= $e($p['counterparty_name'] ?? '') ?></td><td><?= $e($operationLabel($p['action_type'] ?? '')) ?></td><td><?= $e($assetLabel($p['asset_type'] ?? '')) ?></td><td><?= $e($qty($p['quantity'] ?? 0)) ?></td><td><?= $money($p['unit_price'] ?? 0) ?></td><td><?= $money($p['total_price'] ?? 0) ?></td><td><span class="status-badge"><?= $e($statusLabel($p['status'] ?? 'pending_counterparty')) ?></span></td></tr><?php endforeach; ?></table></div><?php endif; ?></section>

        <section class="dashboard-section" id="historico"><div class="section-header"><div><p class="team-kicker">Registro</p><h2>Histórico da equipe</h2></div></div><?php if (empty($recentEvents) && empty($tradeHistory)): ?><div class="empty-state">Ainda não há histórico para esta equipe.</div><?php endif; ?><?php if (!empty($recentEvents)): ?><div class="liquidity-table-wrap"><table class="liquidity-table team-table"><tr><th>Rodada</th><th>Evento</th><th>Descrição</th></tr><?php foreach ($recentEvents as $event): ?><tr><td><?= (int)($event['round_number'] ?? 0) ?></td><td><?= $e($actionLabel($event['event_type'] ?? '')) ?></td><td><?= $e($event['description'] ?? '') ?></td></tr><?php endforeach; ?></table></div><?php endif; ?><?php if (empty($recentEvents) && !empty($tradeHistory)): ?><div class="liquidity-table-wrap"><table class="liquidity-table team-table"><tr><th>Proponente</th><th>Contraparte</th><th>Operação</th><th>Total</th><th>Status</th></tr><?php foreach ($tradeHistory as $p): ?><tr><td><?= $e($p['proposer_name'] ?? '') ?></td><td><?= $e($p['counterparty_name'] ?? '') ?></td><td><?= $e($operationLabel($p['action_type'] ?? '') . ' ' . $assetLabel($p['asset_type'] ?? '')) ?></td><td><?= $money($p['total_price'] ?? 0) ?></td><td><?= $e($statusLabel($p['status'] ?? 'pending_counterparty')) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?></section>
    <?php endif; ?>
</div>
