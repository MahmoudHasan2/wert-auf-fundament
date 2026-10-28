<?php
declare(strict_types=1);

/**
 * Command-line installer (alternative to the web installer at /admin/install).
 *
 *   php bin/install.php --name="Max Mustermann" --email=admin@example.de
 *
 * The password is prompted interactively (or read from the WAF_ADMIN_PASSWORD environment variable).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Security\Auth;
use App\Services\Installer;

$opts = getopt('', ['name:', 'email:']);
$name = trim((string) ($opts['name'] ?? ''));
$email = trim((string) ($opts['email'] ?? ''));

if (Installer::isInstalled()) {
    fwrite(STDERR, "Bereits installiert (storage/installed.lock existiert).\n");
    exit(1);
}
foreach (Installer::preflight() as $problem) {
    fwrite(STDERR, "FEHLER: $problem\n");
    $failed = true;
}
if (!empty($failed)) {
    exit(1);
}
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Aufruf: php bin/install.php --name=\"Vorname Nachname\" --email=adresse@example.de\n");
    exit(1);
}

$password = (string) getenv('WAF_ADMIN_PASSWORD');
if ($password === '') {
    echo 'Passwort für den Administrator (mind. ' . Auth::MIN_PASSWORD_LENGTH . ' Zeichen): ';
    $password = trim((string) fgets(STDIN));
    echo 'Passwort wiederholen: ';
    $confirm = trim((string) fgets(STDIN));
} else {
    $confirm = $password;
}
$errors = Auth::validatePassword($password, $confirm, $email, $name);
if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

Installer::install(['name' => $name, 'email' => $email, 'password' => $password]);
echo "Installation abgeschlossen. Anmeldung unter " . abs_url('/admin/login') . "\n";
