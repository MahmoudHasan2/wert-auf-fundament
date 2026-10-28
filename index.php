<?php
// Root entry point for hosting where the whole project sits in public_html.
// Normally the root .htaccess forwards every request to public/; this file is a safety net
// in case that rewrite is not applied (e.g. .htaccess not uploaded).
require __DIR__ . '/public/index.php';
