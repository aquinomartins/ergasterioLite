<?php
$loggedIn = !empty($currentUser);
$teacherHref = $loggedIn ? '/liquidity/create' : '/login';
$teamHref = $loggedIn ? '/liquidity/my-games' : '/login';
$arenaRouteExists = true;
?>
<section class="hero card liquidity-home-hero">
    <div>
        <span class="eyebrow liquidity-eyebrow">Jogo principal</span>
        <h1>Piscina de Liquidez</h1>
        <p class="hero-subtitle">Um jogo de estratégia sobre NFT, BTC, cotas e confiança coletiva.</p>
        <p class="hero-copy">
            Crie uma partida, entre como equipe ou acompanhe a arena pública. Cada equipe precisa decidir
            quando guardar, depositar, negociar ou buscar liquidez.
        </p>
    </div>
</section>

<section class="role-choice-grid home-role-grid" aria-label="Escolha como participar da Piscina de Liquidez">
    <article class="card role-choice-card home-role-card">
        <span class="role-choice-icon">P</span>
        <h2>Professor</h2>
        <p>Crie uma partida, aprove participantes e controle as rodadas.</p>
        <a class="button" href="<?= $teacherHref ?>">Criar jogo</a>
    </article>
    <article class="card role-choice-card home-role-card">
        <span class="role-choice-icon">E</span>
        <h2>Equipe</h2>
        <p>Entre com o código do jogo, aguarde aprovação e jogue pelo Painel da Equipe.</p>
        <a class="button button-secondary" href="<?= $teamHref ?>">Entrar como equipe</a>
    </article>
    <article class="card role-choice-card home-role-card">
        <span class="role-choice-icon">A</span>
        <h2>Espectador</h2>
        <p>Veja ranking, estado da piscina e feed narrativo da partida.</p>
        <?php if ($arenaRouteExists): ?>
            <a class="button button-secondary" href="/liquidity/arenas">Ver arena pública</a>
        <?php else: ?>
            <span class="button button-secondary button-disabled" aria-disabled="true">Em breve</span>
        <?php endif; ?>
    </article>
</section>

<section class="card mvp-note-card technical-note-card">
    <span class="eyebrow">Sobre o MVP</span>
    <h2>Sobre o MVP</h2>
    <p>Projeto em desenvolvimento com PHP, MySQL e JavaScript leve.</p>
</section>

<section class="grid-two technical-grid" aria-label="Notas técnicas do projeto">
    <article class="card technical-note-card">
        <h2>Base simples para evoluir</h2>
        <p>A estrutura do projeto mantém módulos separados para facilitar melhorias futuras.</p>
    </article>
    <article class="card technical-note-card">
        <h2>Compatível com hospedagem compartilhada</h2>
        <p>Sem framework CSS novo: apenas PHP, MySQL, JavaScript leve e estilos próprios.</p>
    </article>
</section>
