<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$csrf = \App\Core\Csrf::input();
$game = $game ?? [];
$teams = $teams ?? [];
$pendingParticipants = $pendingParticipants ?? [];
$approvedParticipants = $approvedParticipants ?? [];
$rejectedParticipants = $rejectedParticipants ?? [];
$actedByTeam = $actedByTeam ?? $actionsThisRound ?? [];
$lastActionByTeam = $lastActionByTeam ?? [];
$currentRoundState = $currentRoundState ?? ['status' => 'open'];
$roundStats = $roundStats ?? ['active_teams' => 0, 'acted' => 0, 'pending' => 0, 'percent' => 0, 'decided_teams' => [], 'pending_teams' => []];
$poolStats = $poolStats ?? ['maintenance_fee' => 100, 'total_shares' => 0, 'pool_value' => 0, 'estimated_dividend_per_share' => 0];
$pendingProposals = $pendingProposals ?? [];
$recentEvents = $recentEvents ?? $events ?? [];
$currentRound = (int)($currentRound ?? $game['current_round'] ?? 1);
$maxRounds = (int)($maxRounds ?? $game['max_rounds'] ?? 6);
$statusLabel = $statusLabel ?? 'Em acompanhamento';
$valuationRules = $valuationRules ?? ['btc_value' => 100, 'nft_value' => 2000, 'share_value' => 2000];
$roundStatus = ($currentRoundState['status'] ?? 'open') === 'closed' ? 'encerrada' : 'aberta';
$participantName = static fn($p) => trim((string)($p['display_name'] ?? $p['username'] ?? $p['name'] ?? $p['email'] ?? 'Participante'));
$participantStatus = static fn($s) => ['pending'=>'Aguardando aprovação','approved'=>'Aprovado','rejected'=>'Recusado','removed'=>'Removido'][(string)$s] ?? 'Em análise';
$proposalStatus = static fn($s) => ['pending_counterparty'=>'Aguardando resposta','executed'=>'Executada','rejected'=>'Rejeitada','cancelled'=>'Cancelada','master_executed'=>'Executada pelo Master Gold'][(string)$s] ?? 'Em análise';
$assetLabel = static fn($s) => ['btc'=>'BTC','nft'=>'NFT','share'=>'Cota','cash'=>'Caixa'][(string)$s] ?? 'Ativo';
$actionLabel = static fn($a) => ['deposit_nft'=>'Depositou NFT','withdraw_nft_btc'=>'Retirou NFT com BTC','withdraw_nft_cash'=>'Retirou NFT com caixa','pass'=>'Passou a vez','buy_btc'=>'Comprou BTC','sell_btc'=>'Vendeu BTC','buy_nft'=>'Comprou NFT','sell_nft'=>'Vendeu NFT','buy_share'=>'Comprou cota','sell_share'=>'Vendeu cota'][(string)$a] ?? 'Decisão registrada';
?>
<div class="teacher-dashboard">
    <section class="teacher-hero">
        <div>
            <p class="liquidity-eyebrow">Central de controle da partida</p>
            <h1 class="teacher-title">Painel do Professor</h1>
            <p class="teacher-subtitle"><?= $e($game['title'] ?? 'Jogo sem título') ?></p>
            <div class="teacher-meta">
                <span>Código: <strong id="invite-code"><?= $e($game['invite_code'] ?? 'Sem código') ?></strong></span>
                <span>Rodada <strong><?= $currentRound ?></strong> de <strong><?= $maxRounds ?></strong></span>
                <span>Status: <strong><?= $e($statusLabel) ?></strong></span>
                <span>Equipes: <strong><?= count($teams) ?></strong></span>
                <span>Pendentes: <strong><?= count($pendingParticipants) ?></strong></span>
            </div>
        </div>
        <div class="teacher-toolbar">
            <a class="button secondary" href="/liquidity/my-games">Meus jogos</a>
            <a class="button" href="/liquidity/games/<?= (int)($game['id'] ?? 0) ?>/arena">Arena pública</a>
            <button type="button" class="button ghost" data-copy-target="invite-code">Copiar código</button>
        </div>
    </section>

    <section class="quick-actions-grid" aria-label="Ações rápidas">
        <a class="quick-action-card" href="#participantes"><strong>Compartilhar código</strong><span>Envie este código para os participantes entrarem no jogo.</span></a>
        <a class="quick-action-card" href="/liquidity/games/<?= (int)($game['id'] ?? 0) ?>/arena"><strong>Abrir Arena</strong><span>Use em projetor para acompanhar ranking, piscina e feed.</span></a>
        <a class="quick-action-card" href="#progresso"><strong>Acompanhar rodada</strong><span>Veja quem já decidiu e quem ainda está pendente.</span></a>
    </section>

    <section id="participantes" class="teacher-section">
        <div class="teacher-section-header"><div><p class="liquidity-eyebrow">Entrada no jogo</p><h2>Participantes</h2></div></div>
        <div class="participants-grid">
            <article><h3>Pendentes de aprovação</h3><?php if (!$pendingParticipants): ?><p class="empty-state">Não há participantes aguardando aprovação.</p><?php endif; ?><?php foreach ($pendingParticipants as $p): ?><div class="participant-card"><div><strong><?= $e($participantName($p)) ?></strong><span><?= $e($p['joined_at'] ?? '') ?></span></div><div class="participant-actions"><form method="post" action="/liquidity/games/<?= (int)($game['id'] ?? 0) ?>/participants/<?= (int)($p['id'] ?? 0) ?>/approve" class="inline-form"><?= $csrf ?><button>Aprovar</button></form><form method="post" action="/liquidity/games/<?= (int)($game['id'] ?? 0) ?>/participants/<?= (int)($p['id'] ?? 0) ?>/reject" class="inline-form"><?= $csrf ?><button class="button secondary">Rejeitar</button></form></div></div><?php endforeach; ?></article>
            <article><h3>Aprovados</h3><?php if (!$approvedParticipants): ?><p class="empty-state">Nenhum participante aprovado ainda.</p><?php endif; ?><?php foreach ($approvedParticipants as $p): ?><div class="participant-card"><div><strong><?= $e($participantName($p)) ?></strong><span>Equipe: <?= $e($p['team_name'] ?? 'Não vinculada') ?></span></div><span class="status-badge status-approved"><?= $e($participantStatus($p['status'] ?? 'approved')) ?></span></div><?php endforeach; ?></article>
            <article><h3>Recusados/removidos</h3><?php if (!$rejectedParticipants): ?><p class="empty-state">Nenhum participante recusado ou removido.</p><?php endif; ?><?php foreach ($rejectedParticipants as $p): ?><div class="participant-card"><strong><?= $e($participantName($p)) ?></strong><span class="status-badge status-pending"><?= $e($participantStatus($p['status'] ?? 'rejected')) ?></span></div><?php endforeach; ?></article>
        </div>
    </section>

    <section class="teacher-section">
        <div class="teacher-section-header"><div><p class="liquidity-eyebrow">Visão operacional</p><h2>Equipes</h2></div><p class="public-note">Patrimônio estimado considera BTC = <?= $money($valuationRules['btc_value'] ?? 100) ?>, NFT = <?= $money($valuationRules['nft_value'] ?? 2000) ?> e cota = <?= $money($valuationRules['share_value'] ?? 2000) ?>.</p></div>
        <?php if (!$teams): ?><p class="empty-state">Ainda não há equipes criadas neste jogo.</p><?php endif; ?>
        <div class="teams-grid"><?php foreach ($teams as $team): $tid=(int)($team['id'] ?? 0); $last=$lastActionByTeam[$tid] ?? null; ?><article class="team-control-card"><div class="team-card-top"><h3><?= $e($team['name'] ?? 'Equipe') ?></h3><span class="status-badge <?= ($team['status'] ?? 'active') === 'active' ? 'status-active' : 'status-finished' ?>"><?= $e(($team['status'] ?? 'active') === 'active' ? 'Ativa' : 'Inativa') ?></span></div><dl><div><dt>Caixa</dt><dd><?= $money($team['cash_balance'] ?? $team['cash'] ?? 0) ?></dd></div><div><dt>BTC</dt><dd><?= $e($team['btc_balance'] ?? $team['btc'] ?? 0) ?></dd></div><div><dt>NFTs em mãos</dt><dd><?= (int)($team['nft_balance'] ?? $team['nft_in_hand'] ?? 0) ?></dd></div><div><dt>Cotas</dt><dd><?= (int)($team['pool_shares'] ?? 0) ?></dd></div><div><dt>Patrimônio estimado</dt><dd><?= $money($team['estimated_wealth'] ?? 0) ?></dd></div></dl><p>Ação da rodada: <strong><?= !empty($actedByTeam[$tid]) ? 'Decisão enviada' : 'Aguardando decisão' ?></strong></p><small>Última ação: <?= $last ? $e($actionLabel($last['action_type'] ?? '') . ' — rodada ' . ($last['round_number'] ?? '-')) : 'nenhuma' ?></small></article><?php endforeach; ?></div>
    </section>

    <section id="progresso" class="teacher-section round-progress">
        <div class="teacher-section-header"><div><p class="liquidity-eyebrow">Rodada atual</p><h2>Progresso da rodada</h2></div><strong><?= (int)($roundStats['acted'] ?? 0) ?> de <?= (int)($roundStats['active_teams'] ?? 0) ?> equipes já enviaram decisão.</strong></div>
        <div class="progress-bar"><span class="progress-bar-fill" style="width: <?= max(0, min(100, (int)($roundStats['percent'] ?? 0))) ?>%"></span></div>
        <div class="round-status-grid"><article><h3>Já decidiram</h3><?php if (empty($roundStats['decided_teams'])): ?><p class="empty-state">Nenhuma equipe enviou decisão nesta rodada.</p><?php endif; ?><ul><?php foreach (($roundStats['decided_teams'] ?? []) as $team): ?><li><?= $e($team['name'] ?? 'Equipe') ?></li><?php endforeach; ?></ul></article><article><h3>Aguardando decisão</h3><?php if (empty($roundStats['pending_teams'])): ?><p class="empty-state">Todas as equipes já decidiram. A rodada pode ser encerrada.</p><?php else: ?><p class="public-note">Ainda há equipes sem decisão. Você pode encerrar mesmo assim apenas se essa for a regra do jogo.</p><?php endif; ?><ul><?php foreach (($roundStats['pending_teams'] ?? []) as $team): ?><li><?= $e($team['name'] ?? 'Equipe') ?></li><?php endforeach; ?></ul></article></div>
    </section>

    <section class="teacher-section market-summary">
        <div class="teacher-section-header"><div><p class="liquidity-eyebrow">Mercado</p><h2>Mercado e propostas</h2></div></div>
        <?php if (!$pendingProposals): ?><p class="empty-state">Não há propostas de mercado no momento.</p><?php else: ?><div class="table-scroll"><table><thead><tr><th>Proponente</th><th>Contraparte</th><th>Ativo</th><th>Quantidade</th><th>Preço unitário</th><th>Total</th><th>Status</th></tr></thead><tbody><?php foreach ($pendingProposals as $p): ?><tr><td><?= $e($p['proposer_name'] ?? 'Equipe') ?></td><td><?= $e($p['counterparty_name'] ?? 'Equipe') ?></td><td><?= $e($assetLabel($p['asset_type'] ?? '')) ?></td><td><?= $e($p['quantity'] ?? 0) ?></td><td><?= $money($p['unit_price'] ?? 0) ?></td><td><?= $money($p['total_price'] ?? 0) ?></td><td><span class="status-badge status-pending"><?= $e($proposalStatus($p['status'] ?? '')) ?></span></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>

    <section class="teacher-section control-panel">
        <div class="teacher-section-header"><div><p class="liquidity-eyebrow">Ação sensível</p><h2>Controle da rodada</h2></div><span class="status-badge <?= $roundStatus === 'encerrada' ? 'status-finished' : 'status-active' ?>">Rodada <?= $e($roundStatus) ?></span></div>
        <p>Ao encerrar a rodada, o sistema poderá aplicar taxa de manutenção, distribuir dividendos da piscina e avançar o jogo conforme as regras já implementadas.</p>
        <div class="liquidity-stat-grid"><article class="liquidity-stat"><span>Taxa de manutenção</span><strong><?= $money($poolStats['maintenance_fee'] ?? 100) ?></strong></article><article class="liquidity-stat"><span>Total de cotas</span><strong><?= (int)($poolStats['total_shares'] ?? 0) ?></strong></article><article class="liquidity-stat"><span>Dividendo estimado por cota</span><strong><?= $money($poolStats['estimated_dividend_per_share'] ?? 0) ?></strong></article><article class="liquidity-stat"><span>Valor estimado da piscina</span><strong><?= $money($poolStats['pool_value'] ?? 0) ?></strong></article></div>
        <?php if ((int)($poolStats['total_shares'] ?? 0) === 0): ?><p class="empty-state">A piscina ainda não possui cotas. Não haverá dividendos estimados nesta rodada.</p><?php endif; ?>
        <form method="post" action="/liquidity/games/<?= (int)($game['id'] ?? 0) ?>/advance-round" class="liquidity-inline-control danger-zone" onsubmit="return confirm('Tem certeza? Esta ação pode aplicar taxa, dividendos e avançar a rodada.');"><?= $csrf ?><button type="submit" <?= $roundStatus === 'encerrada' ? 'disabled' : '' ?>>Encerrar rodada e avançar</button><?php if ($roundStatus === 'encerrada'): ?><small>Esta rodada já foi encerrada e não pode ser aplicada novamente.</small><?php endif; ?></form>
    </section>

    <section class="teacher-section event-feed">
        <div class="teacher-section-header"><div><p class="liquidity-eyebrow">Narrativa</p><h2>Feed do jogo</h2></div></div>
        <?php if (!$recentEvents): ?><p class="empty-state">O feed ainda está vazio. Os eventos aparecerão conforme a partida acontecer.</p><?php endif; ?>
        <?php foreach ($recentEvents as $event): ?><article class="event-item"><span>Rodada <?= (int)($event['round_number'] ?? 0) ?></span><p><?= $e($event['description'] ?? $event['message'] ?? 'Evento registrado no jogo.') ?></p></article><?php endforeach; ?>
    </section>
</div>
<script>
document.querySelectorAll('[data-copy-target]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = document.getElementById(button.dataset.copyTarget);
        if (!target || !navigator.clipboard) return;
        navigator.clipboard.writeText(target.textContent.trim());
        button.textContent = 'Código copiado';
        setTimeout(() => { button.textContent = 'Copiar código'; }, 1800);
    });
});
</script>
