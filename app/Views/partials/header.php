<?php
use App\Core\Csrf;
$isMasterGold = !empty($currentUser) && strtolower((string)($currentUser['role'] ?? '')) === 'master_gold';
?>
<header class="site-header">
    <div class="container nav-bar">
        <a href="/" class="brand"><span class="brand-mark">EL</span><span><?= htmlspecialchars($app['name'], ENT_QUOTES, 'UTF-8') ?></span></a>
        <button class="nav-toggle" type="button" data-nav-toggle aria-label="Abrir menu">☰</button>
        <nav class="site-nav" data-nav>
            <a href="/">Início</a>
            <?php if ($currentUser): ?>
                <a href="/liquidity/my-games">Meus jogos</a>
                <a href="/liquidity/create">Criar jogo</a>
                <a href="/liquidity/join">Entrar com código</a>
                <?php if ($isMasterGold): ?><a href="/dashboard">Administração Master Gold</a><?php endif; ?>
                <a href="/profile/edit">Perfil</a>
                <form method="POST" action="/logout" class="inline-form"><?= Csrf::input() ?><button type="submit" class="link-button">Sair</button></form>
            <?php else: ?>
                <a href="/login">Entrar</a>
                <a href="/register">Criar conta</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
