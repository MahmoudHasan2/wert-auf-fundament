<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_VERSION', '1.0.0');

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/helpers.php';

App\Core\Config::load();
date_default_timezone_set((string) config('app.timezone', 'Europe/Berlin'));
mb_internal_encoding('UTF-8');
App\Core\ErrorHandler::register();
