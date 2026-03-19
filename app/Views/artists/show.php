<?php
$artist = $artist ?? [];
$artworks = $artworks ?? [];
?>
<section class="card detail-hero">
    <span class="eyebrow">Artista</span>
    <h1><?= htmlspecialchars((string) ($artist['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="lead-text"><?= nl2br(htmlspecialchars((string) ($artist['biography'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
</section>

<section class="section-heading compact-heading">
    <div>
        <h2>Obras relacionadas</h2>
        <p>Produções já vinculadas a este artista.</p>
    </div>
    <?php if ($currentUser): ?>
        <a class="button" href="/artworks/create">Cadastrar obra</a>
    <?php endif; ?>
</section>

<?php if ($artworks === []): ?>
    <section class="card empty-state">
        <p>Este artista ainda não possui obras cadastradas.</p>
    </section>
<?php else: ?>
    <section class="card-grid card-grid-3">
        <?php foreach ($artworks as $artwork): ?>
            <article class="card artwork-card">
                <div class="artwork-thumb-wrap">
                    <img src="<?= htmlspecialchars($artwork['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($artwork['title'], ENT_QUOTES, 'UTF-8') ?>" class="artwork-thumb">
                </div>
                <h3><?= htmlspecialchars($artwork['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars(mb_strimwidth((string) $artwork['description'], 0, 140, '...'), ENT_QUOTES, 'UTF-8') ?></p>
                <a class="button button-secondary" href="/artworks/<?= htmlspecialchars($artwork['slug'], ENT_QUOTES, 'UTF-8') ?>">Ver obra</a>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
