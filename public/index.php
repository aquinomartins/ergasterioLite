<?php

define('LARAVEL_START', microtime(true));

$basePath = dirname(__DIR__);
$autoloadPath = $basePath.'/vendor/autoload.php';
$environmentPath = $basePath.'/.env';

if (! file_exists($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');

    echo renderStartupErrorPage(
        'Dependências do Laravel não encontradas.',
        [
            'Execute <code>composer install --no-dev --optimize-autoloader</code> no servidor, ou envie a pasta <code>vendor/</code> já gerada.',
            'Confirme que o domínio está apontando para a pasta <code>public/</code> do projeto.',
        ],
    );

    exit(1);
}

if (appKeyIsMissing($environmentPath)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');

    echo renderStartupErrorPage(
        'APP_KEY ausente ou vazia no arquivo .env.',
        [
            'Defina uma chave válida executando <code>php artisan key:generate</code>.',
            'Se a hospedagem não permitir Artisan, gere a chave em outro ambiente Laravel e copie o valor completo para <code>APP_KEY=...</code> no servidor.',
        ],
    );

    exit(1);
}

require $autoloadPath;

$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());

function appKeyIsMissing(string $environmentPath): bool
{
    if (! file_exists($environmentPath)) {
        return true;
    }

    $lines = file($environmentPath, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        return true;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_starts_with($line, 'APP_KEY=')) {
            continue;
        }

        $value = trim(substr($line, strlen('APP_KEY=')));

        return $value === '' || $value === '""' || $value === "''";
    }

    return true;
}

/**
 * @param  array<int, string>  $steps
 */
function renderStartupErrorPage(string $title, array $steps): string
{
    $items = implode('', array_map(
        static fn (string $step): string => '<li>'.$step.'</li>',
        $steps,
    ));

    return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro de configuração</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }
        main {
            max-width: 760px;
            margin: 48px auto;
            padding: 32px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(23, 32, 51, 0.10);
        }
        h1 {
            margin-top: 0;
            font-size: 1.8rem;
        }
        p, li {
            line-height: 1.6;
        }
        code {
            padding: 2px 6px;
            background: #eef2ff;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <main>
        <h1>{$title}</h1>
        <p>O Ergastério Lite não conseguiu finalizar a inicialização por causa de uma configuração obrigatória ausente no servidor.</p>
        <ol>{$items}</ol>
    </main>
</body>
</html>
HTML;
}
