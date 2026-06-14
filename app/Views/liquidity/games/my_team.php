<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
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
$actionLabel = static fn($a) => [
    'deposit_nft' => 'Depositar NFT na piscina',
    'withdraw_nft_btc' => 'Retirar NFT pagando 11 BTC',
    'withdraw_nft_cash' => 'Retirar NFT pagando R$ 2.000',
    'market_proposal' => 'Enviar proposta de mercado',
    'pass' => 'Passar a vez',
][$a] ?? (string)$a;
?>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Painel da Equipe</p>
    <h1><?= $e($game['title'] ?? 'Jogo sem título') ?></h1>
    <p><strong>Equipe:</strong> <?= $e($team['name'] ?? 'Equipe não vinculada') ?></p>
    <p><strong>Rodada atual:</strong> <?= $currentRound ?></p>
    <p><strong>Código do jogo:</strong> <?= $e($game['invite_code'] ?? '') ?> · <strong>Status:</strong> <?= $e($game['status'] ?? '') ?></p>
    <p><a href="/liquidity/my-games">Meus jogos</a><?php if (!empty($game['id'])): ?> · <a href="/liquidity/games/<?= (int)$game['id'] ?>/arena">Arena pública</a><?php endif; ?></p>

    <?php if (!empty($message)): ?>
        <p class="state-message"><strong><?= $e($message) ?></strong></p>
    <?php else: ?>
        <section class="liquidity-card team-panel-block">
            <h2>Situação da equipe</h2>
            <div class="liquidity-stat-grid team-status-panel">
                <article class="liquidity-stat"><span>Caixa</span><strong><?= $money($team['cash_balance'] ?? 0) ?></strong></article>
                <article class="liquidity-stat"><span>BTC</span><strong><?= $e($team['btc_balance'] ?? 0) ?></strong></article>
                <article class="liquidity-stat"><span>NFTs em mãos</span><strong><?= (int)($team['nft_balance'] ?? 0) ?></strong></article>
                <article class="liquidity-stat"><span>Cotas</span><strong><?= (int)($team['pool_shares'] ?? 0) ?></strong></article>
                <article class="liquidity-stat"><span>Patrimônio estimado</span><strong><?= $money($estimatedWealth) ?></strong></article>
                <article class="liquidity-stat"><span>Ação da rodada</span><strong><?= $hasActionThisRound ? 'já enviada' : ($roundOpen ? 'pendente' : 'rodada encerrada') ?></strong></article>
            </div>
            <p><strong>Última ação:</strong> <?= !empty($lastAction) ? $e($actionLabel($lastAction['action_type'] ?? '') . ' — rodada ' . ($lastAction['round_number'] ?? '-')) : 'Nenhuma ação registrada ainda.' ?></p>
            <p><strong>Último dividendo:</strong> <?= !empty($lastDividend) ? $e($lastDividend['description'] ?? '') : 'Nenhum dividendo recebido ainda.' ?></p>
        </section>

        <section class="liquidity-card team-panel-block" id="propostas">
            <h2>Propostas recebidas</h2>
            <?php if (empty($receivedProposals)): ?>
                <p>Nenhuma proposta recebida.</p>
            <?php else: ?>
                <div class="liquidity-table-wrap"><table class="liquidity-table"><tr><th>Proponente</th><th>Tipo</th><th>Qtd.</th><th>Total</th><th>Status</th><th>Ações</th></tr>
                    <?php foreach ($receivedProposals as $p): $status = $p['status'] ?? 'pending_counterparty'; ?>
                        <tr><td><?= $e($p['proposer_name'] ?? '') ?></td><td><?= $e(($p['action_type'] ?? '') . ' ' . ($p['asset_type'] ?? '')) ?></td><td><?= $e($p['quantity'] ?? 0) ?></td><td><?= $money($p['total_price'] ?? 0) ?></td><td><?= $e($status) ?></td><td><?php if ($status === 'pending_counterparty'): ?><form method="post" action="/liquidity/games/<?= (int)($game['id'] ?? 0) ?>/trades/<?= (int)($p['id'] ?? 0) ?>/approve" style="display:inline"><?= \App\Core\Csrf::input() ?><button>Aprovar transação</button></form> <form method="post" action="/liquidity/games/<?= (int)($game['id'] ?? 0) ?>/trades/<?= (int)($p['id'] ?? 0) ?>/reject" style="display:inline"><?= \App\Core\Csrf::input() ?><button>Rejeitar transação</button></form><?php endif; ?></td></tr>
                    <?php endforeach; ?>
                </table></div>
            <?php endif; ?>
        </section>

        <section class="liquidity-card team-panel-block" id="acoes">
            <h2>Ações individuais</h2>
            <p>Escolha uma ação. Sua equipe só pode concluir uma ação por rodada.</p>
            <?php if (!$roundOpen): ?><p><strong>A rodada atual já foi encerrada. Aguarde o início da próxima rodada.</strong></p><?php elseif ($hasActionThisRound): ?><p><strong>Sua equipe já realizou uma ação nesta rodada.</strong></p><?php endif; ?>
            <?php $actions = [
                ['deposit_nft', 'Depositar NFT na piscina', 'Você informa a quantidade de NFTs, recebe 10 BTC e ganha 1 cota por NFT depositado.'],
                ['withdraw_nft_btc', 'Retirar NFT pagando 11 BTC', 'Você usa 1 cota e paga 11 BTC para recuperar 1 NFT.'],
                ['withdraw_nft_cash', 'Retirar NFT pagando R$ 2.000', 'Você usa 1 cota e paga R$ 2.000 para recuperar 1 NFT.'],
                ['pass', 'Passar a vez', 'Você não altera seus recursos, mas registra sua decisão da rodada.'],
            ]; ?>
            <div class="liquidity-control-grid">
                <?php foreach ($actions as [$type, $label, $help]): ?>
                    <form method="post" action="/liquidity/games/<?= (int)($game['id'] ?? 0) ?>/my-team/action" class="action-card">
                        <?= \App\Core\Csrf::input() ?><input type="hidden" name="action_type" value="<?= $e($type) ?>">
                        <h3><?= $e($label) ?></h3><p><?= $e($help) ?></p>
                        <?php if ($type !== 'pass'): ?><label>Quantidade <input type="number" name="quantity" min="1" step="1" value="1"></label><?php endif; ?>
                        <button type="submit" <?= (!$roundOpen || $hasActionThisRound) ? 'disabled' : '' ?>><?= $e($label) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="liquidity-card team-panel-block" id="mercado">
            <h2>Mercado entre equipes</h2>
            <?php if (empty($availableTeams)): ?><p>Nenhuma outra equipe disponível para negociar.</p><?php endif; ?>
            <?php $marketActions = [['buy','btc','Comprar BTC de outra equipe'], ['sell','btc','Vender BTC para outra equipe'], ['buy','nft','Comprar NFT de outra equipe'], ['sell','nft','Vender NFT para outra equipe'], ['buy','share','Comprar cota de outra equipe'], ['sell','share','Vender cota para outra equipe']]; ?>
            <div class="liquidity-control-grid">
                <?php foreach ($marketActions as [$act,$asset,$label]): ?>
                    <form method="post" action="/liquidity/games/<?= (int)($game['id'] ?? 0) ?>/my-team/trades" class="action-card">
                        <?= \App\Core\Csrf::input() ?><input type="hidden" name="action_type" value="<?= $e($act) ?>"><input type="hidden" name="asset_type" value="<?= $e($asset) ?>">
                        <h3><?= $e($label) ?></h3>
                        <label>Contraparte <select name="counterparty_team_id" required><?php foreach ($availableTeams as $other): ?><option value="<?= (int)($other['id'] ?? 0) ?>"><?= $e($other['name'] ?? 'Equipe') ?></option><?php endforeach; ?></select></label>
                        <label>Quantidade <input type="number" name="quantity" min="0.0001" step="0.0001" value="1" required></label>
                        <label>Preço unitário (R$) <input type="number" name="unit_price" min="0.01" step="0.01" value="100" required></label>
                        <button type="submit" <?= (!$roundOpen || $hasActionThisRound || empty($availableTeams)) ? 'disabled' : '' ?>>Enviar proposta</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="liquidity-card team-panel-block">
            <h2>Minhas propostas enviadas</h2>
            <?php if (empty($sentProposals)): ?><p>Nenhuma proposta enviada.</p><?php else: ?><div class="liquidity-table-wrap"><table class="liquidity-table"><tr><th>Tipo</th><th>Contraparte</th><th>Qtd.</th><th>Unitário</th><th>Total</th><th>Status</th></tr><?php foreach ($sentProposals as $p): ?><tr><td><?= $e(($p['action_type'] ?? '') . ' ' . ($p['asset_type'] ?? '')) ?></td><td><?= $e($p['counterparty_name'] ?? '') ?></td><td><?= $e($p['quantity'] ?? 0) ?></td><td><?= $money($p['unit_price'] ?? 0) ?></td><td><?= $money($p['total_price'] ?? 0) ?></td><td><?= $e($p['status'] ?? 'pending_counterparty') ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
        </section>

        <section class="liquidity-card team-panel-block" id="historico">
            <h2>Eventos/histórico</h2>
            <?php if (empty($recentEvents) && empty($tradeHistory)): ?><p>Nenhum evento registrado.</p><?php endif; ?>
            <?php if (!empty($recentEvents)): ?><div class="liquidity-table-wrap"><table class="liquidity-table"><tr><th>Rodada</th><th>Evento</th><th>Descrição</th></tr><?php foreach ($recentEvents as $event): ?><tr><td><?= (int)($event['round_number'] ?? 0) ?></td><td><?= $e($event['event_type'] ?? '') ?></td><td><?= $e($event['description'] ?? '') ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
            <?php if (empty($recentEvents) && !empty($tradeHistory)): ?><div class="liquidity-table-wrap"><table class="liquidity-table"><tr><th>Proponente</th><th>Contraparte</th><th>Tipo</th><th>Total</th><th>Status</th></tr><?php foreach ($tradeHistory as $p): ?><tr><td><?= $e($p['proposer_name'] ?? '') ?></td><td><?= $e($p['counterparty_name'] ?? '') ?></td><td><?= $e(($p['action_type'] ?? '') . ' ' . ($p['asset_type'] ?? '')) ?></td><td><?= $money($p['total_price'] ?? 0) ?></td><td><?= $e($p['status'] ?? 'pending_counterparty') ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
        </section>
    <?php endif; ?>
</section>
