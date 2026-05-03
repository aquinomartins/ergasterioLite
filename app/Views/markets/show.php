<?php
use App\Core\Csrf;

$market = $market ?? [];
$options = $market['options'] ?? [];
$snapshots = $market['snapshots'] ?? [];
$canManageMarkets = $canManageMarkets ?? false;
$isAuthenticated = $isAuthenticated ?? false;
$errors = $errors ?? [];
$status = (string) ($market['status'] ?? 'draft');
$userBalance = $userBalance ?? null;
$trades = $trades ?? [];
$resolution = $resolution ?? null;
$marketPayouts = $marketPayouts ?? [];
$myPayouts = $myPayouts ?? [];
$userPayoutInMarket = null;
foreach ($myPayouts as $myPayout) {
    if ((int) ($myPayout['market_id'] ?? 0) === (int) ($market['id'] ?? 0)) {
        $userPayoutInMarket = $myPayout;
        break;
    }
}
?>
<section class="card detail-hero market-detail-hero">
    <div class="status-row">
        <span class="status-badge status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
        <span class="meta-pill"><?= htmlspecialchars((string) ($market['market_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <span class="eyebrow">Mercado</span>
    <h1><?= htmlspecialchars((string) ($market['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="lead-text"><?= nl2br(htmlspecialchars((string) ($market['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>

    <dl class="definition-list market-meta-grid">
        <div>
            <dt>Fecha em</dt>
            <dd><?= date('d/m/Y H:i', strtotime((string) ($market['closes_at'] ?? 'now'))) ?></dd>
        </div>
        <div>
            <dt>Modo de resolução</dt>
            <dd><?= htmlspecialchars((string) ($market['resolution_mode'] ?? 'manual'), ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
        <div>
            <dt>Criado por</dt>
            <dd><?= htmlspecialchars((string) ($market['creator_name'] ?? $market['creator_email'] ?? 'Equipe'), ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
        <div>
            <dt>Resultado</dt>
            <dd><?= htmlspecialchars((string) ($market['resolved_option_label'] ?? 'Ainda não resolvido'), ENT_QUOTES, 'UTF-8') ?></dd>
        </div>
    </dl>


    <article class="card">
        <div class="section-heading compact-heading market-inline-heading">
            <div>
                <h2>Resultado e payouts</h2>
                <p>Resumo da resolução e distribuição de payoff deste mercado.</p>
            </div>
        </div>

        <?php if ($status !== 'resolved'): ?>
            <p class="helper-text">O mercado ainda não foi resolvido.</p>
        <?php else: ?>
            <p class="helper-text">
                Vencedora: <strong><?= htmlspecialchars((string) ($market['resolved_option_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>.
                <?php if (! empty($resolution['resolved_at'])): ?>
                    Resolvido em <?= date('d/m/Y H:i', strtotime((string) $resolution['resolved_at'])) ?>
                <?php endif; ?>
            </p>

            <?php if (! empty($resolution['resolution_notes'])): ?>
                <p class="helper-text">Observações: <?= nl2br(htmlspecialchars((string) $resolution['resolution_notes'], ENT_QUOTES, 'UTF-8')) ?></p>
            <?php endif; ?>

            <?php if ($userPayoutInMarket !== null): ?>
                <p class="helper-text">Seu payoff líquido: <strong><?= number_format((float) $userPayoutInMarket['net_amount'], 2, ',', '.') ?></strong>.</p>
            <?php elseif ($isAuthenticated): ?>
                <p class="helper-text">Você não recebeu payout neste mercado.</p>
            <?php endif; ?>

            <?php if ($canManageMarkets): ?>
                <?php if ($marketPayouts === []): ?>
                    <p class="helper-text">Nenhum payout registrado.</p>
                <?php else: ?>
                    <ul class="trade-list">
                        <?php foreach ($marketPayouts as $payout): ?>
                            <li>
                                <span><?= htmlspecialchars((string) ($payout['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) ($payout['option_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <strong><?= number_format((float) $payout['net_amount'], 2, ',', '.') ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </article>

</section>

<section class="section-heading compact-heading">
    <div>
        <h2>Opções e probabilidades</h2>
        <p>Distribuição atual baseada no peso acumulado de cada opção.</p>
    </div>
</section>

<section class="card option-list-card">
    <div class="option-list">
        <?php foreach ($options as $option): ?>
            <article class="market-option-row">
                <div>
                    <h3><?= htmlspecialchars((string) $option['label'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="meta-line">
                        <?php if (! empty($option['artwork_title'])): ?>
                            Obra vinculada:
                            <a href="/artworks/<?= htmlspecialchars((string) $option['artwork_slug'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) $option['artwork_title'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php elseif (! empty($option['artist_name'])): ?>
                            Artista vinculado:
                            <a href="/artists/<?= htmlspecialchars((string) $option['artist_slug'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) $option['artist_name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php else: ?>
                            Sem vínculo externo.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="option-metrics">
                    <strong><?= number_format(((float) $option['probability_value']) * 100, 2, ',', '.') ?>%</strong>
                    <span>Peso <?= number_format((float) $option['weight_value'], 2, ',', '.') ?></span>
                </div>
                <div class="probability-bar">
                    <span style="width: <?= max(4, min(100, (int) round(((float) $option['probability_value']) * 100))) ?>%"></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>


<section class="card participation-card">
    <div class="section-heading compact-heading market-inline-heading">
        <div>
            <h2>Participar do mercado</h2>
            <p>Cada participação aumenta o peso da opção escolhida e recalcula as probabilidades.</p>
        </div>
    </div>

    <?php if (! empty($errors['position'])): ?>
        <div class="inline-errors">
            <?php foreach ($errors['position'] as $message): ?>
                <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($isAuthenticated && $userBalance !== null): ?>
        <div class="balance-pill">Saldo disponível: <strong>R$ <?= number_format((float) $userBalance, 2, ',', '.') ?></strong></div>
    <?php endif; ?>

    <?php if ($status !== 'open'): ?>
        <p class="helper-text">Participações ficam disponíveis apenas quando o mercado está aberto.</p>
    <?php elseif (! $isAuthenticated): ?>
        <p class="helper-text">
            Entre para participar deste mercado.
            <a href="/login">/login</a>.
        </p>
    <?php else: ?>
        <form method="POST" action="/markets/<?= (int) $market['id'] ?>/positions" class="form-grid" data-position-form>
            <?= Csrf::input() ?>
            <label>
                <span>Opção</span>
                <select name="option_id" required data-position-option>
                    <option value="">Selecione</option>
                    <?php foreach ($options as $option): ?>
                        <option
                            value="<?= (int) $option['id'] ?>"
                            data-current-probability="<?= (float) $option['probability_value'] ?>"
                            data-current-weight="<?= (float) $option['weight_value'] ?>"
                        >
                            <?= htmlspecialchars((string) $option['label'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php foreach ($errors['option_id'] ?? [] as $message): ?>
                    <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endforeach; ?>
            </label>

            <label>
                <span>Quantidade de shares</span>
                <input type="number" name="shares_amount" min="1" step="1" value="1" required placeholder="Ex: 1" data-position-shares>
                <?php foreach ($errors['shares_amount'] ?? [] as $message): ?>
                    <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endforeach; ?>
            </label>

            <p class="helper-text" data-position-preview>
                Escolha uma opção e uma quantidade para visualizar o impacto estimado da participação.
            </p>

            <button type="submit" class="button">Participar agora</button>
        </form>
    <?php endif; ?>
</section>

<section class="grid-two market-detail-grid">
    <article class="card">
        <div class="section-heading compact-heading market-inline-heading">
            <div>
                <h2>Snapshots</h2>
                <p>Histórico simplificado das probabilidades persistidas.</p>
            </div>
        </div>

        <?php if ($snapshots === []): ?>
            <div class="empty-state inline-state">
                <p>Nenhum snapshot registrado ainda.</p>
            </div>
        <?php else: ?>
            <div class="snapshot-list">
                <?php foreach ($snapshots as $snapshot): ?>
                    <article class="snapshot-card">
                        <header>
                            <strong><?= date('d/m/Y H:i', strtotime((string) $snapshot['created_at'])) ?></strong>
                        </header>
                        <ul>
                            <?php foreach (($snapshot['decoded_snapshot']['options'] ?? []) as $snapshotOption): ?>
                                <li>
                                    <span><?= htmlspecialchars((string) ($snapshotOption['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong><?= number_format(((float) ($snapshotOption['probability'] ?? 0)) * 100, 2, ',', '.') ?>%</strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="card">
        <div class="section-heading compact-heading market-inline-heading">
            <div>
                <h2>Histórico de trades</h2>
                <p>Últimas participações registradas neste mercado.</p>
            </div>
        </div>

        <?php if ($trades === []): ?>
            <div class="empty-state inline-state"><p>Nenhuma participação registrada ainda.</p></div>
        <?php else: ?>
            <ul class="trade-list">
                <?php foreach (array_slice($trades, 0, 8) as $trade): ?>
                    <li>
                        <span><?= htmlspecialchars((string) $trade['user_name'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars((string) $trade['option_label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <strong><?= number_format((float) $trade['shares_amount'], 2, ',', '.') ?> shares</strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>

    <article class="card">
        <div class="section-heading compact-heading market-inline-heading">
            <div>
                <h2>Ações administrativas</h2>
                <p>Disponíveis apenas para perfis com status <code>admin</code> ou <code>curator</code>.</p>
            </div>
        </div>

        <?php if (! $canManageMarkets): ?>
            <div class="empty-state inline-state">
                <p>Você pode acompanhar o mercado, mas as ações de curadoria ficam restritas a perfis autorizados.</p>
            </div>
        <?php else: ?>
            <div class="admin-action-stack">
                <?php if ($status === 'draft'): ?>
                    <form method="POST" action="/markets/<?= (int) $market['id'] ?>/publish">
                        <?= Csrf::input() ?>
                        <button type="submit" class="button">Publicar mercado</button>
                    </form>
                <?php endif; ?>

                <?php if ($status === 'open'): ?>
                    <form method="POST" action="/markets/<?= (int) $market['id'] ?>/close">
                        <?= Csrf::input() ?>
                        <button type="submit" class="button button-secondary">Fechar mercado</button>
                    </form>
                <?php endif; ?>

                <?php if (in_array($status, ['open', 'closed'], true)): ?>
                    <form method="POST" action="/markets/<?= (int) $market['id'] ?>/resolve" class="form-grid">
                        <?= Csrf::input() ?>
                        <label>
                            <span>Opção vencedora</span>
                            <select name="resolved_option_id" required>
                                <option value="">Selecione</option>
                                <?php foreach ($options as $option): ?>
                                    <option value="<?= (int) $option['id'] ?>"><?= htmlspecialchars((string) $option['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php foreach ($errors['resolved_option_id'] ?? [] as $message): ?>
                                <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endforeach; ?>
                        </label>
                        <label>
                            <span>Observações (opcional)</span>
                            <textarea name="resolution_notes" rows="3" placeholder="Justificativa da resolução"></textarea>
                            <?php foreach ($errors['resolution_notes'] ?? [] as $message): ?>
                                <small class="error-text"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endforeach; ?>
                        </label>
                        <button type="submit" class="button">Resolver mercado</button>
                    </form>
                <?php endif; ?>

                <?php if ($status === 'resolved'): ?>
                    <p class="helper-text">Mercado resolvido em favor de <strong><?= htmlspecialchars((string) ($market['resolved_option_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </article>
</section>
