<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<section class="liquidity-card">
    <h1>Meus jogos</h1>
    <p><a href="/liquidity/create">Criar novo jogo</a> · <a href="/liquidity/join">Entrar com código</a></p>
</section>
<section class="liquidity-card">
    <h2>Jogos criados por mim</h2>
    <?php if (!$ownedGames): ?><p>Você ainda não criou jogos.</p><?php endif; ?>
    <?php foreach ($ownedGames as $game): ?>
        <article><strong><?= $e($game['title']) ?></strong> — código <?= $e($game['invite_code']) ?> — <?= $e($game['status']) ?> <a href="/liquidity/games/<?= (int)$game['id'] ?>/teacher">Painel do professor</a></article>
    <?php endforeach; ?>
</section>
<section class="liquidity-card">
    <h2>Jogos em que participo</h2>
    <?php if (!$participantGames): ?><p>Você ainda não entrou em jogos.</p><?php endif; ?>
    <?php foreach ($participantGames as $row): ?>
        <article><strong><?= $e($row['title']) ?></strong> — sua participação: <?= $e($row['status']) ?>
            <?php if ($row['status'] === 'approved'): ?><a href="/liquidity/games/<?= (int)$row['game_id'] ?>/my-team">Painel da equipe</a><?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
