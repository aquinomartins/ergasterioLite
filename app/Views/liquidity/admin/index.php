<section class="liquidity-card liquidity-entry-page">
    <p class="liquidity-eyebrow">Piscina de Liquidez</p>
    <h1>Escolha como entrar no jogo</h1>
    <p>Use o fluxo principal para organizar partidas, solicitar entrada como equipe ou acompanhar arenas públicas. O painel técnico antigo foi preservado apenas como acesso administrativo avançado.</p>
</section>
<section class="role-choice-grid liquidity-entry-actions">
    <article class="liquidity-card role-choice-card"><h2>Meus jogos</h2><p>Veja partidas que você organiza ou participa.</p><a class="button" href="/liquidity/my-games">Meus jogos</a></article>
    <article class="liquidity-card role-choice-card"><h2>Criar novo jogo</h2><p>Abra uma partida e compartilhe o código de convite.</p><a class="button" href="/liquidity/create">Criar novo jogo</a></article>
    <article class="liquidity-card role-choice-card"><h2>Entrar com código</h2><p>Solicite participação em uma partida criada pelo professor.</p><a class="button button-secondary" href="/liquidity/join">Entrar com código</a></article>
    <article class="liquidity-card role-choice-card"><h2>Arenas públicas</h2><p>Acompanhe rankings, piscina e feed de eventos.</p><a class="button button-secondary" href="/liquidity/arenas">Ver arenas públicas</a></article>
</section>
<?php if (!empty($sessions)): ?>
<section class="liquidity-card admin-legacy-list">
    <p class="liquidity-eyebrow">Administrativo avançado preservado</p>
    <h2>Sessões antigas</h2>
    <p>Use estes links apenas para manutenção de sessões antigas.</p>
    <?php foreach (($sessions ?? []) as $s): ?>
        <div><a href="/liquidity/<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></a></div>
    <?php endforeach; ?>
</section>
<?php endif; ?>
