<?php
/**
 * Copy this file to env.php and fill in real values.
 *
 * Recommended location on Hostinger: one directory ABOVE the project, e.g.
 *   /home/u123456789/domains/wertauffundament.de/waf-private/env.php
 * (the project itself then lives in .../wertauffundament.de/waf/ or similar).
 * Alternatively config/env.php inside the project – it is protected by .htaccess.
 *
 * NEVER commit env.php to version control.
 */
return [
    'app' => [
        'url'   => 'https://www.wertauffundament.de',   // no trailing slash
        'env'   => 'production',
        'debug' => false,                                // never true on the live site
        // Generate with:  php -r "echo bin2hex(random_bytes(32));"
        'key'   => 'REPLACE_WITH_64_HEX_CHARACTERS',
        'force_https' => true,
        'hsts'  => false,
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'u123456789_waf',
        'user' => 'u123456789_waf',
        'pass' => 'REPLACE_WITH_DATABASE_PASSWORD',
    ],
    'mail' => [
        // 'none' = enquiries are only stored in the dashboard (no e-mail notification)
        // 'smtp' = recommended (Hostinger: smtp.hostinger.com, port 465, ssl)
        // 'mail' = PHP mail() function
        'transport'    => 'none',
        'host'         => 'smtp.hostinger.com',
        'port'         => 465,
        'encryption'   => 'ssl',
        'username'     => 'website@wertauffundament.de',
        'password'     => 'REPLACE_WITH_MAILBOX_PASSWORD',
        'from_address' => 'website@wertauffundament.de',
        'from_name'    => 'Website Wert auf Fundament',
    ],
    'install' => [
        // Required for the web installer (/admin/install). Generate a long random value and
        // REMOVE or empty it after the installation is finished.
        'token' => '',
    ],
];
