<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$qty = static fn($v) => rtrim(rtrim(number_format((float)$v, 4, ',', '.'), '0'), ',');
$game = $game ?? [];
$team = $team ?? [];
$participant = $participant ?? [];
$currentRound = (int)($currentRound ?? $game['current_round'] ?? 1);
$hasActionThisRound = (bool)($hasActionThisRound ?? $hasActed ?? false);
$availableTeams = $availableTeams ?? $teams ?? [];
$receivedProposals = $receivedProposals ?? [];
$sentProposals = $sentProposals ?? [];
$recentEvents = $recentEvents ?? [];
$tradeHistory = $tradeHistory ?? [];
$roundOpen = ($roundState['status'] ?? 'open') === 'open';
$estimatedWealth = (float)($team['cash_balance'] ?? 0) + ((float)($team['btc_balance'] ?? 0) * 100) + ((int)($team['nft_balance'] ?? 0) * 2000) + ((int)($team['pool_shares'] ?? 0) * 2000);
$gameId = (int)($game['id'] ?? 0);
$statusLabels = ['pending_counterparty'=>'Aguardando resposta','executed'=>'Executada','rejected'=>'Rejeitada','cancelled'=>'Cancelada','master_executed'=>'Executada pelo Master Gold','pending'=>'Pendente','approved'=>'Aprovada','removed'=>'Removida'];
$teamStatusLabels = ['approved'=>'Aprovada','pending'=>'Aguardando aprovação','rejected'=>'Solicitação recusada','removed'=>'Participação removida','active'=>'Ativa'];
$gameStatusLabels = ['waiting'=>'Aguardando início','active'=>'Em andamento','finished'=>'Encerrado','semifinal'=>'Semifinal','closed'=>'Encerrado'];
$assetLabels = ['btc'=>'BTC','nft'=>'NFT','share'=>'cota'];
$actionWords = ['buy'=>'Comprar','sell'=>'Vender'];
$proposalText = static function (array $p) use ($actionWords, $assetLabels): string {
    $action = $actionWords[(string)($p['action_type'] ?? '')] ?? ucfirst((string)($p['action_type'] ?? 'Proposta'));
    $asset = $assetLabels[(string)($p['asset_type'] ?? '')] ?? (string)($p['asset_type'] ?? 'ativo');
    return trim($action . ' ' . $asset);
};
$actionLabel = static fn($a) => ['deposit_nft'=>'Depositar NFT na piscina','withdraw_nft_btc'=>'Retirar NFT pagando 11 BTC','withdraw_nft_cash'=>'Retirar NFT pagando R$ 2.000','pass'=>'Passar a vez'][$a] ?? 'Ação registrada';
?>
<div class="team-dashboard">
    <section class="liquidity-card team-hero">
        <div>
            <p class="liquidity-eyebrow">Painel da Equipe</p>
            <h1>Painel da Equipe</h1>
            <p class="team-subtitle">Você está jogando em: <strong><?= $e($game['title'] ?? $game['name'] ?? 'Jogo sem título') ?></strong></p>
        </div>
        <div class="quick-actions compact">
            <a class="button button-secondary" href="/liquidity/my-games">Voltar para Meus jogos</a>
            <?php if ($gameId > 0): ?><a class="button" href="/liquidity/games/<?= $gameId ?>/arena">Ver Arena Pública</a><?php endif; ?>
        </div>
        <div class="team-status-grid">
            <article class="team-status-card"><span>Equipe</span><strong><?= $e($team['name'] ?? 'Equipe sem nome') ?></strong></article>
            <article class="team-status-card"><span>Rodada atual</span><strong>Rodada <?= $currentRound ?></strong></article>
            <article class="team-status-card"><span>Status do jogo</span><strong><?= $e($gameStatusLabels[(string)($game['status'] ?? '')] ?? $game['status'] ?? 'Indefinido') ?></strong></article>
            <article class="team-status-card"><span>Status da equipe</span><strong><?= $e($teamStatusLabels[(string)($participant['status'] ?? $team['status'] ?? '')] ?? $participant['status'] ?? $team['status'] ?? 'Indefinido') ?></strong></article>
        </div>
    </section>

    <?php if (!empty($message)): ?>
        <section class="liquidity-card team-section empty-state">
            <h2><?= $e($participant['status'] ?? '') === 'rejected' ? 'Solicitação recusada' : ($e($participant['status'] ?? '') === 'removed' ? 'Participação removida' : 'Aguardando aprovação') ?></h2>
            <p><?= $e($message) ?></p>
            <p>Quando sua participação estiver aprovada, este painel mostrará os recursos, ações e propostas da sua equipe.</p>
        </section>
    <?php else: ?>
        <section class="liquidity-card team-section" id="situacao">
            <h2>Situação da equipe</h2>
            <div class="team-status-grid">
                <article class="team-status-card"><span>Caixa</span><strong><?= $money($team['cash_balance'] ?? 0) ?></strong></article>
                <article class="team-status-card"><span>BTC</span><strong><?= $qty($team['btc_balance'] ?? 0) ?></strong></article>
                <article class="team-status-card"><span>NFTs em mãos</span><strong><?= (int)($team['nft_balance'] ?? 0) ?></strong></article>
                <article class="team-status-card"><span>Cotas da piscina</span><strong><?= (int)($team['pool_shares'] ?? 0) ?></strong></article>
                <article class="team-status-card"><span>Patrimônio estimado</span><strong><?= $money($estimatedWealth) ?></strong></article>
                <article class="team-status-card"><span>Ação da rodada</span><strong><?= $hasActionThisRound ? 'Já enviada' : ($roundOpen ? 'Aguardando decisão' : 'Rodada encerrada') ?></strong></article>
            </div>
            <p class="team-note">Patrimônio estimado usa BTC = R$ 100, NFT = R$ 2.000 e cota = R$ 2.000.</p>
            <?php if (!empty($lastAction)): ?><p>Sua equipe já enviou a ação desta rodada? Última ação registrada: <?= $e($actionLabel($lastAction['action_type'] ?? '') . ' — rodada ' . ($lastAction['round_number'] ?? '-')) ?>.</p><?php endif; ?>
        </section>

        <section class="liquidity-card team-section">
            <h2>O que você quer fazer agora?</h2>
            <div class="team-action-nav">
                <a class="action-card" href="#acoes-individuais"><h3>Fazer ação individual</h3><p>Deposite, retire NFT ou passe a vez.</p></a>
                <a class="action-card" href="#mercado"><h3>Negociar com outra equipe</h3><p>Envie proposta de compra ou venda de BTC, NFT ou cotas.</p></a>
                <a class="action-card" href="#propostas-recebidas"><h3>Responder propostas recebidas</h3><p>Aprove ou rejeite propostas enviadas por outras equipes.</p></a>
            </div>
        </section>

        <section class="liquidity-card team-section" id="propostas-recebidas">
            <h2>Propostas recebidas</h2>
            <?php $pendingReceived = array_values(array_filter($receivedProposals, static fn($p) => ($p['status'] ?? '') === 'pending_counterparty')); ?>
            <?php if (!$pendingReceived): ?><p class="empty-state">Você não tem propostas pendentes no momento.</p><?php else: ?>
                <div class="proposal-list">
                    <?php foreach ($pendingReceived as $p): ?>
                        <article class="proposal-card proposal-pending">
                            <h3><?= $e($proposalText($p)) ?></h3>
                            <p>Equipe proponente: <strong><?= $e($p['proposer_name'] ?? 'Equipe sem nome') ?></strong></p>
                            <p>Quantidade: <strong><?= $qty($p['quantity'] ?? 0) ?></strong> · Preço unitário: <strong><?= $money($p['unit_price'] ?? 0) ?></strong> · Total: <strong><?= $money($p['total_price'] ?? ((float)($p['quantity'] ?? 0) * (float)($p['unit_price'] ?? 0))) ?></strong></p>
                            <form method="post" action="/liquidity/games/<?= $gameId ?>/trades/<?= (int)($p['id'] ?? 0) ?>/approve" class="inline-form"><?= \App\Core\Csrf::input() ?><button>Aprovar transação</button></form>
                            <form method="post" action="/liquidity/games/<?= $gameId ?>/trades/<?= (int)($p['id'] ?? 0) ?>/reject" class="inline-form"><?= \App\Core\Csrf::input() ?><button class="button-secondary">Rejeitar transação</button></form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="liquidity-card team-section" id="acoes-individuais">
            <h2>Ações individuais</h2>
            <p>Escolha uma ação. Sua equipe só pode concluir uma ação por rodada.</p>
            <?php if (!$roundOpen): ?><p class="empty-state">A rodada atual já foi encerrada. Aguarde a próxima rodada.</p><?php elseif ($hasActionThisRound): ?><p class="empty-state">Sua equipe já concluiu uma ação nesta rodada.</p><?php endif; ?>
            <?php $actions = [['deposit_nft','Depositar NFT na piscina','Você entrega NFT para a piscina, recebe BTC e ganha cotas.','Depositar NFT'],['withdraw_nft_btc','Retirar NFT pagando 11 BTC','Você usa cotas e paga BTC para recuperar NFT da piscina.','Retirar pagando BTC'],['withdraw_nft_cash','Retirar NFT pagando R$ 2.000','Você usa cotas e paga dinheiro para recuperar NFT da piscina.','Retirar pagando R$ 2.000'],['pass','Passar a vez','Você não altera seus recursos, mas registra sua decisão da rodada.','Passar a vez']]; ?>
            <div class="liquidity-control-grid">
                <?php foreach ($actions as [$type, $title, $help, $button]): ?>
                    <form method="post" action="/liquidity/games/<?= $gameId ?>/my-team/action" class="action-card">
                        <?= \App\Core\Csrf::input() ?><input type="hidden" name="action_type" value="<?= $e($type) ?>">
                        <h3><?= $e($title) ?></h3><p><?= $e($help) ?></p>
                        <?php if ($type !== 'pass'): ?><label>Quantidade <input type="number" name="quantity" min="1" step="1" value="1" <?= (!$roundOpen || $hasActionThisRound) ? 'disabled' : '' ?> required></label><?php endif; ?>
                        <button type="submit" <?= (!$roundOpen || $hasActionThisRound) ? 'disabled' : '' ?>><?= $e($button) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="liquidity-card team-section" id="mercado">
            <h2>Mercado entre equipes</h2>
            <p>Envie uma proposta para outra equipe. A transação só será executada se a outra equipe aprovar.</p>
            <?php if (!$availableTeams): ?><p class="empty-state">Nenhuma equipe contraparte disponível no momento.</p><?php endif; ?>
            <div class="liquidity-control-grid">
                <?php foreach ([['buy','btc','Comprar BTC'],['sell','btc','Vender BTC'],['buy','nft','Comprar NFT'],['sell','nft','Vender NFT'],['buy','share','Comprar cota'],['sell','share','Vender cota']] as [$act,$asset,$label]): ?>
                    <form method="post" action="/liquidity/games/<?= $gameId ?>/my-team/trades" class="action-card">
                        <?= \App\Core\Csrf::input() ?><input type="hidden" name="action_type" value="<?= $e($act) ?>"><input type="hidden" name="asset_type" value="<?= $e($asset) ?>">
                        <h3><?= $e($label) ?></h3>
                        <label>Equipe contraparte <select name="counterparty_team_id" required <?= (!$availableTeams || !$roundOpen || $hasActionThisRound) ? 'disabled' : '' ?>><?php foreach ($availableTeams as $other): ?><option value="<?= (int)($other['id'] ?? 0) ?>"><?= $e($other['name'] ?? 'Equipe sem nome') ?></option><?php endforeach; ?></select></label>
                        <label>Quantidade <input type="number" name="quantity" min="0.0001" step="0.0001" value="1" <?= (!$roundOpen || $hasActionThisRound) ? 'disabled' : '' ?> required></label>
                        <label>Preço unitário em reais <input type="number" name="unit_price" min="0.01" step="0.01" value="100" <?= (!$roundOpen || $hasActionThisRound) ? 'disabled' : '' ?> required></label>
                        <button type="submit" <?= (!$roundOpen || $hasActionThisRound || !$availableTeams) ? 'disabled' : '' ?>>Enviar proposta</button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="liquidity-card team-section" id="propostas-enviadas">
            <h2>Minhas propostas enviadas</h2>
            <?php if (!$sentProposals): ?><p class="empty-state">Você ainda não enviou propostas.</p><?php else: ?><div class="liquidity-table-wrap"><table class="liquidity-table"><thead><tr><th>Contraparte</th><th>Tipo</th><th>Ativo</th><th>Quantidade</th><th>Preço unitário</th><th>Total</th><th>Status</th></tr></thead><tbody><?php foreach ($sentProposals as $p): ?><tr><td><?= $e($p['counterparty_name'] ?? 'Equipe sem nome') ?></td><td><?= $e($actionWords[(string)($p['action_type'] ?? '')] ?? '-') ?></td><td><?= $e($assetLabels[(string)($p['asset_type'] ?? '')] ?? '-') ?></td><td><?= $qty($p['quantity'] ?? 0) ?></td><td><?= $money($p['unit_price'] ?? 0) ?></td><td><?= $money($p['total_price'] ?? 0) ?></td><td><span class="status-badge proposal-<?= $e($p['status'] ?? 'pending') ?>"><?= $e($statusLabels[(string)($p['status'] ?? '')] ?? $p['status'] ?? 'Indefinido') ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
        </section>

        <section class="liquidity-card team-section" id="historico">
            <h2>Histórico</h2>
            <?php if (!$tradeHistory && !$recentEvents && empty($lastAction)): ?><p class="empty-state">Não há histórico de transações ainda.</p><?php else: ?>
                <?php if (!empty($lastAction)): ?><p>Ação individual recente: <?= $e($actionLabel($lastAction['action_type'] ?? '') . ' — rodada ' . ($lastAction['round_number'] ?? '-')) ?>.</p><?php endif; ?>
                <?php if ($tradeHistory): ?><div class="liquidity-table-wrap"><table class="liquidity-table"><thead><tr><th>Equipe proponente</th><th>Contraparte</th><th>Proposta</th><th>Total</th><th>Status</th></tr></thead><tbody><?php foreach ($tradeHistory as $p): ?><tr><td><?= $e($p['proposer_name'] ?? '-') ?></td><td><?= $e($p['counterparty_name'] ?? '-') ?></td><td><?= $e($proposalText($p)) ?></td><td><?= $money($p['total_price'] ?? 0) ?></td><td><?= $e($statusLabels[(string)($p['status'] ?? '')] ?? $p['status'] ?? 'Indefinido') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
                <?php if ($recentEvents): ?><div class="event-list"><?php foreach ($recentEvents as $event): ?><p><?= $e($event['description'] ?? $event['event_type'] ?? 'Evento recente') ?></p><?php endforeach; ?></div><?php endif; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
