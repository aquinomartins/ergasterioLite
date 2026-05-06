<?php
/** @var string $content */
/** @var array $app */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(($pageTitle ?? $app['name']) . ' | ' . $app['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/liquidity.css">
</head>
<body>
<div class="page-shell">
    <?php \App\Core\View::partial('partials.header', ['currentUser' => $currentUser ?? null]); ?>
    <main class="main-content container">
        <?php \App\Core\View::partial('partials.flash', ['flash' => $flash ?? []]); ?>
        <?= $content ?>
    </main>
    <?php \App\Core\View::partial('partials.footer'); ?>
</div>
<script src="/assets/js/app.js" defer></script>
</body>
</html>
