<?php
/** @var string $content */
/** @var array $app */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="10">
    <title><?= htmlspecialchars(($pageTitle ?? $app['name']) . ' | ' . $app['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/liquidity.css">
</head>
<body class="liquidity-projector-body">
    <?= $content ?>
</body>
</html>
