<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Arena pública</p>
    <h1>Arenas públicas</h1>
    <p>Escolha uma partida para acompanhar o placar, o estado da piscina e os eventos recentes.</p>
</section>
<section class="liquidity-card">
    <?php if (empty($games)): ?><p>Nenhuma arena disponível no momento.</p><?php endif; ?>
    <div class="game-list">
        <?php foreach (($games ?? []) as $game): ?>
            <article class="game-summary-card">
                <h2><?= $e($game['title'] ?? 'Jogo') ?></h2>
                <p><strong>Status:</strong> <?= $e($game['status'] ?? '-') ?> · <strong>Rodada:</strong> <?= (int)($game['current_round'] ?? 1) ?></p>
                <a class="button" href="/liquidity/games/<?= (int)$game['id'] ?>/arena">Ver placar</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
