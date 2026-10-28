<?php
/**
 * Default configuration. Do NOT put secrets here.
 *
 * Secrets and environment-specific values belong in env.php, which is looked up in this order:
 *   1. The path in the environment variable WAF_ENV_FILE
 *   2. ../waf-private/env.php  (one level ABOVE the project directory – recommended on Hostinger)
 *   3. config/env.php          (inside the project; protected by .htaccess, never commit it)
 *
 * See config/env.example.php for all keys.
 */
return [
    'app' => [
        'name'     => 'Wert auf Fundament GmbH',
        'url'      => 'http://localhost:8000',
        'env'      => 'production',
        'debug'    => false,
        'key'      => '',            // 64 hex chars, required (HMAC for form tokens)
        'timezone' => 'Europe/Berlin',
        'locale'   => 'de_DE',
        'force_https' => false,      // redirect http -> https at application level
        'hsts'     => false,         // send Strict-Transport-Security (enable only when HTTPS works everywhere)
    ],
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => '',
        'user'    => '',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        'transport'    => 'none',   // none | smtp | mail
        'host'         => '',
        'port'         => 465,
        'encryption'   => 'ssl',    // ssl | tls | none
        'username'     => '',
        'password'     => '',
        'from_address' => '',
        'from_name'    => 'Website Wert auf Fundament',
        'timeout'      => 15,
    ],
    'install' => [
        'token' => '',              // long random string; required to run the web installer
    ],
    'uploads' => [
        'max_bytes'  => 10 * 1024 * 1024,
        'max_pixels' => 40_000_000,
        'max_side'   => 9000,
    ],
    'paths' => [
        'storage' => '',            // empty = <project>/storage
    ],
    'session' => [
        'idle_timeout'     => 7200,
        'absolute_timeout' => 43200,
    ],
];
