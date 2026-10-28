<?php
// Keep this file compatible with old PHP versions so a wrong PHP version shows a clear message
// instead of a blank page.
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><meta charset="utf-8"><title>PHP-Version zu alt</title>'
        . '<p style="font:16px sans-serif;max-width:640px;margin:40px auto">Diese Website benötigt PHP 8.2 oder neuer (aktuell: '
        . htmlspecialchars(PHP_VERSION) . '). Bitte in Hostinger unter <b>hPanel → Erweitert → PHP-Konfiguration</b> PHP 8.3 auswählen.</p>';
    exit;
}

// PHP built-in development server: let it serve existing static files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && !str_ends_with($file, '.php')) {
        if (str_contains($file, '/uploads/') && !preg_match('#/[a-f0-9]{24}-(sm|md|lg|full)\.(webp|jpg|png)$#', $file)) {
            http_response_code(403);
            exit;
        }
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

$response = (new App\Core\Kernel())->handle(App\Core\Request::capture());
$response->send();
