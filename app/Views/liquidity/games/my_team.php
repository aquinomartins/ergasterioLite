<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); $money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.'); ?>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Painel da equipe</p>
    <h1><?= $e($game['title'] ?? 'Jogo') ?></h1>
    <?php if (!empty($message)): ?><p><?= $e($message) ?></p><?php else: ?>
        <h2><?= $e($team['name'] ?? 'Equipe') ?></h2>
        <ul>
            <li>Caixa: <?= $money($team['cash_balance'] ?? 0) ?></li>
            <li>BTC: <?= $e($team['btc_balance'] ?? 0) ?></li>
            <li>NFTs em mãos: <?= (int)($team['nft_balance'] ?? 0) ?></li>
            <li>Cotas: <?= (int)($team['pool_shares'] ?? 0) ?></li>
            <li>Status: <?= $e($team['status'] ?? 'active') ?></li>
        </ul>
        <p><a href="/liquidity/<?= (int)$game['id'] ?>/projector">Arena pública</a></p>
    <?php endif; ?>
    <p><a href="/liquidity/my-games">Meus jogos</a></p>
</section>
