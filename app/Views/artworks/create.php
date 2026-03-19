<?php
use App\Core\Csrf;
$artists = $artists ?? [];
$old = $old ?? [];
$errors = $errors ?? [];
?>
<section class="card narrow">
    <span class="eyebrow">Nova obra</span>
    <h1>Cadastrar obra</h1>
    <p>Associe a obra a um artista existente e envie uma imagem JPG ou PNG de até 5 MB.</p>

    <?php if ($artists === []): ?>
        <div class="empty-state inline-state">
            <p>Cadastre um artista antes de publicar uma obra.</p>
            <a class="button" href="/artists/create">Cadastrar artista</a>
        </div>
    <?php else: ?>
        <form method="POST" action="/artworks" enctype="multipart/form-data" class="form-grid">
            <?= Csrf::input() ?>
            <label>
                <span>Artista</span>
                <select name="artist_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($artists as $artist): ?>
                        <option value="<?= (int) $artist['id'] ?>" <?= (string) ($old['artist_id'] ?? '') === (string) $artist['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($artist['display_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php foreach ($errors['artist_id'] ?? [] as $message): ?>
                    <small class="error-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endforeach; ?>
            </label>

            <label>
                <span>Título</span>
                <input type="text" name="title" value="<?= htmlspecialchars((string) ($old['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="160" required>
                <?php foreach ($errors['title'] ?? [] as $message): ?>
                    <small class="error-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endforeach; ?>
            </label>

            <label>
                <span>Descrição</span>
                <textarea name="description" rows="7" maxlength="3000" required><?= htmlspecialchars((string) ($old['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                <?php foreach ($errors['description'] ?? [] as $message): ?>
                    <small class="error-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endforeach; ?>
            </label>

            <label>
                <span>Imagem</span>
                <input type="file" name="image" accept="image/png,image/jpeg" required>
                <?php foreach ($errors['image'] ?? [] as $message): ?>
                    <small class="error-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></small>
                <?php endforeach; ?>
            </label>

            <button type="submit" class="button">Salvar obra</button>
        </form>
    <?php endif; ?>
</section>
