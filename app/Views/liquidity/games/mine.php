<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Piscina de Liquidez</p>
    <h1>Meus jogos</h1>
    <div class="quick-actions">
        <a class="button" href="/liquidity/create">Criar novo jogo</a>
        <a class="button button-secondary" href="/liquidity/join">Entrar com código</a>
        <a class="button button-secondary" href="/liquidity/arenas">Ver arenas públicas</a>
    </div>
</section>
<section class="liquidity-card">
    <h2>Jogos que eu organizo</h2>
    <?php if (!$ownedGames): ?><p>Você ainda não criou jogos.</p><?php endif; ?>
    <div class="game-list">
    <?php foreach ($ownedGames as $game): ?>
        <article class="game-summary-card">
            <h3><?= $e($game['title']) ?></h3>
            <p><strong>Código de convite:</strong> <code><?= $e($game['invite_code']) ?></code></p>
            <p><strong>Status:</strong> <span class="liquidity-badge status-active"><?= $e($game['status']) ?></span> · <strong>Rodada atual:</strong> <?= (int)($game['current_round'] ?? 1) ?></p>
            <div class="quick-actions compact"><a class="button" href="/liquidity/games/<?= (int)$game['id'] ?>/teacher">Painel do professor</a><a class="button button-secondary" href="/liquidity/games/<?= (int)$game['id'] ?>/arena">Arena pública</a></div>
        </article>
    <?php endforeach; ?>
    </div>
</section>
<section class="liquidity-card">
    <h2>Jogos em que participo</h2>
    <?php if (!$participantGames): ?><p>Você ainda não entrou em jogos.</p><?php endif; ?>
    <div class="game-list">
    <?php foreach ($participantGames as $row): $status=(string)$row['status']; ?>
        <article class="game-summary-card">
            <h3><?= $e($row['title']) ?></h3>
            <p><strong>Status da participação:</strong> <span class="liquidity-badge status-<?= $status === 'approved' ? 'qualified' : ($status === 'rejected' ? 'eliminated' : 'pending') ?>"><?= $e($status) ?></span></p>
            <?php if ($status === 'pending'): ?><p class="state-message state-waiting">Aguardando aprovação</p><?php endif; ?>
            <?php if ($status === 'rejected'): ?><p class="state-message state-rejected">Solicitação recusada</p><?php endif; ?>
            <div class="quick-actions compact">
                <?php if ($status === 'approved'): ?><a class="button" href="/liquidity/games/<?= (int)$row['game_id'] ?>/my-team">Painel da equipe</a><?php endif; ?>
                <a class="button button-secondary" href="/liquidity/games/<?= (int)$row['game_id'] ?>/arena">Arena pública</a>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
</section>
