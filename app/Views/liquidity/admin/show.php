<?php
$s = $state['session'] ?? [];
$pool = $state['pool'] ?? [];
$ranking = $state['ranking'] ?? [];
$feed = $state['feed'] ?? [];
$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v): string => 'R$ ' . number_format((float)$v, 2, ',', '.');
$percent = static fn($v): string => rtrim(rtrim(number_format(((float)$v) * 100, 2, ',', '.'), '0'), ',') . '%';
$totalTeams = count($teams);
$actedCount = 0;
foreach ($actedByTeam as $hasActed) {
    if (!empty($hasActed)) {
        $actedCount++;
    }
}
$pendingCount = max(0, $totalTeams - $actedCount);
?>
<div class="liquidity-dashboard liquidity-admin-page">
    <section class="liquidity-card liquidity-hero-card">
        <div>
            <p class="liquidity-eyebrow">Piscina de Liquidez</p>
            <h1><?= $e($s['name'] ?? 'Sessão') ?></h1>
            <div class="liquidity-meta-grid">
                <span><strong>Código:</strong> <?= $e($s['access_code'] ?? '-') ?></span>
                <span><strong>Rodada:</strong> <?= (int)($s['current_round'] ?? 0) ?>/<?= (int)($s['total_rounds'] ?? 0) ?></span>
                <span><strong>Fase:</strong> <?= $e($s['session_phase'] ?? 'regular') ?></span>
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
                <span>Times pendentes</span>
                <strong><?= $pendingCount ?></strong>
            </article>
        </div>
        <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/advance-round" class="liquidity-inline-control action-card"><?= \App\Core\Csrf::input() ?><button type="submit">Avançar rodada</button></form>
    </section>

    <section class="liquidity-card pool-state-card <?= $e('pool-status-' . ($pool['status'] ?? 'empty')) ?>">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Reserva coletiva</p>
            <h2>Estado da Piscina</h2>
        </div>
        <div class="liquidity-stat-grid liquidity-pool-stats">
            <article class="liquidity-stat"><span>NFTs na piscina</span><strong><?= (int)($pool['pool_nfts'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>Cotas</span><strong><?= (int)($pool['total_shares'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>Valor bloqueado</span><strong><?= $money($pool['total_value'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>Rendimento por cota</span><strong><?= $money($pool['yield_per_share'] ?? 0) ?></strong></article>
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
                    <th>Caixa</th>
                    <th>BTC</th>
                    <th>NFTs</th>
                    <th>Cotas</th>
                    <th>Payoff</th>
                    <th>Status</th>
                    <th>Ação usada?</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($teams as $team): ?>
                    <?php
                    $score = (float)$team['cash_balance'] + ((float)$team['btc_balance'] * (float)$s['btc_sell_price']) + ((int)$team['nft_balance'] * (float)$s['nft_sell_price']) + ((int)$team['pool_shares'] * (float)$s['share_sell_price']);
                    $teamStatus = 'ativo';
                    $teamStatusClass = 'status-active';
                    if (!empty($team['is_eliminated'])) {
                        $teamStatus = 'eliminado';
                        $teamStatusClass = 'status-eliminated';
                    } elseif (!empty($team['qualified_for_final'])) {
                        $teamStatus = 'classificado';
                        $teamStatusClass = 'status-qualified';
                    }
                    $hasTeamActed = !empty($actedByTeam[(int)$team['id']]);
                    ?>
                    <tr>
                        <td><strong><?= $e($team['name']) ?></strong></td>
                        <td><?= $money($team['cash_balance']) ?></td>
                        <td><?= number_format((float)$team['btc_balance'], 2, ',', '.') ?></td>
                        <td><?= (int)$team['nft_balance'] ?></td>
                        <td><?= (int)$team['pool_shares'] ?></td>
                        <td><strong><?= $money($score) ?></strong></td>
                        <td><span class="liquidity-badge <?= $teamStatusClass ?>"><?= $e($teamStatus) ?></span></td>
                        <td><span class="liquidity-badge <?= $hasTeamActed ? 'status-qualified' : 'status-pending' ?>"><?= $hasTeamActed ? 'Sim' : 'Não' ?></span></td>
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
        <p>As ações continuam sendo registradas no painel de cada equipe. Use este bloco para acompanhar se cada time já usou sua ação na rodada atual.</p>
        <div class="liquidity-action-summary">
            <span><strong><?= $actedCount ?></strong> com ação registrada</span>
            <span><strong><?= $pendingCount ?></strong> aguardando decisão</span>
        </div>
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

    <section class="liquidity-card ranking-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Pontuação parcial</p>
            <h2>Ranking</h2>
        </div>
        <ol class="liquidity-ranking-list">
            <?php foreach($ranking as $index => $r): ?>
                <li>
                    <span class="liquidity-rank-position">#<?= $index + 1 ?></span>
                    <strong><?= $e($r['name'] ?? '-') ?></strong>
                    <span><?= $money($r['score'] ?? 0) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="liquidity-card liquidity-controls-card">
        <div class="liquidity-card-header">
            <p class="liquidity-eyebrow">Administração</p>
            <h2>Controles da semifinal/final</h2>
        </div>
        <div class="action-grid liquidity-control-grid">
            <a class="action-card" href="/liquidity/<?= (int)$s['id'] ?>/teams"><h3>Gerenciar equipes</h3></a>
            <a class="action-card" target="_blank" href="/liquidity/<?= (int)$s['id'] ?>/projector"><h3>Abrir projetor</h3></a>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/advance-round" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit">Avançar rodada</button></form>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/evaluate-semifinal" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit">Avaliar semifinal</button></form>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/close-final" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit">Encerrar final</button></form>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/close" class="action-card"><?= \App\Core\Csrf::input() ?><button type="submit">Encerrar sessão</button></form>
        </div>
    </section>
</div>
<script src="/assets/js/liquidity-admin.js"></script>
