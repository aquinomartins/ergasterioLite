<?php
$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v): string => 'R$ ' . number_format((float)$v, 2, ',', '.');
$number = static fn($v, int $decimals = 0): string => number_format((float)$v, $decimals, ',', '.');

if (!empty($projectorError)) {
    ?>
    <div class="projector-shell" id="projector">
        <header class="projector-hero">
            <div>
                <p class="projector-kicker">Modo projetor · somente leitura</p>
                <h1>Piscina de Liquidez</h1>
                <div class="projector-hero-meta"><span>Erro de carregamento</span></div>
            </div>
        </header>

        <section class="projector-card">
            <div class="projector-empty-state">
                <strong><?= $e($projectorError) ?></strong>
                <span>Confira o endereço do projetor ou tente novamente em instantes.</span>
            </div>
        </section>
    </div>
    <?php
    return;
}

$state = $state ?? [];
$s = $state['session'] ?? [];
$pool = $state['pool'] ?? [];
$finalRanking = $state['final_ranking'] ?? [];
$feed = array_slice($state['feed'] ?? [], 0, 8);
$teams = $teams ?? [];
$actedByTeam = $actedByTeam ?? [];
$lastActionByTeam = $lastActionByTeam ?? [];
$currentRoundState = $currentRoundState ?? null;

$projectorRanking = $teams;
foreach ($projectorRanking as &$rankingTeam) {
    $rankingTeam['projector_estimated_wealth'] = (float)($rankingTeam['cash_balance'] ?? 0)
        + ((float)($rankingTeam['btc_balance'] ?? 0) * 100)
        + ((int)($rankingTeam['nft_balance'] ?? 0) * 1800)
        + ((int)($rankingTeam['pool_shares'] ?? 0) * 500);
}
unset($rankingTeam);
usort($projectorRanking, static fn(array $a, array $b): int =>
    ((float)$b['projector_estimated_wealth'] <=> (float)$a['projector_estimated_wealth'])
    ?: ((float)($b['cash_balance'] ?? 0) <=> (float)($a['cash_balance'] ?? 0))
    ?: ((string)($a['name'] ?? '') <=> (string)($b['name'] ?? ''))
);

$currentRound = (int)($s['current_round'] ?? 1);
$totalRounds = (int)($s['total_rounds'] ?? 0);
$phase = (string)($s['session_phase'] ?? 'regular');
$sessionStatus = (string)($s['status'] ?? 'active');
$semifinalEvaluated = in_array($phase, ['semifinal_evaluated', 'final', 'final_closed', 'closed'], true);
$finalClosed = in_array($phase, ['final_closed', 'closed'], true) || $sessionStatus === 'closed';
$phaseLabel = match (true) {
    $finalClosed => 'Final encerrada',
    $semifinalEvaluated => 'Semifinal avaliada',
    $phase === 'semifinal' => 'Semifinal',
    default => 'Em andamento',
};
$statusLabel = $sessionStatus === 'closed' ? 'Encerrada' : 'Ativa';
$roundStatus = (string)($currentRoundState['status'] ?? 'open');
$roundStatusLabel = $roundStatus === 'closed' ? 'Rodada encerrada' : 'Rodada aberta';

$totalTeams = count($teams);
$actedCount = 0;
foreach ($teams as $team) {
    if (!empty($actedByTeam[(int)$team['id']])) {
        $actedCount++;
    }
}
$pendingCount = max(0, $totalTeams - $actedCount);

$poolNfts = (int)($pool['pool_nfts'] ?? 0);
$totalShares = (int)($pool['total_shares'] ?? 0);
$poolTotalValue = $poolNfts * 2000;
$totalYield = $poolTotalValue * 0.10;
$yieldPerShare = $totalShares > 0 ? $totalYield / $totalShares : 0.0;

$teamNames = [];
foreach ($teams as $team) {
    $teamNames[(int)$team['id']] = (string)$team['name'];
}

$formatQty = static function ($value) use ($number): string {
    $float = (float)$value;
    return abs($float - round($float)) < 0.00001 ? (string)(int)round($float) : $number($float, 2);
};

$actionText = static function (?array $action) use ($teamNames, $formatQty, $money): string {
    if (!$action) {
        return '—';
    }

    $qty = $formatQty($action['quantity'] ?? 1);
    $targetName = isset($action['target_team_id'], $teamNames[(int)$action['target_team_id']])
        ? $teamNames[(int)$action['target_team_id']]
        : 'outro time';
    $price = (float)($action['price'] ?? 0);
    $total = $price * (float)($action['quantity'] ?? 1);

    return match ((string)($action['action_type'] ?? '')) {
        'deposit_nft' => 'Depositou 1 NFT na piscina',
        'withdraw_nft_btc' => 'Retirou 1 NFT pagando em BTC',
        'withdraw_nft_cash' => 'Retirou 1 NFT pagando em reais',
        'buy_btc' => 'Comprou ' . $qty . ' BTC de ' . $targetName . ($price > 0 ? ' por ' . $money($total) : ''),
        'sell_btc' => 'Vendeu ' . $qty . ' BTC para ' . $targetName . ($price > 0 ? ' por ' . $money($total) : ''),
        'buy_nft', 'trade_nft_between_teams' => 'Comprou ' . $qty . ' NFT de ' . $targetName . ($price > 0 ? ' por ' . $money($total) : ''),
        'sell_nft' => 'Vendeu ' . $qty . ' NFT para ' . $targetName . ($price > 0 ? ' por ' . $money($total) : ''),
        'buy_share' => 'Comprou ' . $qty . ' cota de ' . $targetName . ($price > 0 ? ' por ' . $money($total) : ''),
        'sell_share' => 'Vendeu ' . $qty . ' cota para ' . $targetName . ($price > 0 ? ' por ' . $money($total) : ''),
        'pass' => 'Passou a vez',
        default => 'Ação registrada',
    };
};

$badgeClass = static function (string $status): string {
    $normalized = strtolower($status);
    return match (true) {
        str_contains($normalized, 'vencedor') => 'projector-badge-win',
        str_contains($normalized, 'eliminado') => 'projector-badge-danger',
        str_contains($normalized, 'classificado'), str_contains($normalized, 'finalista') => 'projector-badge-success',
        str_contains($normalized, 'aguardando') => 'projector-badge-wait',
        str_contains($normalized, 'agiu') => 'projector-badge-done',
        default => 'projector-badge-neutral',
    };
};
?>
<div class="projector-shell" id="projector" data-session-id="<?= (int)($s['id'] ?? 0) ?>">
    <header class="projector-hero">
        <div>
            <p class="projector-kicker">Modo projetor · somente leitura</p>
            <h1>Piscina de Liquidez — Modo Projetor</h1>
            <div class="projector-hero-meta">
                <span>Nome: <?= $e($s['name'] ?? ('Piscina ' . ($s['id'] ?? ''))) ?></span>
                <span>Código: <?= $e($s['access_code'] ?? '-') ?></span>
                <span>Rodada <?= $currentRound ?><?= $totalRounds > 0 ? '/' . $totalRounds : '' ?></span>
                <span>Fase: <?= $e($phaseLabel) ?></span>
                <span>Status: <?= $e($statusLabel) ?></span>
            </div>
        </div>
        <div class="projector-round-pill">
            <strong>R<?= $currentRound ?></strong>
            <span><?= $e($roundStatusLabel) ?></span>
        </div>
    </header>

    <section class="projector-card projector-round-card">
        <div class="projector-card-header">
            <div>
                <p class="projector-kicker">Acompanhamento</p>
                <h2>Estado da rodada</h2>
            </div>
            <span class="projector-badge <?= $roundStatus === 'closed' ? 'projector-badge-neutral' : 'projector-badge-success' ?>"><?= $e($roundStatusLabel) ?></span>
        </div>
        <div class="projector-stat-grid projector-stat-grid-four">
            <div><span>Rodada atual</span><strong><?= $currentRound ?></strong></div>
            <div><span>Total de times</span><strong><?= $totalTeams ?></strong></div>
            <div><span>Já agiram</span><strong><?= $actedCount ?></strong></div>
            <div><span>Aguardando</span><strong><?= $pendingCount ?></strong></div>
        </div>
        <div class="projector-table-wrap">
            <table class="projector-table">
                <thead><tr><th>Equipe</th><th>Ação usada?</th><th>Última ação</th></tr></thead>
                <tbody>
                    <?php foreach ($teams as $team): ?>
                        <?php $teamId = (int)$team['id']; $acted = !empty($actedByTeam[$teamId]); ?>
                        <tr>
                            <td><strong><?= $e($team['name']) ?></strong></td>
                            <td><span class="projector-badge <?= $acted ? 'projector-badge-done' : 'projector-badge-wait' ?>"><?= $acted ? 'Já agiu' : 'Aguardando' ?></span></td>
                            <td><?= $e($actionText($lastActionByTeam[$teamId] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$teams): ?><tr><td colspan="3">Nenhum time cadastrado ainda.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="projector-card">
        <div class="projector-card-header">
            <div>
                <p class="projector-kicker">Piscina central</p>
                <h2>Estado da piscina</h2>
            </div>
            <span class="projector-badge projector-badge-neutral"><?= $e($pool['status'] ?? '—') ?></span>
        </div>
        <div class="projector-stat-grid projector-stat-grid-five">
            <div><span>NFTs na piscina</span><strong><?= $poolNfts ?></strong></div>
            <div><span>Cotas</span><strong><?= $totalShares ?></strong></div>
            <div><span>Valor da piscina</span><strong><?= $money($poolTotalValue) ?></strong></div>
            <div><span>Rendimento estimado</span><strong><?= $money($totalYield) ?></strong></div>
            <div><span>Rendimento por cota</span><strong><?= $money($yieldPerShare) ?></strong></div>
        </div>
    </section>

    <section class="projector-card">
        <div class="projector-card-header">
            <div>
                <p class="projector-kicker">Saldos dos participantes</p>
                <h2>Times</h2>
            </div>
        </div>
        <div class="projector-table-wrap">
            <table class="projector-table projector-table-large">
                <thead><tr><th>Equipe</th><th>Caixa R$</th><th>BTC</th><th>NFTs em mãos</th><th>Cotas</th><th>Ação usada?</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($teams as $team): ?>
                        <?php
                        $teamId = (int)$team['id'];
                        $acted = !empty($actedByTeam[$teamId]);
                        $status = (string)($team['display_status'] ?? '');
                        if ($status === '') {
                            $status = !empty($team['final_status']) ? (string)$team['final_status'] : (!empty($team['is_eliminated']) ? 'Eliminado na semifinal' : (!empty($team['qualified_for_final']) ? 'Classificado para a final' : 'Em jogo'));
                        }
                        ?>
                        <tr>
                            <td><strong><?= $e($team['name']) ?></strong></td>
                            <td><?= $money($team['cash_balance'] ?? 0) ?></td>
                            <td><?= $number($team['btc_balance'] ?? 0, 2) ?></td>
                            <td><?= (int)($team['nft_balance'] ?? 0) ?></td>
                            <td><?= (int)($team['pool_shares'] ?? 0) ?></td>
                            <td><span class="projector-badge <?= $acted ? 'projector-badge-done' : 'projector-badge-wait' ?>"><?= $acted ? 'Já agiu' : 'Aguardando' ?></span></td>
                            <td><span class="projector-badge <?= $badgeClass($status) ?>"><?= $e($status) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$teams): ?><tr><td colspan="7">Nenhum time cadastrado ainda.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="projector-grid-two">
        <article class="projector-card">
            <div class="projector-card-header">
                <div>
                    <p class="projector-kicker">Patrimônio estimado</p>
                    <h2>Ranking geral</h2>
                </div>
            </div>
            <p class="projector-note">Ranking informativo. Não define o vencedor final.</p>
            <div class="projector-table-wrap">
                <table class="projector-table">
                    <thead><tr><th>Posição</th><th>Equipe</th><th>Patrimônio estimado</th><th>Caixa R$</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($projectorRanking as $i => $row): ?>
                            <?php $status = (string)($row['display_status'] ?? 'Em jogo'); ?>
                            <tr>
                                <td><strong>#<?= $i + 1 ?></strong></td>
                                <td><?= $e($row['name'] ?? 'Equipe') ?></td>
                                <td><?= $money($row['projector_estimated_wealth'] ?? 0) ?></td>
                                <td><?= $money($row['cash_balance'] ?? 0) ?></td>
                                <td><span class="projector-badge <?= $badgeClass($status) ?>"><?= $e($status) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$projectorRanking): ?><tr><td colspan="5">Ranking indisponível.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="projector-card">
            <div class="projector-card-header">
                <div>
                    <p class="projector-kicker">Critério: NFT em mãos</p>
                    <h2>Semifinal</h2>
                </div>
            </div>
            <?php if (!$semifinalEvaluated): ?>
                <div class="projector-empty-state">
                    <strong>Semifinal ainda não avaliada.</strong>
                    <span>Critério: passa quem tiver pelo menos 1 NFT em mãos.</span>
                    <span>NFT dentro da piscina não conta.</span>
                </div>
            <?php else: ?>
                <p class="projector-note">NFT dentro da piscina não conta.</p>
                <div class="projector-table-wrap">
                    <table class="projector-table">
                        <thead><tr><th>Equipe</th><th>NFTs em mãos</th><th>Resultado</th></tr></thead>
                        <tbody>
                            <?php foreach ($teams as $team): ?>
                                <?php $classified = !empty($team['qualified_for_final']) && empty($team['is_eliminated']); ?>
                                <tr>
                                    <td><?= $e($team['name']) ?></td>
                                    <td><?= (int)($team['nft_balance'] ?? 0) ?></td>
                                    <td><span class="projector-badge <?= $classified ? 'projector-badge-success' : 'projector-badge-danger' ?>"><?= $classified ? 'Classificado' : 'Eliminado' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section class="projector-grid-two">
        <article class="projector-card projector-final-card">
            <div class="projector-card-header">
                <div>
                    <p class="projector-kicker">Critério: caixa em reais</p>
                    <h2>Final</h2>
                </div>
            </div>
            <p class="projector-note">Na final, vence quem tiver mais dinheiro em reais. BTC, NFT e cotas não contam.</p>
            <?php if (!$semifinalEvaluated): ?>
                <div class="projector-empty-state"><strong>A final será exibida após a semifinal.</strong></div>
            <?php elseif ($finalClosed): ?>
                <?php
                $winners = array_values(array_filter($finalRanking, static fn($row): bool => (int)($row['final_position'] ?? 0) === 1));
                $winnerCash = $winners ? (float)($winners[0]['final_cash_score'] ?? $winners[0]['cash_balance'] ?? 0) : 0.0;
                ?>
                <div class="projector-winner-box">
                    <span>Resultado final</span>
                    <strong><?= count($winners) > 1 ? 'Vencedores empatados' : 'Vencedor' ?>: <?= $e(implode(' e ', array_map(static fn($row): string => (string)$row['name'], $winners))) ?> — <?= $money($winnerCash) ?></strong>
                </div>
                <div class="projector-table-wrap">
                    <table class="projector-table">
                        <thead><tr><th>Posição</th><th>Equipe</th><th>Caixa R$</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($finalRanking as $row): ?>
                                <?php $status = (string)($row['display_status'] ?? 'Finalista'); ?>
                                <tr><td>#<?= (int)($row['final_position'] ?? 0) ?></td><td><?= $e($row['name']) ?></td><td><?= $money($row['final_cash_score'] ?? $row['cash_balance'] ?? 0) ?></td><td><span class="projector-badge <?= $badgeClass($status) ?>"><?= $e($status) ?></span></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <h3>Ranking parcial da final por caixa em reais</h3>
                <div class="projector-table-wrap">
                    <table class="projector-table">
                        <thead><tr><th>Posição</th><th>Equipe</th><th>Caixa R$</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($finalRanking as $row): ?>
                                <?php $status = (string)($row['display_status'] ?? 'Classificado para a final'); ?>
                                <tr><td>#<?= (int)($row['final_position'] ?? 0) ?></td><td><?= $e($row['name']) ?></td><td><?= $money($row['final_cash_score'] ?? $row['cash_balance'] ?? 0) ?></td><td><span class="projector-badge <?= $badgeClass($status) ?>"><?= $e($status) ?></span></td></tr>
                            <?php endforeach; ?>
                            <?php if (!$finalRanking): ?><tr><td colspan="4">Nenhum finalista encontrado.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>

        <article class="projector-card projector-feed-card">
            <div class="projector-card-header">
                <div>
                    <p class="projector-kicker">Linha do tempo</p>
                    <h2>Últimos eventos</h2>
                </div>
            </div>
            <ul class="projector-feed-list">
                <?php foreach ($feed as $event): ?>
                    <li><span>[R<?= (int)($event['round_number'] ?? 0) ?>]</span><strong><?= $e($event['description'] ?? '') ?></strong></li>
                <?php endforeach; ?>
                <?php if (!$feed): ?><li><strong>Nenhum evento registrado ainda.</strong></li><?php endif; ?>
            </ul>
        </article>
    </section>

    <footer class="projector-footer">Atualização automática a cada 10 segundos.</footer>
</div>
