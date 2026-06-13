<?php $loggedIn = !empty($currentUser); ?>
<section class="hero card liquidity-home-hero">
    <div>
        <span class="eyebrow liquidity-eyebrow">Jogo principal</span>
        <h1>Piscina de Liquidez</h1>
        <p>
            Piscina de Liquidez é um jogo de estratégia sobre NFT, BTC, cotas e confiança coletiva.
            Crie uma partida, entre como equipe ou acompanhe a arena pública.
        </p>
    </div>
</section>

<section class="role-choice-grid">
    <article class="card role-choice-card">
        <span class="role-choice-icon">A</span>
        <h2>Professor</h2>
        <p>Crie uma partida, aprove participantes e controle as rodadas.</p>
        <a class="button" href="<?= $loggedIn ? '/liquidity/create' : '/login' ?>">Criar jogo</a>
    </article>
    <article class="card role-choice-card">
        <span class="role-choice-icon">B</span>
        <h2>Equipe</h2>
        <p>Entre com o código do jogo, aguarde aprovação e jogue pelo Painel da Equipe.</p>
        <a class="button button-secondary" href="<?= $loggedIn ? '/liquidity/my-games' : '/login' ?>">Entrar como equipe</a>
    </article>
    <article class="card role-choice-card">
        <span class="role-choice-icon">C</span>
        <h2>Espectador</h2>
        <p>Veja o ranking, o estado da piscina e o feed de eventos.</p>
        <a class="button button-secondary" href="/liquidity/arenas">Ver arena pública</a>
    </article>
</section>

<section class="card mvp-note-card">
    <span class="eyebrow">MVP em construção</span>
    <h2>Base técnica pronta para evoluir</h2>
    <p>
        O Ergastério Lite mantém autenticação, perfis, artistas, obras, mercados e o módulo da Piscina de Liquidez
        em arquitetura simples para hospedagem compartilhada, PHP/MySQL e CSS próprio.
    </p>
</section>

<section class="grid-two">
    <article class="card">
        <h2>Arquitetura pensada para o próximo passo</h2>
        <p>A estrutura separa responsabilidades para facilitar a evolução dos módulos sem reorganizar a base do projeto.</p>
    </article>
    <article class="card">
        <h2>Compatível com ambiente simples</h2>
        <p>Sem framework pesado, sem websocket, sem filas e sem processos persistentes. Apenas PHP, MySQL, JavaScript leve e CSS próprio.</p>
    </article>
</section>
