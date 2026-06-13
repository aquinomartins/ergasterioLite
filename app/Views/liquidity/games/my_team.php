<?php
$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => 'R$ ' . number_format((float)$v, 2, ',', '.');
$actionLabel = static fn($a) => [
    'deposit_nft' => 'Depositar NFT na piscina',
    'withdraw_nft_btc' => 'Retirar NFT pagando 11 BTC',
    'withdraw_nft_cash' => 'Retirar NFT pagando R$ 2.000',
    'pass' => 'Passar a vez',
][$a] ?? (string)$a;
?>
<section class="liquidity-card">
    <p class="liquidity-eyebrow">Painel da Equipe</p>
    <h1><?= $e($game['title'] ?? 'Jogo') ?></h1>
    <p><strong>Código do jogo:</strong> <?= $e($game['invite_code'] ?? '') ?></p>
    <p><strong>Status do jogo:</strong> <?= $e($game['status'] ?? '') ?></p>
    <p><strong>Você está na Rodada <?= (int)($game['current_round'] ?? 1) ?>.</strong></p>
    <?php if (!empty($message)): ?>
        <p><?= $e($message) ?></p>
    <?php else: ?>
        <h2><?= $e($team['name'] ?? 'Equipe') ?></h2>
        <div class="liquidity-stat-grid">
            <article class="liquidity-stat"><span>Caixa em reais</span><strong><?= $money($team['cash_balance'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>BTC</span><strong><?= $e($team['btc_balance'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>NFTs em mãos</span><strong><?= (int)($team['nft_balance'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>Cotas da piscina</span><strong><?= (int)($team['pool_shares'] ?? 0) ?></strong></article>
            <article class="liquidity-stat"><span>Status da equipe</span><strong><?= $e($team['status'] ?? 'active') ?></strong></article>
        </div>
        <p><strong>Última ação realizada:</strong> <?= !empty($lastAction) ? $e($actionLabel($lastAction['action_type']) . ' — rodada ' . $lastAction['round_number']) : 'Nenhuma ação registrada ainda.' ?></p>
        <p><a href="/liquidity/games/<?= (int)$game['id'] ?>/arena">Arena pública</a></p>
        <section class="liquidity-card">
            <h2>Escolha sua ação da rodada</h2>
            <p>Escolha uma ação. Sua equipe só pode fazer uma ação por rodada.</p>
            <?php if (!empty($hasActed)): ?>
                <p><strong>Sua equipe já realizou uma ação nesta rodada.</strong></p>
            <?php endif; ?>
            <?php $actions = [
                ['deposit_nft', 'Depositar NFT', 'Você entrega 1 NFT, recebe 10 BTC e ganha 1 cota da piscina.'],
                ['withdraw_nft_btc', 'Retirar NFT pagando 11 BTC', 'Você usa 1 cota e paga 11 BTC para recuperar 1 NFT.'],
                ['withdraw_nft_cash', 'Retirar NFT pagando R$ 2.000', 'Você usa 1 cota e paga R$ 2.000 para recuperar 1 NFT.'],
                ['pass', 'Passar a vez', 'Você não altera seus recursos, mas registra sua decisão da rodada.'],
            ]; ?>
            <div class="liquidity-control-grid">
                <?php foreach ($actions as [$type, $label, $help]): ?>
                    <form method="post" action="/liquidity/games/<?= (int)$game['id'] ?>/my-team/action" class="action-card">
                        <?= \App\Core\Csrf::input() ?>
                        <input type="hidden" name="action_type" value="<?= $e($type) ?>">
                        <h3><?= $e($label) ?></h3>
                        <p><?= $e($help) ?></p>
                        <button type="submit" <?= !empty($hasActed) ? 'disabled' : '' ?>><?= $e($label) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    <p><a href="/liquidity/my-games">Meus jogos</a></p>
</section>
