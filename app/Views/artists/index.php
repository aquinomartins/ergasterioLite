<?php $artists = $artists ?? []; ?>
<section class="section-heading">
    <div>
        <span class="eyebrow">Rede criativa</span>
        <h1>Artistas em destaque</h1>
        <p>Explore os perfis já cadastrados e navegue pelas obras publicadas na plataforma.</p>
    </div>
    <?php if ($currentUser): ?>
        <a class="button" href="/artists/create">Cadastrar artista</a>
    <?php endif; ?>
</section>

<?php if ($artists === []): ?>
    <section class="card empty-state">
        <h2>Nenhum artista cadastrado ainda.</h2>
        <p>Seja o primeiro a publicar um perfil artístico na base do Ergastério Lite.</p>
    </section>
<?php else: ?>
    <section class="card-grid card-grid-3">
        <?php foreach ($artists as $artist): ?>
            <article class="card entity-card">
                <span class="eyebrow">Artista</span>
                <h2><?= htmlspecialchars($artist['display_name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars(mb_strimwidth((string) $artist['biography'], 0, 180, '...'), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="meta-line">
                    <span><?= (int) ($artist['artworks_count'] ?? 0) ?> obra(s)</span>
                </div>
                <a class="button button-secondary" href="/artists/<?= htmlspecialchars($artist['slug'], ENT_QUOTES, 'UTF-8') ?>">Ver detalhes</a>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
