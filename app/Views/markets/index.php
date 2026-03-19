<?php
$markets = $markets ?? [];
$openMarkets = $openMarkets ?? [];
$canManageMarkets = $canManageMarkets ?? false;
?>
<section class="section-heading">
    <div>
        <span class="eyebrow">Mercados</span>
        <h1>Mercados preditivos simplificados</h1>
        <p>Mercados conectados a artistas e obras, com probabilidades proporcionais aos pesos atuais de cada opção.</p>
    </div>
    <?php if ($currentUser): ?>
        <a class="button" href="/markets/create">Criar mercado</a>
    <?php endif; ?>
</section>

<?php if ($openMarkets !== []): ?>
    <section class="card market-highlight">
        <div class="section-heading compact-heading market-inline-heading">
            <div>
                <h2>Abertos agora</h2>
                <p><?= count($openMarkets) ?> mercado(s) aceitando acompanhamento até o fechamento.</p>
            </div>
        </div>
        <div class="card-grid card-grid-3">
            <?php foreach ($openMarkets as $market): ?>
                <article class="card market-card compact-card">
                    <div class="status-row">
                        <span class="status-badge status-<?= htmlspecialchars((string) $market['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $market['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="meta-pill"><?= htmlspecialchars((string) $market['market_type'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h3><?= htmlspecialchars((string) $market['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars(mb_strimwidth((string) $market['description'], 0, 140, '...'), ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="meta-stack">
                        <span>Fecha em <?= date('d/m/Y H:i', strtotime((string) $market['closes_at'])) ?></span>
                        <span><?= (int) ($market['options_count'] ?? 0) ?> opções</span>
                    </div>
                    <a class="button button-secondary" href="/markets/<?= htmlspecialchars((string) $market['slug'], ENT_QUOTES, 'UTF-8') ?>">Ver detalhe</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($markets === []): ?>
    <section class="card empty-state">
        <h2>Nenhum mercado cadastrado ainda.</h2>
        <p>Crie o primeiro mercado para conectar obras, artistas e o histórico probabilístico do Ergastério Lite.</p>
    </section>
<?php else: ?>
    <section class="card-grid card-grid-3">
        <?php foreach ($markets as $market): ?>
            <article class="card market-card">
                <div class="status-row">
                    <span class="status-badge status-<?= htmlspecialchars((string) $market['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $market['status'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="meta-pill"><?= htmlspecialchars((string) $market['market_type'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <h2><?= htmlspecialchars((string) $market['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars(mb_strimwidth((string) $market['description'], 0, 165, '...'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="meta-stack">
                    <span>Fecha em <?= date('d/m/Y H:i', strtotime((string) $market['closes_at'])) ?></span>
                    <span>Criado por <?= htmlspecialchars((string) ($market['creator_name'] ?? $market['creator_email'] ?? 'Equipe'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span><?= (int) ($market['options_count'] ?? 0) ?> opções</span>
                </div>
                <a class="button button-secondary" href="/markets/<?= htmlspecialchars((string) $market['slug'], ENT_QUOTES, 'UTF-8') ?>">Ver detalhe</a>
                <?php if ($canManageMarkets && (string) $market['status'] === 'draft'): ?>
                    <small class="helper-text">Rascunho pronto para publicação.</small>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
