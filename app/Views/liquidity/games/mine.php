<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$statusLabels = [
    'pending' => 'Aguardando aprovação',
    'approved' => 'Aprovado',
    'rejected' => 'Solicitação recusada',
    'removed' => 'Removido',
    'waiting' => 'Aguardando jogadores',
    'active' => 'Em andamento',
    'finished' => 'Encerrado',
    'draft' => 'Rascunho',
    'semifinal' => 'Semifinal',
    'closed' => 'Encerrado',
];
$statusClass = static function (string $status): string {
    return match ($status) {
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        'removed' => 'status-removed',
        'active', 'semifinal' => 'status-active',
        'finished', 'closed' => 'status-finished',
        default => 'status-pending',
    };
};
$label = static fn($status) => $statusLabels[(string)$status] ?? ucfirst((string)$status);
$formatDate = static function ($value): string {
    if (empty($value)) { return ''; }
    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : (string)$value;
};
?>
<section class="liquidity-card user-games-hero">
    <p class="liquidity-eyebrow">Piscina de Liquidez</p>
    <h1>Meus jogos</h1>
    <p>Crie partidas, entre com código e acompanhe os jogos em que você participa.</p>
</section>

<section class="liquidity-card quick-actions-card">
    <h2>Ações rápidas</h2>
    <div class="quick-actions">
        <a class="button" href="/liquidity/create">Criar novo jogo</a>
        <a class="button button-secondary" href="/liquidity/join">Entrar com código</a>
        <a class="button button-secondary" href="/liquidity/arenas">Ver arenas públicas</a>
    </div>
</section>

<section class="liquidity-card game-section">
    <div class="game-card-header">
        <div>
            <h2>Jogos que eu organizo</h2>
            <p>Use esta área para aprovar participantes, acompanhar equipes e controlar as rodadas.</p>
        </div>
    </div>

    <?php if (empty($ownedGames)): ?>
        <div class="empty-state">
            <p>Você ainda não organiza nenhum jogo.</p>
            <a class="button" href="/liquidity/create">Criar meu primeiro jogo</a>
        </div>
    <?php else: ?>
        <div class="game-list">
            <?php foreach ($ownedGames as $game): $gameStatus = (string)($game['status'] ?? 'waiting'); ?>
                <article class="game-card game-summary-card">
                    <div class="game-card-header">
                        <div>
                            <h3><?= $e($game['title'] ?? 'Jogo sem título') ?></h3>
                            <p>Código de convite: <strong class="invite-code"><?= $e($game['invite_code'] ?? '') ?></strong></p>
                        </div>
                        <span class="status-badge <?= $e($statusClass($gameStatus)) ?>"><?= $e($label($gameStatus)) ?></span>
                    </div>
                    <div class="game-meta">
                        <span>Rodada atual: <strong><?= (int)($game['current_round'] ?? 1) ?></strong></span>
                        <span>Máximo de rodadas: <strong><?= (int)($game['max_rounds'] ?? 0) ?></strong></span>
                        <span>Participantes pendentes: <strong><?= (int)($game['pending_participants_count'] ?? 0) ?></strong></span>
                        <span>Participantes aprovados: <strong><?= (int)($game['approved_participants_count'] ?? 0) ?></strong></span>
                        <span>Equipes: <strong><?= (int)($game['teams_count'] ?? 0) ?></strong></span>
                    </div>
                    <div class="quick-actions compact">
                        <a class="button" href="/liquidity/games/<?= (int)$game['id'] ?>/teacher">Painel do professor</a>
                        <a class="button button-secondary" href="/liquidity/games/<?= (int)$game['id'] ?>/arena">Arena pública</a>
                        <span class="button button-secondary copy-code-button" aria-label="Código de convite">Copiar código: <?= $e($game['invite_code'] ?? '') ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="liquidity-card game-section">
    <div class="game-card-header">
        <div>
            <h2>Jogos em que participo</h2>
            <p>Use esta área para acessar o Painel da Equipe e acompanhar sua situação no jogo.</p>
        </div>
    </div>

    <?php if (empty($participantGames)): ?>
        <div class="empty-state">
            <p>Você ainda não entrou em nenhum jogo.</p>
            <a class="button" href="/liquidity/join">Entrar com código</a>
        </div>
    <?php else: ?>
        <div class="game-list">
            <?php foreach ($participantGames as $row): $participationStatus = (string)($row['status'] ?? 'pending'); $gameStatus = (string)($row['game_status'] ?? 'waiting'); ?>
                <article class="game-card game-summary-card">
                    <div class="game-card-header">
                        <div>
                            <h3><?= $e($row['title'] ?? 'Jogo sem título') ?></h3>
                            <p>Código do jogo: <strong class="invite-code"><?= $e($row['invite_code'] ?? '') ?></strong></p>
                        </div>
                        <span class="status-badge <?= $e($statusClass($participationStatus)) ?>"><?= $e($label($participationStatus)) ?></span>
                    </div>
                    <div class="game-meta">
                        <span>Status do jogo: <strong><?= $e($label($gameStatus)) ?></strong></span>
                        <span>Rodada atual: <strong><?= (int)($row['current_round'] ?? 1) ?></strong></span>
                        <span>Máximo de rodadas: <strong><?= (int)($row['max_rounds'] ?? 0) ?></strong></span>
                        <?php if (!empty($row['team_name'])): ?><span>Equipe: <strong><?= $e($row['team_name']) ?></strong></span><?php endif; ?>
                        <?php if (!empty($row['role'])): ?><span>Papel no jogo: <strong><?= $e($row['role'] === 'player' ? 'Jogador' : $row['role']) ?></strong></span><?php endif; ?>
                        <?php if (!empty($row['joined_at'])): ?><span>Entrada: <strong><?= $e($formatDate($row['joined_at'])) ?></strong></span><?php endif; ?>
                    </div>

                    <?php if ($participationStatus === 'pending'): ?>
                        <p class="state-message state-waiting">Sua entrada foi solicitada. Aguarde o professor aprovar sua participação.</p>
                    <?php elseif ($participationStatus === 'approved'): ?>
                        <p class="state-message state-approved">Você já pode jogar pelo Painel da Equipe.</p>
                    <?php elseif ($participationStatus === 'rejected'): ?>
                        <p class="state-message state-rejected">Sua solicitação para este jogo foi recusada.</p>
                    <?php elseif ($participationStatus === 'removed'): ?>
                        <p class="state-message state-removed">Você não participa mais deste jogo.</p>
                    <?php endif; ?>

                    <div class="quick-actions compact">
                        <?php if ($participationStatus === 'approved'): ?>
                            <a class="button" href="/liquidity/games/<?= (int)$row['game_id'] ?>/my-team">Painel da equipe</a>
                            <a class="button button-secondary" href="/liquidity/games/<?= (int)$row['game_id'] ?>/arena">Arena pública</a>
                        <?php elseif ($participationStatus === 'pending'): ?>
                            <a class="button button-secondary" href="/liquidity/games/<?= (int)$row['game_id'] ?>/arena">Arena pública</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
