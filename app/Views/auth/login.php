<?php
$old = $old ?? [];
$errors = $errors ?? [];
?>
<section class="auth-card card narrow">
    <div>
        <span class="eyebrow">Acesso</span>
        <h1>Entrar na plataforma</h1>
        <p>Use seu e-mail e senha para acessar o dashboard do Ergastério Lite.</p>
    </div>

    <form method="POST" action="/login" class="form-grid">
        <?= \App\Core\Csrf::input() ?>
        <label>
            <span>E-mail</span>
            <input type="email" name="email" value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php foreach ($errors['email'] ?? [] as $error): ?>
                <small class="error-text"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <label>
            <span>Senha</span>
            <input type="password" name="password" required>
            <?php foreach ($errors['password'] ?? [] as $error): ?>
                <small class="error-text"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <button type="submit" class="button">Entrar</button>
    </form>
</section>
