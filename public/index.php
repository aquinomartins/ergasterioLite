<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;

$root = dirname(__DIR__);
define('BASE_PATH', $root);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

$config = require BASE_PATH . '/app/Config/config.php';

Session::start($config['session']);
Database::initialize($config['database']);
Csrf::boot();
Auth::boot();

$app = new App($config);
require BASE_PATH . '/routes/web.php';

$app->run();
