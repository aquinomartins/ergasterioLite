<?php $artworks = $artworks ?? []; ?>
<section class="section-heading">
    <div>
        <span class="eyebrow">Catálogo</span>
        <h1>Obras publicadas</h1>
        <p>Uma vitrine inicial para alimentar os próximos mercados preditivos do produto.</p>
    </div>
    <?php if ($currentUser): ?>
        <a class="button" href="/artworks/create">Cadastrar obra</a>
    <?php endif; ?>
</section>

<?php if ($artworks === []): ?>
    <section class="card empty-state">
        <h2>Nenhuma obra cadastrada ainda.</h2>
        <p>Cadastre a primeira obra para começar a estruturar o catálogo do projeto.</p>
    </section>
<?php else: ?>
    <section class="card-grid card-grid-3">
        <?php foreach ($artworks as $artwork): ?>
            <article class="card artwork-card">
                <div class="artwork-thumb-wrap">
                    <img src="<?= htmlspecialchars($artwork['image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($artwork['title'], ENT_QUOTES, 'UTF-8') ?>" class="artwork-thumb">
                </div>
                <h2><?= htmlspecialchars($artwork['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="meta-line">por <a href="/artists/<?= htmlspecialchars($artwork['artist_slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($artwork['artist_name'], ENT_QUOTES, 'UTF-8') ?></a></p>
                <p><?= htmlspecialchars(mb_strimwidth((string) $artwork['description'], 0, 150, '...'), ENT_QUOTES, 'UTF-8') ?></p>
                <a class="button button-secondary" href="/artworks/<?= htmlspecialchars($artwork['slug'], ENT_QUOTES, 'UTF-8') ?>">Ver detalhes</a>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
