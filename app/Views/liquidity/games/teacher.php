<?php $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); $csrf = \App\Core\Csrf::input(); ?>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Painel do professor</p>
    <h1><?= $e($game['title']) ?></h1>
    <p><strong>Código de convite:</strong> <?= $e($game['invite_code']) ?></p>
    <p><strong>Status:</strong> <?= $e($game['status']) ?></p>
    <p><a href="/liquidity/<?= (int)$game['id'] ?>/projector">Arena pública</a> · <a href="/liquidity/<?= (int)$game['id'] ?>">Painel/controle do jogo</a> · <a href="/liquidity/my-games">Meus jogos</a></p>
</section>
<section class="liquidity-card"><h2>Participantes pendentes</h2>
<?php if (!$pendingParticipants): ?><p>Nenhuma solicitação pendente.</p><?php endif; ?>
<?php foreach ($pendingParticipants as $p): ?><article><?= $e($p['display_name'] ?: $p['username'] ?: $p['email']) ?>
<form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/participants/<?= (int)$p['id'] ?>/approve" class="inline-form"><?= $csrf ?><button>Aprovar</button></form>
<form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/participants/<?= (int)$p['id'] ?>/reject" class="inline-form"><?= $csrf ?><button>Rejeitar</button></form></article><?php endforeach; ?></section>
<section class="liquidity-card"><h2>Participantes aprovados</h2><?php if (!$approvedParticipants): ?><p>Nenhum participante aprovado.</p><?php endif; ?><?php foreach ($approvedParticipants as $p): ?><article><?= $e($p['display_name'] ?: $p['username'] ?: $p['email']) ?> — <?= $e($p['team_name'] ?? 'Equipe') ?></article><?php endforeach; ?></section>
<section class="liquidity-card"><h2>Participantes rejeitados</h2><?php if (!$rejectedParticipants): ?><p>Nenhum participante rejeitado.</p><?php endif; ?><?php foreach ($rejectedParticipants as $p): ?><article><?= $e($p['display_name'] ?: $p['username'] ?: $p['email']) ?></article><?php endforeach; ?></section>
