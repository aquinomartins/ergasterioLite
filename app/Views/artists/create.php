<?php
use App\Core\Csrf;
$old = $old ?? [];
$errors = $errors ?? [];
?>
<section class="card narrow">
    <span class="eyebrow">Novo artista</span>
    <h1>Cadastrar artista</h1>
    <p>Crie um perfil artístico com nome público, biografia e slug gerado automaticamente.</p>

    <form method="POST" action="/artists" class="form-grid">
        <?= Csrf::input() ?>
        <label>
            <span>Nome artístico</span>
            <input type="text" name="display_name" value="<?= htmlspecialchars((string) ($old['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="120" required>
            <?php foreach ($errors['display_name'] ?? [] as $message): ?>
                <small class="error-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <label>
            <span>Biografia</span>
            <textarea name="biography" rows="7" maxlength="2000" required><?= htmlspecialchars((string) ($old['biography'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php foreach ($errors['biography'] ?? [] as $message): ?>
                <small class="error-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <button type="submit" class="button">Salvar artista</button>
    </form>
</section>
