<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$team = $team ?? [];
$estimatedWealth = (float)($team['cash_balance'] ?? 0) + ((float)($team['btc_balance'] ?? 0) * 100) + ((int)($team['nft_balance'] ?? 0) * 2000) + ((int)($team['pool_shares'] ?? 0) * 2000);
$roundOpen = ($roundState['status'] ?? 'open') === 'open';
$actionLabel = static fn($a) => [
    'deposit_nft' => 'Depositar NFT na piscina',
    'withdraw_nft_btc' => 'Retirar NFT pagando 11 BTC',
    'withdraw_nft_cash' => 'Retirar NFT pagando R$ 2.000',
    'pass' => 'Passar a vez',
][$a] ?? (string)$a;
?>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Painel da Equipe</p>
    <h1><?= $e($game['title'] ?? 'Jogo') ?></h1>
    <p><strong>Código do jogo:</strong> <?= $e($game['invite_code'] ?? '') ?></p>
    <p><strong>Status do jogo:</strong> <?= $e($game['status'] ?? '') ?></p>
    <p><strong>Você está na Rodada <?= (int)($game['current_round'] ?? 1) ?>.</strong></p>
    <?php if (!empty($message)): ?>
        <p><?= $e($message) ?></p>
    <?php else: ?>
        <h2>Bloco 1: Situação da equipe — <?= $e($team['name'] ?? 'Equipe') ?></h2>
        <div class="liquidity-stat-grid team-status-panel">
            <article class="liquidity-stat"><span>Caixa em reais</span><strong><?= $money($team['cash_balance'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>BTC</span><strong><?= $e($team['btc_balance'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>NFTs em mãos</span><strong><?= (int)($team['nft_balance'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>Cotas da piscina</span><strong><?= (int)($team['pool_shares'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>Status da equipe</span><strong><?= $e($team['status'] ?? 'active') ?></strong></article>
            <article class="liquidity-stat"><span>Patrimônio estimado</span><strong><?= $money($estimatedWealth) ?></strong></article>
        </div>
        <p><strong>Ação da rodada:</strong> <?= !empty($hasActed) ? 'já enviada' : ($roundOpen ? 'pendente' : 'rodada encerrada') ?></p>
        <p><strong>Última ação realizada:</strong> <?= !empty($lastAction) ? $e($actionLabel($lastAction['action_type']) . ' — rodada ' . $lastAction['round_number']) : 'Nenhuma ação registrada ainda.' ?></p>
        <p><strong>Último dividendo recebido:</strong> <?= !empty($lastDividend) ? $e($lastDividend['description']) : 'Nenhum dividendo recebido ainda.' ?></p>
        <p class="action-card">Quando o professor encerrar a rodada, todas as equipes pagam R$ 100 de taxa. Se houver NFTs depositados na piscina, as cotas recebem dividendos.</p>
        <p><a href="/liquidity/games/<?= (int)$game['id'] ?>/arena">Arena pública</a></p>
        <section class="liquidity-card team-panel-block">
            <h2>Bloco 2: O que você quer fazer agora?</h2>
            <div class="quick-actions"><a class="button" href="#acoes">Fazer ação individual</a><a class="button button-secondary" href="#mercado">Negociar com outra equipe</a><a class="button button-secondary" href="#propostas">Ver propostas recebidas</a></div>
        </section>
        <section class="liquidity-card team-panel-block" id="acoes">
            <h2>Bloco 3: Ações individuais</h2>
            <p>Escolha uma ação. Sua equipe só pode concluir uma ação por rodada.</p>
            <?php if (!$roundOpen): ?>
                <p><strong>A rodada atual já foi encerrada. Aguarde o início da próxima rodada.</strong></p>
            <?php elseif (!empty($hasActed)): ?>
                <p><strong>Sua equipe já realizou uma ação nesta rodada.</strong></p>
            <?php endif; ?>
            <?php $actions = [
                ['deposit_nft', 'Depositar NFT na piscina', 'Você informa a quantidade de NFTs, recebe 10 BTC e ganha 1 cota por NFT depositado.'],
                ['withdraw_nft_btc', 'Retirar NFT pagando 11 BTC', 'Você usa 1 cota e paga 11 BTC para recuperar 1 NFT.'],
                ['withdraw_nft_cash', 'Retirar NFT pagando R$ 2.000', 'Você usa 1 cota e paga R$ 2.000 para recuperar 1 NFT.'],
                ['pass', 'Passar a vez', 'Você não altera seus recursos, mas registra sua decisão da rodada.'],
            ]; ?>
            <div class="liquidity-control-grid">
                <?php foreach ($actions as [$type, $label, $help]): ?>
                    <form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/my-team/action" class="action-card">
                        <?= \App\Core\Csrf::input() ?>
                        <input type="hidden" name="action_type" value="<?= $e($type) ?>">
                        <h3><?= $e($label) ?></h3>
                        <p><?= $e($help) ?></p>
                        <?php if ($type !== 'pass'): ?><label>Quantidade <input type='number' name='quantity' min='1' step='1' value='1'></label><?php endif; ?>
                        <button type="submit" <?= (!$roundOpen || !empty($hasActed)) ? 'disabled' : '' ?>><?= $e($label) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>

            <section class="liquidity-card team-panel-block" id="mercado">
                <h2>Bloco 4: Mercado entre equipes</h2>
                <p>Comprar BTC: escolha uma equipe vendedora, informe a quantidade de BTC e o preço unitário em reais. As demais ações seguem a mesma lógica bilateral e só executam após aprovação da contraparte.</p>
                <?php $marketActions = [
                    ['buy','btc','Comprar BTC de outra equipe'], ['sell','btc','Vender BTC para outra equipe'],
                    ['buy','nft','Comprar NFT de outra equipe'], ['sell','nft','Vender NFT para outra equipe'],
                    ['buy','share','Comprar cota de outra equipe'], ['sell','share','Vender cota para outra equipe'],
                ]; ?>
                <div class="liquidity-control-grid">
                <?php foreach ($marketActions as [$act,$asset,$label]): ?>
                    <form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/my-team/trades" class="action-card">
                        <?= \App\Core\Csrf::input() ?>
                        <input type="hidden" name="action_type" value="<?= $e($act) ?>"><input type="hidden" name="asset_type" value="<?= $e($asset) ?>">
                        <h3><?= $e($label) ?></h3>
                        <label>Contraparte <select name="counterparty_team_id" required><?php foreach (($teams ?? []) as $other): ?><option value="<?= (int)$other['id'] ?>"><?= $e($other['name']) ?></option><?php endforeach; ?></select></label>
                        <label>Quantidade <input type="number" name="quantity" min="0.0001" step="0.0001" value="1" required></label>
                        <label>Preço unitário (R$) <input type="number" name="unit_price" min="0.01" step="0.01" value="100" required></label>
                        <button type="submit" <?= (!$roundOpen || !empty($hasActed) || empty($teams)) ? 'disabled' : '' ?>>Enviar proposta</button>
                    </form>
                <?php endforeach; ?>
                </div>
            </section>
            <section class="liquidity-card team-panel-block" id="propostas"><h2>Bloco 5: Propostas recebidas</h2><?php if (empty($receivedProposals)): ?><p>Nenhuma proposta recebida.</p><?php else: ?><div class="liquidity-table-wrap"><table class="liquidity-table"><tr><th>Proponente</th><th>Tipo</th><th>Qtd.</th><th>Total</th><th>Status</th><th>Ações</th></tr><?php foreach ($receivedProposals as $p): ?><tr><td><?= $e($p['proposer_name'] ?? '') ?></td><td><?= $e($p['action_type'].' '.$p['asset_type']) ?></td><td><?= $e($p['quantity']) ?></td><td><?= $money($p['total_price']) ?></td><td><?= $e($p['status']) ?></td><td><?php if ($p['status']==='pending_counterparty'): ?><form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/trades/<?= (int)$p['id'] ?>/approve" style="display:inline"><?= \App\Core\Csrf::input() ?><button>Aprovar transação</button></form> <form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/trades/<?= (int)$p['id'] ?>/reject" style="display:inline"><?= \App\Core\Csrf::input() ?><button>Rejeitar transação</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div><?php endif; ?></section>
            <section class="liquidity-card team-panel-block"><h2>Minhas propostas enviadas</h2><?php if (empty($sentProposals)): ?><p>Nenhuma proposta enviada.</p><?php else: ?><div class="liquidity-table-wrap"><table class="liquidity-table"><tr><th>Tipo</th><th>Contraparte</th><th>Qtd.</th><th>Unitário</th><th>Total</th><th>Status</th></tr><?php foreach ($sentProposals as $p): ?><tr><td><?= $e($p['action_type'].' '.$p['asset_type']) ?></td><td><?= $e($p['counterparty_name'] ?? '') ?></td><td><?= $e($p['quantity']) ?></td><td><?= $money($p['unit_price']) ?></td><td><?= $money($p['total_price']) ?></td><td><?= $e($p['status']) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?></section>
            <section class="liquidity-card team-panel-block" id="historico"><h2>Bloco 6: Histórico</h2><?php if (empty($tradeHistory)): ?><p>Nenhuma transação no histórico.</p><?php else: ?><div class="liquidity-table-wrap"><table class="liquidity-table"><tr><th>Proponente</th><th>Contraparte</th><th>Tipo</th><th>Total</th><th>Status</th></tr><?php foreach ($tradeHistory as $p): ?><tr><td><?= $e($p['proposer_name'] ?? '') ?></td><td><?= $e($p['counterparty_name'] ?? '') ?></td><td><?= $e($p['action_type'].' '.$p['asset_type']) ?></td><td><?= $money($p['total_price']) ?></td><td><?= $e($p['status']) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?></section>

    <?php endif; ?>
    <p><a href="/liquidity/my-games">Meus jogos</a></p>
</section>
