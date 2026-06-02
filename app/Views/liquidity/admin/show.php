<?php
$s = $state['session'] ?? [];
$pool = $state['pool'] ?? [];
$ranking = $state['ranking'] ?? [];
$finalRanking = $state['final_ranking'] ?? [];
$feed = $state['feed'] ?? [];
$lastActionByTeam = $lastActionByTeam ?? [];
$currentRoundState = $currentRoundState ?? [];
$actionLabels = [
    'deposit_nft' => 'Depositou NFT',
    'withdraw_nft_btc' => 'Retirou NFT com BTC',
    'withdraw_nft_cash' => 'Retirou NFT com dinheiro',
    'buy_btc' => 'Comprou BTC',
    'sell_btc' => 'Vendeu BTC',
    'buy_nft' => 'Comprou NFT em mãos',
    'sell_nft' => 'Vendeu NFT em mãos',
    'buy_share' => 'Comprou cota',
    'sell_share' => 'Vendeu cota',
    'trade_nft_between_teams' => 'Comprou NFT em mãos',
    'pass' => 'Passou a vez',
];
$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v): string => 'R$ ' . number_format((float)$v, 2, ',', '.');
$percent = static fn($v): string => rtrim(rtrim(number_format(((float)$v) * 100, 2, ',', '.'), '0'), ',') . '%';
$poolTotalValue = (int)($pool['pool_nfts'] ?? 0) * (float)($s['nft_pool_value'] ?? 0);
$estimatedYieldTotal = $poolTotalValue * (float)($s['pool_yield_rate'] ?? 0);
$estimatedYieldPerShare = (int)($pool['total_shares'] ?? 0) > 0 ? $estimatedYieldTotal / (int)($pool['total_shares'] ?? 0) : 0.0;
$totalTeams = count($teams);
$actionableTeamIds = [];
$semifinalClassifiedCount = 0;
$semifinalEliminatedCount = 0;
foreach ($teams as $team) {
    $teamId = (int)($team['id'] ?? 0);
    if (!empty($team['is_eliminated'])) {
        $semifinalEliminatedCount++;
        continue;
    }
    if (!empty($team['qualified_for_final'])) {
        $semifinalClassifiedCount++;
    }
    $actionableTeamIds[] = $teamId;
}
$actedCount = 0;
foreach ($actionableTeamIds as $teamId) {
    if (!empty($actedByTeam[$teamId])) {
        $actedCount++;
    }
}
$actionableTeamsCount = count($actionableTeamIds);
$pendingCount = max(0, $actionableTeamsCount - $actedCount);
$sessionPhase = (string)($s['session_phase'] ?? 'regular');
$finalWasClosed = in_array($sessionPhase, ['final_closed', 'closed'], true) || (string)($s['status'] ?? '') === 'closed';
$semifinalWasEvaluated = in_array($sessionPhase, ['semifinal_evaluated', 'final', 'final_closed', 'closed'], true) || $semifinalClassifiedCount > 0 || $semifinalEliminatedCount > 0;
$finalistCount = count($finalRanking);
$finalWinners = array_values(array_filter($finalRanking, static fn($team): bool => in_array((string)($team['display_status'] ?? $team['final_status'] ?? ''), ['Vencedor', 'Vencedor empatado'], true)));
$finalWinnerText = $finalWinners ? implode(', ', array_map(static fn($team): string => (string)($team['name'] ?? '-'), $finalWinners)) : '';
$phaseLabels = [
    'regular' => 'regular',
    'semifinal' => 'semifinal',
    'semifinal_evaluated' => 'semifinal avaliada',
    'final' => 'final',
    'final_closed' => 'final encerrada',
    'closed' => 'final encerrada',
];
$teamNamesById = [];
foreach ($teams as $team) {
    $teamNamesById[(int)($team['id'] ?? 0)] = (string)($team['name'] ?? '-');
}
$describeLastAction = static function (?array $lastAction) use ($actionLabels, $teamNamesById): string {
    if (!$lastAction) {
        return '—';
    }
    $type = (string)($lastAction['action_type'] ?? '');
    $label = $actionLabels[$type] ?? $type;
    $targetId = (int)($lastAction['target_team_id'] ?? 0);
    if ($targetId > 0 && isset($teamNamesById[$targetId])) {
        $connector = in_array($type, ['buy_btc', 'buy_nft', 'buy_share', 'trade_nft_between_teams'], true) ? ' de ' : ' para ';
        $label .= $connector . $teamNamesById[$targetId];
    }
    return $label ?: '—';
};
$roundStatus = ($finalWasClosed || ($currentRoundState['status'] ?? '') === 'closed') ? 'Encerrada' : (($actionableTeamsCount > 0 && $pendingCount === 0) ? 'Todos já agiram' : 'Em andamento');
?>
<div class="liquidity-dashboard liquidity-admin-page">
    <section class="liquidity-card liquidity-hero-card">
        <div>
            <p class="liquidity-eyebrow">Piscina de Liquidez</p>
            <h1><?= $e($s['name'] ?? 'Sessão') ?></h1>
            <div class="liquidity-meta-grid">
                <span><strong>Código:</strong> <?= $e($s['access_code'] ?? '-') ?></span>
                <span><strong>Rodada:</strong> <?= (int)($s['current_round'] ?? 0) ?>/<?= (int)($s['total_rounds'] ?? 0) ?></span>
                <span><strong>Fase:</strong> <?= $e($phaseLabels[$sessionPhase] ?? $sessionPhase) ?></span>
                <span><strong>Status:</strong> <?= $e($s['status'] ?? '-') ?></span>
            </div>
        </div>

        <div class="liquidity-params-grid" aria-label="Parâmetros principais da piscina">
            <div><span>BTC compra</span><strong><?= $money($s['btc_buy_price'] ?? 0) ?></strong></div>
            <div><span>BTC venda</span><strong><?= $money($s['btc_sell_price'] ?? 0) ?></strong></div>
            <div><span>Venda NFT</span><strong><?= $money($s['nft_sell_price'] ?? 0) ?></strong></div>
            <div><span>Venda cota</span><strong><?= $money($s['share_sell_price'] ?? 0) ?></strong></div>
            <div><span>Taxa por rodada</span><strong><?= $money($s['round_fee'] ?? 0) ?></strong></div>
            <div><span>Valor da NFT na piscina</span><strong><?= $money($s['nft_pool_value'] ?? 0) ?></strong></div>
            <div><span>Rendimento da piscina</span><strong><?= $percent($s['pool_yield_rate'] ?? 0) ?></strong></div>
        </div>
    </section>

    <section class="liquidity-card liquidity-rules-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Regras rápidas</p>
            <h2>Como o jogo funciona</h2>
        </div>
        <ul class="liquidity-rules-list">
            <li>Cada time começa com R$ 1.600 e 1 NFT.</li>
            <li>Cada time pode fazer apenas 1 ação por rodada.</li>
            <li>Depositar NFT gera 10 BTC e 1 cota.</li>
            <li>Retirar NFT custa 11 BTC ou R$ 2.000.</li>
            <li>Ao final da rodada, todos pagam R$ 100.</li>
            <li>A piscina distribui 10% do valor das NFTs depositadas.</li>
            <li>Para passar na semifinal, o time precisa ter 1 NFT em mãos.</li>
            <li>Na final, vence quem tiver mais dinheiro em reais.</li>
        </ul>
    </section>

    <section class="liquidity-card liquidity-round-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Andamento</p>
            <h2>Estado da Rodada</h2>
        </div>
        <div class="liquidity-stat-grid">
            <article class="liquidity-stat">
                <span>Rodada atual</span>
                <strong><?= (int)($s['current_round'] ?? 0) ?>/<?= (int)($s['total_rounds'] ?? 0) ?></strong>
            </article>
            <article class="liquidity-stat liquidity-stat-success">
                <span>Times que já agiram</span>
                <strong><?= $actedCount ?></strong>
            </article>
            <article class="liquidity-stat liquidity-stat-warning">
                <span>Times aguardando ação</span>
                <strong><?= $pendingCount ?></strong>
            </article>
            <article class="liquidity-stat <?= $pendingCount === 0 && $totalTeams > 0 ? 'liquidity-stat-success' : '' ?>">
                <span>Status da rodada</span>
                <strong><?= $e($roundStatus) ?></strong>
            </article>
        </div>
        <?php if ($actionableTeamsCount > 0 && $pendingCount === 0): ?>
            <p class="round-ready-message">Todos os times já agiram. A rodada pode ser encerrada.</p>
        <?php endif; ?>
        <div class="liquidity-table-wrap liquidity-round-state-table">
            <table class="liquidity-table">
                <thead><tr><th>Equipe</th><th>Ação usada?</th><th>Última ação</th></tr></thead>
                <tbody>
                <?php foreach ($teams as $team): ?>
                    <?php
                    $teamId = (int)$team['id'];
                    $hasTeamActed = !empty($actedByTeam[$teamId]);
                    $lastAction = $lastActionByTeam[$teamId] ?? null;
                    $lastActionLabel = $describeLastAction($lastAction);
                    ?>
                    <tr>
                        <td><strong><?= $e($team['name'] ?? '-') ?></strong></td>
                        <td><span class="liquidity-badge <?= $hasTeamActed ? 'status-qualified' : 'status-pending' ?>"><?= $hasTeamActed ? 'Sim' : 'Não' ?></span></td>
                        <td><?= $e($lastActionLabel) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/advance-round" class="liquidity-inline-control action-card"><?= \App\Core\Csrf::input() ?><button type="submit" <?= $finalWasClosed ? 'disabled' : '' ?>>Encerrar rodada</button><?php if ($finalWasClosed): ?><small>A final já foi encerrada. A rodada não pode mais ser alterada.</small><?php endif; ?></form>
    </section>

    <section class="liquidity-card pool-state-card <?= $e('pool-status-' . ($pool['status'] ?? 'empty')) ?>">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Reserva coletiva</p>
            <h2>Estado da Piscina</h2>
        </div>
        <div class="liquidity-stat-grid liquidity-pool-stats">
            <article class="liquidity-stat"><span>NFTs na piscina</span><strong><?= (int)($pool['pool_nfts'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>Cotas</span><strong><?= (int)($pool['total_shares'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>Valor total da piscina</span><strong><?= $money($poolTotalValue) ?></strong></article>
            <article class="liquidity-stat"><span>Rendimento total estimado</span><strong><?= $money($estimatedYieldTotal) ?></strong></article>
            <article class="liquidity-stat"><span>Rendimento por cota estimado</span><strong><?= $money($estimatedYieldPerShare) ?></strong></article>
            <article class="liquidity-stat"><span>Valor bloqueado</span><strong><?= $money($poolTotalValue) ?></strong></article>
        </div>
    </section>

    <section class="liquidity-card ranking-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Acompanhamento</p>
            <h2>Ranking geral</h2>
        </div>
        <p>Ranking informativo por patrimônio estimado. Não define o vencedor da final.</p>
        <div class="liquidity-table-wrap">
            <table class="liquidity-table">
                <thead>
                <tr>
                    <th>Posição</th>
                    <th>Equipe</th>
                    <th>Caixa R$</th>
                    <th>BTC</th>
                    <th>NFTs em mãos</th>
                    <th>Cotas</th>
                    <th>Patrimônio estimado</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$ranking): ?>
                    <tr><td colspan="8">Nenhuma equipe cadastrada ainda.</td></tr>
                <?php endif; ?>
                <?php foreach ($ranking as $row): ?>
                    <?php
                    $generalStatus = (string)($row['display_status'] ?? 'Em jogo');
                    $generalStatusClass = in_array($generalStatus, ['Vencedor', 'Vencedor empatado', 'Classificado para a final', 'Finalista'], true)
                        ? 'status-qualified'
                        : ($generalStatus === 'Eliminado na semifinal' ? 'status-eliminated' : 'status-active');
                    ?>
                    <tr>
                        <td><strong><?= (int)($row['general_position'] ?? 0) ?>º</strong></td>
                        <td><?= $e($row['name'] ?? '-') ?></td>
                        <td><?= $money($row['cash_balance'] ?? 0) ?></td>
                        <td><?= number_format((float)($row['btc_balance'] ?? 0), 2, ',', '.') ?></td>
                        <td><?= (int)($row['nft_balance'] ?? 0) ?></td>
                        <td><?= (int)($row['pool_shares'] ?? 0) ?></td>
                        <td><?= $money($row['estimated_wealth'] ?? $row['score'] ?? 0) ?></td>
                        <td><span class="liquidity-badge <?= $generalStatusClass ?>"><?= $e($generalStatus) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="liquidity-card liquidity-semifinal-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Critério eliminatório</p>
            <h2>Semifinal</h2>
        </div>
        <p><strong>Para passar na semifinal, o time precisa ter pelo menos 1 NFT em mãos.</strong><br>NFT dentro da piscina não conta.</p>
        <div class="liquidity-stat-grid">
            <article class="liquidity-stat liquidity-stat-success"><span>Classificados</span><strong><?= $semifinalClassifiedCount ?></strong></article>
            <article class="liquidity-stat liquidity-stat-warning"><span>Eliminados</span><strong><?= $semifinalEliminatedCount ?></strong></article>
            <article class="liquidity-stat"><span>Status</span><strong><?= $semifinalWasEvaluated ? 'Avaliada' : 'Pendente' ?></strong></article>
        </div>
        <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/evaluate-semifinal" class="liquidity-inline-control action-card">
            <?= \App\Core\Csrf::input() ?>
            <button type="submit">Avaliar semifinal</button>
        </form>
        <div class="liquidity-table-wrap">
            <table class="liquidity-table">
                <thead><tr><th>Equipe</th><th>NFTs em mãos</th><th>Resultado</th></tr></thead>
                <tbody>
                <?php foreach ($teams as $team): ?>
                    <?php
                    $nftsInHand = (int)($team['nft_balance'] ?? 0);
                    $resultText = 'Em jogo';
                    $resultClass = 'status-active';
                    if (!empty($team['is_eliminated'])) {
                        $resultText = 'Eliminado';
                        $resultClass = 'status-eliminated';
                    } elseif (!empty($team['qualified_for_final'])) {
                        $resultText = 'Classificado';
                        $resultClass = 'status-qualified';
                    } elseif ($semifinalWasEvaluated) {
                        $resultText = $nftsInHand >= 1 ? 'Classificado' : 'Eliminado';
                        $resultClass = $nftsInHand >= 1 ? 'status-qualified' : 'status-eliminated';
                    }
                    ?>
                    <tr>
                        <td><strong><?= $e($team['name'] ?? '-') ?></strong></td>
                        <td><?= $nftsInHand ?></td>
                        <td><span class="liquidity-badge <?= $resultClass ?>"><?= $e($resultText) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="liquidity-card liquidity-final-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Critério definitivo</p>
            <h2>Final</h2>
        </div>
        <p><strong>Na final, vence o time classificado que tiver mais dinheiro em reais.</strong><br>BTC, NFT e cotas não contam como critério de vitória.</p>
        <div class="liquidity-stat-grid">
            <article class="liquidity-stat liquidity-stat-success"><span>Finalistas</span><strong><?= $finalistCount ?></strong></article>
            <article class="liquidity-stat"><span>Status</span><strong><?= $finalWasClosed ? 'Encerrada' : 'Pendente' ?></strong></article>
            <article class="liquidity-stat <?= $finalWinners ? 'liquidity-stat-success' : '' ?>"><span>Vencedor</span><strong><?= $finalWinnerText !== '' ? $e($finalWinnerText) : '—' ?></strong></article>
        </div>
        <?php if (!$semifinalWasEvaluated): ?>
            <p class="warning-text">A semifinal ainda não foi avaliada. O ranking da final será exibido após a definição dos finalistas.</p>
        <?php endif; ?>
        <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/close-final" class="liquidity-inline-control action-card">
            <?= \App\Core\Csrf::input() ?>
            <button type="submit" <?= $finalWasClosed ? 'disabled' : '' ?>>Encerrar final</button>
            <?php if ($finalWasClosed): ?><small>A final já foi encerrada.</small><?php endif; ?>
        </form>
        <h3>Ranking da final</h3>
        <p>Na final, vence o time classificado que tiver mais dinheiro em reais.</p>
        <?php if ($semifinalWasEvaluated): ?>
            <p><strong><?= $finalWasClosed ? 'Resultado final.' : 'Ranking parcial da final por caixa em reais.' ?></strong></p>
        <?php endif; ?>
        <div class="liquidity-table-wrap">
            <table class="liquidity-table">
                <thead><tr><th>Posição</th><th>Equipe</th><th>Caixa R$</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (!$semifinalWasEvaluated): ?>
                    <tr><td colspan="4">A semifinal ainda não foi avaliada. O ranking da final será exibido após a definição dos finalistas.</td></tr>
                <?php elseif (!$finalRanking): ?>
                    <tr><td colspan="4">Nenhum finalista definido ainda.</td></tr>
                <?php endif; ?>
                <?php foreach ($finalRanking as $row): ?>
                    <tr>
                        <td><strong><?= (int)($row['final_position'] ?? 0) ?>º</strong></td>
                        <td><?= $e($row['name'] ?? '-') ?></td>
                        <td><?= $money($row['final_cash_score'] ?? $row['cash_balance'] ?? 0) ?></td>
                        <td><span class="liquidity-badge <?= in_array((string)($row['display_status'] ?? ''), ['Vencedor', 'Vencedor empatado'], true) ? 'status-qualified' : 'status-active' ?>"><?= $e($row['display_status'] ?? 'Classificado para a final') ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="liquidity-card liquidity-teams-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Participantes</p>
            <h2>Equipes</h2>
        </div>
        <div class="liquidity-table-wrap">
            <table class="liquidity-table">
                <thead>
                <tr>
                    <th>Equipe</th>
                    <th>Caixa R$</th>
                    <th>BTC</th>
                    <th>NFTs em mãos</th>
                    <th>NFTs na piscina</th>
                    <th>Cotas</th>
                    <th>Ação usada?</th>
                    <th>Status</th>
                    <th>Payoff</th>
                    <th>Última ação da rodada</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($teams as $team): ?>
                    <?php
                    $score = (float)$team['cash_balance'] + ((float)$team['btc_balance'] * (float)$s['btc_sell_price']) + ((int)$team['nft_balance'] * (float)$s['nft_sell_price']) + ((int)$team['pool_shares'] * (float)$s['share_sell_price']);
                    $teamStatus = 'Em jogo';
                    $teamStatusClass = 'status-active';
                    if (!empty($team['final_status'])) {
                        $teamStatus = (string)$team['final_status'];
                        $teamStatusClass = in_array($teamStatus, ['Vencedor', 'Vencedor empatado'], true) ? 'status-qualified' : ($teamStatus === 'Eliminado na semifinal' ? 'status-eliminated' : 'status-active');
                    } elseif (!empty($team['is_eliminated'])) {
                        $teamStatus = 'Eliminado na semifinal';
                        $teamStatusClass = 'status-eliminated';
                    } elseif (!empty($team['qualified_for_final'])) {
                        $teamStatus = 'Classificado para a final';
                        $teamStatusClass = 'status-qualified';
                    }
                    $teamPoolNfts = (int)($team['pool_shares'] ?? 0);
                    $teamId = (int)$team['id'];
                    $hasTeamActed = !empty($actedByTeam[$teamId]);
                    $lastAction = $lastActionByTeam[$teamId] ?? null;
                    $lastActionLabel = $describeLastAction($lastAction);
                    ?>
                    <tr>
                        <td><strong><?= $e($team['name']) ?></strong></td>
                        <td><?= $money($team['cash_balance']) ?></td>
                        <td><?= number_format((float)$team['btc_balance'], 2, ',', '.') ?></td>
                        <td><?= (int)$team['nft_balance'] ?></td>
                        <td><?= $teamPoolNfts ?></td>
                        <td><?= (int)$team['pool_shares'] ?></td>
                        <td><span class="liquidity-badge <?= $hasTeamActed ? 'status-qualified' : 'status-pending' ?>"><?= $hasTeamActed ? 'Sim' : 'Não' ?></span></td>
                        <td><span class="liquidity-badge <?= $teamStatusClass ?>"><?= $e($teamStatus) ?></span></td>
                        <td><strong><?= $money($score) ?></strong></td>
                        <td><?= $e($lastActionLabel) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="liquidity-card liquidity-action-info-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Decisão dos times</p>
            <h2>Registrar ação do time</h2>
        </div>
        <p>Escolha o time principal, a ação da rodada e, para operações de mercado, informe time alvo, quantidade e preço unitário. O backend valida caixa, ativos, cotas e bloqueia a segunda ação apenas do time principal.</p>
        <?php if ($finalWasClosed): ?><p class="warning-text">A final já foi encerrada. Nenhuma nova ação pode ser registrada.</p><?php endif; ?>
        <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/actions" class="liquidity-team-action-form" data-admin-action-form>
            <?= \App\Core\Csrf::input() ?>
            <div class="liquidity-form-grid">
                <label>
                    <span>Time principal</span>
                    <select name="team_id" required>
                        <option value="">Selecione a equipe</option>
                        <?php foreach ($teams as $team): ?>
                            <?php $teamId = (int)$team['id']; ?>
                            <?php $teamCannotAct = $finalWasClosed || !empty($actedByTeam[$teamId]) || !empty($team['is_eliminated']); ?>
                            <option value="<?= $teamId ?>" <?= $teamCannotAct ? 'disabled' : '' ?>>
                                <?= $e($team['name'] ?? '-') ?><?= $finalWasClosed ? ' — final encerrada' : (!empty($team['is_eliminated']) ? ' — eliminado na semifinal' : (!empty($actedByTeam[$teamId]) ? ' — ação já usada' : '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Ação</span>
                    <select name="action_type" data-action-select required>
                        <option value="deposit_nft">Depositar NFT na piscina</option>
                        <option value="withdraw_nft">Retirar NFT da piscina</option>
                        <option value="buy_btc">Comprar BTC de outro time</option>
                        <option value="sell_btc">Vender BTC para outro time</option>
                        <option value="buy_nft">Comprar NFT em mãos de outro time</option>
                        <option value="sell_nft">Vender NFT em mãos para outro time</option>
                        <option value="buy_share">Comprar cota da piscina de outro time</option>
                        <option value="sell_share">Vender cota da piscina para outro time</option>
                        <option value="pass">Passar a vez</option>
                    </select>
                </label>
                <label data-quantity-field hidden>
                    <span>Quantidade</span>
                    <input type="number" name="quantity" min="0.01" step="0.01" value="1" inputmode="decimal">
                </label>
                <label data-target-field hidden>
                    <span>Time alvo</span>
                    <select name="target_team_id">
                        <option value="">Selecione a contraparte</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?= (int)$team['id'] ?>" <?= ($finalWasClosed || !empty($team['is_eliminated'])) ? 'disabled' : '' ?>><?= $e($team['name'] ?? '-') ?><?= $finalWasClosed ? ' — final encerrada' : (!empty($team['is_eliminated']) ? ' — eliminado' : '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label data-price-field hidden>
                    <span>Preço unitário em R$</span>
                    <input type="number" name="price" min="0.01" step="0.01" inputmode="decimal">
                </label>
                <label data-payment-field hidden>
                    <span>Forma de pagamento</span>
                    <select name="payment_method">
                        <option value="btc">Pagar com 11 BTC</option>
                        <option value="cash">Pagar com R$ 2.000</option>
                    </select>
                </label>
            </div>
            <div class="liquidity-action-summary">
                <span><strong><?= $actedCount ?></strong> com ação registrada</span>
                <span><strong><?= $pendingCount ?></strong> aguardando decisão</span>
            </div>
            <button type="submit" <?= $finalWasClosed ? 'disabled' : '' ?>>Registrar ação</button>
        </form>
    </section>

    <section class="liquidity-card feed-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Linha do tempo</p>
            <h2>Feed de eventos</h2>
        </div>
        <ul class="liquidity-feed-list">
            <?php if (!$feed): ?>
                <li>Nenhum evento registrado ainda.</li>
            <?php endif; ?>
            <?php foreach ($feed as $event): ?>
                <li>
                    <span class="liquidity-event-round">R<?= (int)$event['round_number'] ?></span>
                    <div>
                        <strong><?= $e($event['event_type']) ?></strong>
                        <p><?= $e($event['description']) ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="liquidity-card liquidity-controls-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Administração</p>
            <h2>Controles da semifinal/final</h2>
        </div>
        <div class="action-grid liquidity-control-grid">
            <a class="action-card" href="/liquidity/<?= (int)$s['id'] ?>/teams"><h3>Gerenciar equipes</h3></a>
            <a class="action-card" target="_blank" href="/liquidity/<?= (int)$s['id'] ?>/projector"><h3>Abrir projetor</h3></a>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/advance-round" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit" <?= $finalWasClosed ? 'disabled' : '' ?>>Encerrar rodada</button></form>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/evaluate-semifinal" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit">Avaliar semifinal</button></form>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/close-final" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit" <?= $finalWasClosed ? 'disabled' : '' ?>>Encerrar final</button></form>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/close" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit" <?= $finalWasClosed ? 'disabled' : '' ?>>Encerrar sessão</button></form>
        </div>
    </section>
</div>
<script src="/assets/js/liquidity-admin.js"></script>
