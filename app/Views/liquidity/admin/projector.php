<?php $e = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="liquidity-shell projector-layout" id="projector" data-session-id="<?= (int)$sessionId ?>">
    <header class="liquidity-header">
        <h1 class="projector-title">Piscina de Liquidez</h1>
        <p>Rodada <span data-session-round>0</span>/<span data-session-total>0</span></p>
    </header>

    <section class="vitality-card projector-pool pool-status-stable" data-projector-pool>
        <h2>Piscina Central</h2>
        <p>Status visual: <strong data-pool-status>stable</strong></p>
        <ul>
            <li>NFTs na piscina: <span data-pool-nfts>0</span></li>
            <li>Cotas emitidas: <span data-pool-shares>0</span></li>
            <li>Valor bloqueado: <span data-pool-total>R$ 0,00</span></li>
            <li>Rendimento por cota: <span data-pool-yield>0,0000</span></li>
        </ul>
    </section>

    <section class="vitality-grid">
        <article class="vitality-card">
            <h2>Ranking geral</h2>
            <p>Ranking informativo por patrimônio estimado. Não define o vencedor da final.</p>
            <table class="ranking-table"><thead><tr><th>#</th><th>Equipe</th><th>Patrimônio estimado</th><th>Status</th></tr></thead><tbody data-ranking-body></tbody></table>
        </article>
        <article class="vitality-card">
            <h2>Feed vivo</h2>
            <ul class="feed-list" data-feed-list></ul>
        </article>
    </section>

    <section class="vitality-card market-arena-card" id="prediction-projector" data-markets-wrap hidden>
        <h2>Mercados da Arena</h2>
        <div data-markets-list></div>
    </section>
</div>
<script src="/assets/js/liquidity-projector.js"></script>
