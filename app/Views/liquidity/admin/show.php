<?php
$s = $state['session'] ?? [];
$pool = $state['pool'] ?? [];
$ranking = $state['ranking'] ?? [];
$feed = $state['feed'] ?? [];
$e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v): string => 'R$ ' . number_format((float)$v, 2, ',', '.');
$isClosed = ($s['status'] ?? '') === 'closed';
$isFinalRound = (int)($s['current_round'] ?? 0) >= (int)($s['total_rounds'] ?? 0);
?>
<div class="liquidity-shell" id="liquidity-admin-show" data-session-id="<?= (int)($s['id'] ?? 0) ?>">
    <header class="liquidity-header">
        <h1><?= $e($s['name'] ?? 'Sessão') ?></h1>
        <p>Código de acesso: <strong><?= $e($s['access_code'] ?? '-') ?></strong> · Rodada <strong><?= (int)($s['current_round'] ?? 0) ?></strong> · Status: <strong><?= $e($s['status'] ?? '-') ?></strong></p>
    </header>

    <section class="vitality-card">
        <h2>Ações administrativas</h2>
        <div class="action-grid">
            <a class="action-card" href="/liquidity/<?= (int)$s['id'] ?>/teams"><h3>Gerenciar equipes</h3></a>
            <a class="action-card" target="_blank" href="/liquidity/<?= (int)$s['id'] ?>/projector"><h3>Abrir projetor</h3></a>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/advance-round" class="action-card" data-admin-action-form>
                <?= \App\Core\Csrf::input() ?>
                <h3>Avançar rodada</h3>
                <button type="submit" <?= $isClosed ? 'disabled' : '' ?>>Avançar rodada</button>
            </form>
            <form method="post" action="/liquidity/<?= (int)$s['id'] ?>/close" class="action-card" data-admin-action-form>
                <?= \App\Core\Csrf::input() ?>
                <h3>Encerrar jogo</h3>
                <button type="submit" <?= $isClosed ? 'disabled' : '' ?>>Encerrar sessão</button>
            </form>
        </div>
        <?php if ($isFinalRound): ?><p class="warning-text">A rodada atual alcançou o limite total. O jogo pode ser encerrado.</p><?php endif; ?>
    </section>

    <section class="vitality-grid">
        <article class="vitality-card pool-card <?= $e('pool-status-' . ($pool['status'] ?? 'stable')) ?>">
            <h2>Estado da piscina</h2>
            <ul>
                <li>NFTs: <?= (int)($pool['nft_reserve'] ?? 0) ?></li>
                <li>Cotas: <?= number_format((float)($pool['share_supply'] ?? 0), 4, ',', '.') ?></li>
                <li>Valor total: <?= $money($pool['total_value_locked'] ?? 0) ?></li>
                <li>Rendimento por cota: <?= number_format((float)($pool['yield_per_share'] ?? 0), 4, ',', '.') ?></li>
                <li>Status: <?= $e($pool['status'] ?? '-') ?></li>
            </ul>
        </article>

        <article class="vitality-card">
            <h2>Ranking parcial</h2>
            <table class="ranking-table">
                <thead><tr><th>#</th><th>Equipe</th><th>Score</th></tr></thead>
                <tbody><?php foreach ($ranking as $i => $row): ?><tr><td><?= $i + 1 ?></td><td><?= $e($row['name'] ?? '') ?></td><td><?= number_format((float)($row['score'] ?? 0), 2, ',', '.') ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </article>
    </section>

    <section class="vitality-grid">
        <article class="vitality-card">
            <h2>Feed</h2>
            <ul class="feed-list"><?php foreach ($feed as $event): ?><li><?= $e($event['event_type'] ?? '') ?> — <?= $e($event['description'] ?? '') ?></li><?php endforeach; ?></ul>
        </article>

        <article class="vitality-card market-arena-card">
            <h2>Mercados preditivos da sessão</h2>
            <a href="/liquidity/<?= (int)$s['id'] ?>/predictions/create">Criar mercado</a>
            <?php if (!empty($predictionMarkets)): ?>
                <?php foreach ($predictionMarkets as $m): ?><div class="prediction-market-card"><strong><?= $e($m['question'] ?? '') ?></strong> (<?= $e($m['status'] ?? '') ?>)</div><?php endforeach; ?>
            <?php else: ?>
                <p>Mercados da Arena serão ativados na próxima fase.</p>
            <?php endif; ?>
        </article>
    </section>
</div>
<script src="/assets/js/liquidity-admin.js"></script>
