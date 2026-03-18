<?php
$old = $old ?? [];
$errors = $errors ?? [];
?>
<section class="auth-card card narrow">
    <div>
        <span class="eyebrow">Cadastro</span>
        <h1>Criar conta no Ergastério Lite</h1>
        <p>Abra sua conta para acessar o dashboard e editar o perfil inicial do projeto.</p>
    </div>

    <form method="POST" action="/register" class="form-grid">
        <?= \App\Core\Csrf::input() ?>
        <label>
            <span>Nome de exibição</span>
            <input type="text" name="display_name" value="<?= htmlspecialchars((string) ($old['display_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php foreach ($errors['display_name'] ?? [] as $error): ?>
                <small class="error-text"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

        <label>
            <span>Username</span>
            <input type="text" name="username" value="<?= htmlspecialchars((string) ($old['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
            <?php foreach ($errors['username'] ?? [] as $error): ?>
                <small class="error-text"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></small>
            <?php endforeach; ?>
        </label>

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

        <label>
            <span>Confirmar senha</span>
            <input type="password" name="password_confirmation" required>
        </label>

        <button type="submit" class="button">Cadastrar</button>
    </form>
</section>
