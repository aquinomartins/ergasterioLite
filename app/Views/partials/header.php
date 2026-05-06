<?php
use App\Core\Csrf;
?>
<header class="site-header">
    <div class="container nav-bar">
        <a href="/" class="brand">
            <span class="brand-mark">EL</span>
            <span><?= htmlspecialchars($app['name'], ENT_QUOTES, 'UTF-8') ?></span>
        </a>

        <button class="nav-toggle" type="button" data-nav-toggle aria-label="Abrir menu">☰</button>

        <nav class="site-nav" data-nav>
            <a href="/">Início</a>
            <a href="/liquidity">Piscina de Liquidez</a>
            <a href="/artists">Artistas</a>
            <a href="/artworks">Obras</a>
            <a href="/markets">Mercados</a>
            <a href="/rankings">Ranking</a>
            <?php if ($currentUser): ?>
                <a href="/dashboard">Dashboard</a>
                <a href="/profile/edit">Perfil</a>
                <form method="POST" action="/logout" class="inline-form">
                    <?= Csrf::input() ?>
                    <button type="submit" class="link-button">Sair</button>
                </form>
            <?php else: ?>
                <a href="/register">Cadastro</a>
                <a href="/login">Entrar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
