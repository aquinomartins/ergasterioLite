<?php $artwork = $artwork ?? []; ?>
<section class="card artwork-detail">
    <div class="artwork-detail-media">
        <img src="<?= htmlspecialchars((string) ($artwork['image_path'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($artwork['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="artwork-detail-image">
    </div>
    <div>
        <span class="eyebrow">Obra</span>
        <h1><?= htmlspecialchars((string) ($artwork['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="meta-line">
            artista:
            <a href="/artists/<?= htmlspecialchars((string) ($artwork['artist_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars((string) ($artwork['artist_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </a>
        </p>
        <p class="lead-text"><?= nl2br(htmlspecialchars((string) ($artwork['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
    </div>
</section>
