<?php
$old = $old ?? [];
$errors = $errors ?? [];
$user = $user ?? [];
?>
<section class="auth-card card narrow">
    <div>
        <span class="eyebrow">Perfil</span>
        <h1>Editar perfil</h1>
        <p>Atualize os dados básicos que serão usados nos próximos módulos do produto.</p>
    </div>

    <form method="POST" action="/profile/edit" class="form-grid">
        <?= \App\Core\Csrf::input() ?>
        <label>
            <span>Nome de exibição</span>
            <input type="text" name="display_name" value="<?= htmlspecialchars((string) ($old['display_name'] ?? $user['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php foreach ($errors['display_name'] ?? [] as $error): ?>
                <small class="error-text"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <label>
            <span>Username</span>
            <input type="text" name="username" value="<?= htmlspecialchars((string) ($old['username'] ?? $user['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php foreach ($errors['username'] ?? [] as $error): ?>
                <small class="error-text"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <label>
            <span>Bio</span>
            <textarea name="bio" rows="5" placeholder="Conte brevemente sobre sua relação com arte, curadoria ou colecionismo."><?= htmlspecialchars((string) ($old['bio'] ?? $user['bio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php foreach ($errors['bio'] ?? [] as $error): ?>
                <small class="error-text"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <button type="submit" class="button">Salvar perfil</button>
    </form>
</section>
