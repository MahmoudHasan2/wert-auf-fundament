<?php
declare(strict_types=1);

use App\Core\DB;

/** Minimal HTTP client with cookie jar. @return array{status:int, headers:string, body:string, location:string} */
function http(string $method, string $path, array $data = [], ?string $jar = null, array $files = [], array $headers = []): array
{
    global $base;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $headers]);
    if ($jar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        $payload = $data;
        foreach ($files as $k => $f) {
            $payload[$k] = new CURLFile($f[0], $f[1], $f[2]);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $files ? $payload : http_build_query($data));
    }
    $raw = (string) curl_exec($ch);
    $size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $hdr = substr($raw, 0, $size);
    preg_match('/^Location:\s*(\S+)/mi', $hdr, $m);
    return ['status' => $status, 'headers' => $hdr, 'body' => substr($raw, $size), 'location' => $m[1] ?? ''];
}
function csrfFrom(string $html): string
{
    preg_match('/name="_csrf" value="([a-f0-9]+)"/', $html, $m);
    return $m[1] ?? '';
}

$jar = tempnam(sys_get_temp_dir(), 'waf');
$email = (string) getenv('WAF_TEST_EMAIL');
$password = (string) getenv('WAF_TEST_PASSWORD');

// ------------------------------------------------------------------ public website
foreach (['/', '/leistungen', '/leistungen/garten-und-landschaftsbau', '/leistungen/hoch-und-tiefbau', '/leistungen/hoch-und-tiefbau/kabel-und-rohrverlegung',
             '/leistungen/abbruch-und-altlastensanierung', '/leistungen/rohbau', '/referenzen', '/unternehmen', '/kontakt', '/impressum', '/datenschutz', '/sitemap.xml', '/robots.txt'] as $p) {
    $r = http('GET', $p);
    check("GET $p → 200", $r['status'] === 200, (string) $r['status']);
}
$home = http('GET', '/');
check('Public pages set no cookies', !preg_match('/^Set-Cookie/mi', $home['headers']));
check('Security headers (CSP, nosniff, frame)', str_contains($home['headers'], 'Content-Security-Policy') && str_contains($home['headers'], 'nosniff') && str_contains($home['headers'], 'X-Frame-Options'));
check('Homepage has logo, nav and services from DB', str_contains($home['body'], 'logo.png') && str_contains($home['body'], 'Garten- und Landschaftsbau') && str_contains($home['body'], 'Rohbau'));
check('Organization structured data only with real data', str_contains($home['body'], '"vatID":"DE460 571 549"') && !str_contains($home['body'], 'aggregateRating'));
check('Pre-launch: noindex active', str_contains($home['body'], 'noindex'));
$r404 = http('GET', '/diese-seite-gibt-es-nicht');
check('404 page', $r404['status'] === 404 && str_contains($r404['body'], 'Seite nicht gefunden') && !str_contains($r404['body'], 'Stack'));
check('Unknown service → 404', http('GET', '/leistungen/gibt-es-nicht')['status'] === 404);
check('Uploads: non-image path blocked', http('GET', '/uploads/test.php')['status'] >= 403);
check('Admin requires login', http('GET', '/admin/pages')['status'] === 303);

// ------------------------------------------------------------------ contact form
$before = (int) DB::value('SELECT COUNT(*) FROM enquiries');
$page = http('GET', '/kontakt');
preg_match('/name="_token" value="([^"]+)"/', $page['body'], $m);
$token = $m[1] ?? '';
preg_match('/name="_page" value="(\d+)"/', $page['body'], $m);
$pageId = $m[1] ?? '0';
$fast = http('POST', '/kontakt/anfrage', ['_token' => $token, '_page' => $pageId, 'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Spam spam spam spam', 'privacy' => '1']);
check('Contact: too-fast submission silently dropped', $fast['status'] === 303 && (int) DB::value('SELECT COUNT(*) FROM enquiries') === $before);
sleep(4);
$invalid = http('POST', '/kontakt/anfrage', ['_token' => $token, '_page' => $pageId, 'name' => '', 'email' => 'kein-mail', 'message' => 'kurz']);
check('Contact: validation errors (422)', $invalid['status'] === 422 && str_contains($invalid['body'], 'gültige E-Mail-Adresse') && str_contains($invalid['body'], 'Datenschutzhinweise'));
$hp = http('POST', '/kontakt/anfrage', ['_token' => $token, '_page' => $pageId, 'website' => 'http://spam', 'name' => 'Spam', 'email' => 'x@example.com', 'message' => 'Hallo Hallo Hallo', 'privacy' => '1']);
check('Contact: honeypot dropped', $hp['status'] === 303 && (int) DB::value('SELECT COUNT(*) FROM enquiries') === $before);
$bad = http('POST', '/kontakt/anfrage', ['_token' => $token . 'x', '_page' => $pageId, 'name' => 'Max', 'email' => 'max@example.com', 'message' => 'Hallo, ich habe eine Frage.', 'privacy' => '1']);
check('Contact: forged token rejected', $bad['status'] === 422 && (int) DB::value('SELECT COUNT(*) FROM enquiries') === $before);
$ok = http('POST', '/kontakt/anfrage', ['_token' => $token, '_page' => $pageId, 'name' => 'Max <b>Muster</b>', 'email' => 'max@example.com', 'phone' => '0511 123456', 'project_type' => 'Rohbau', 'message' => 'Wir planen ein Einfamilienhaus <script>x</script>.', 'privacy' => '1']);
check('Contact: valid submission stored', $ok['status'] === 303 && str_contains($ok['location'], 'gesendet=1') && (int) DB::value('SELECT COUNT(*) FROM enquiries') === $before + 1);
$enquiryId = (int) DB::value('SELECT MAX(id) FROM enquiries');
check('Contact: success message shown', str_contains(http('GET', '/kontakt?gesendet=1')['body'], 'Vielen Dank'));

// ------------------------------------------------------------------ authentication
$login = http('GET', '/admin/login', [], $jar);
check('Admin session cookie is HttpOnly + scoped to /admin', (bool) preg_match('/Set-Cookie: waf_admin=[^\n]*path=\/admin[^\n]*HttpOnly/i', $login['headers']));
$csrf = csrfFrom($login['body']);
$wrong = http('POST', '/admin/login', ['_csrf' => $csrf, 'email' => $email, 'password' => 'falsch-falsch-falsch'], $jar);
$follow = http('GET', '/admin/login', [], $jar);
check('Login: wrong password → generic error', str_contains($follow['body'], 'E-Mail-Adresse oder Passwort ist nicht korrekt'));
$noCsrf = http('POST', '/admin/login', ['email' => $email, 'password' => $password], $jar);
check('Login without CSRF token rejected', $noCsrf['status'] === 303 && !str_contains($noCsrf['location'], '/admin/pages') && http('GET', '/admin/pages', [], $jar)['status'] === 303);
$csrf = csrfFrom(http('GET', '/admin/login', [], $jar)['body']);
$in = http('POST', '/admin/login', ['_csrf' => $csrf, 'email' => $email, 'password' => $password], $jar);
check('Login succeeds', $in['status'] === 303 && http('GET', '/admin', [], $jar)['status'] === 200);
$hash = (string) DB::value('SELECT password_hash FROM admins WHERE email = ?', [mb_strtolower($email)]);
check('Password stored as hash', str_starts_with($hash, '$2y$') && !str_contains($hash, $password));
$csrf = csrfFrom(http('GET', '/admin', [], $jar)['body']);

// ------------------------------------------------------------------ pages, slugs, blocks
$countPages = (int) DB::value('SELECT COUNT(*) FROM pages');
http('POST', '/admin/pages', ['title' => 'CSRF Test'], $jar);
check('CSRF enforced on admin POST', (int) DB::value('SELECT COUNT(*) FROM pages') === $countPages);
$cr = http('POST', '/admin/pages', ['_csrf' => $csrf, 'title' => 'Karriere bei uns', 'slug' => '', 'template' => 'standard', 'status' => 'draft'], $jar);
$newPageId = (int) DB::value("SELECT id FROM pages WHERE slug = 'karriere-bei-uns'");
check('Page created with generated slug', $cr['status'] === 303 && $newPageId > 0);
check('Draft page not public', http('GET', '/karriere-bei-uns')['status'] === 404);
check('Draft page previewable for admin', http('GET', "/admin/pages/$newPageId/preview", [], $jar)['status'] === 200);
http('POST', '/admin/pages', ['_csrf' => $csrf, 'title' => 'Doppelt', 'slug' => 'karriere-bei-uns'], $jar);
check('Duplicate slug rejected', (int) DB::value("SELECT COUNT(*) FROM pages WHERE slug = 'karriere-bei-uns'") === 1);
http('POST', '/admin/pages', ['_csrf' => $csrf, 'title' => 'Admin', 'slug' => 'admin'], $jar);
check('Reserved slug rejected', !DB::value("SELECT id FROM pages WHERE slug = 'admin'"));
http('POST', "/admin/pages/$newPageId", ['_csrf' => $csrf, 'title' => 'Karriere bei uns', 'slug' => 'karriere-bei-uns', 'template' => 'standard', 'status' => 'published'], $jar);
check('Published page is public', http('GET', '/karriere-bei-uns')['status'] === 200);
$future = date('Y-m-d\TH:i', time() + 86400);
http('POST', "/admin/pages/$newPageId", ['_csrf' => $csrf, 'title' => 'Karriere bei uns', 'slug' => 'karriere-bei-uns', 'template' => 'standard', 'status' => 'published', 'published_at' => $future], $jar);
check('Scheduled publication hides page until date', http('GET', '/karriere-bei-uns')['status'] === 404);
http('POST', "/admin/pages/$newPageId", ['_csrf' => $csrf, 'title' => 'Karriere bei uns', 'slug' => 'karriere-bei-uns', 'template' => 'standard', 'status' => 'published', 'published_at' => ''], $jar);

http('POST', '/admin/blocks/add', ['_csrf' => $csrf, 'owner' => 'page', 'owner_id' => $newPageId, 'type' => 'text'], $jar);
$blockId = (int) DB::value('SELECT MAX(id) FROM content_blocks WHERE page_id = ?', [$newPageId]);
http('POST', "/admin/blocks/$blockId", ['_csrf' => $csrf, 'is_visible' => '1', 'data' => ['heading' => 'Offene Stellen', 'layout' => 'narrow', 'bg' => 'white',
    'body' => '<p onclick="x">Wir suchen <strong>Verstärkung</strong>.</p><script>alert(1)</script><a href="javascript:alert(1)">klick</a>']], $jar);
$pub = http('GET', '/karriere-bei-uns')['body'];
check('Block content rendered', str_contains($pub, 'Offene Stellen') && str_contains($pub, '<strong>Verstärkung</strong>'));
check('Stored XSS prevented', !str_contains($pub, 'alert(1)') && !str_contains($pub, 'onclick'));
http('POST', '/admin/blocks/add', ['_csrf' => $csrf, 'owner' => 'page', 'owner_id' => $newPageId, 'type' => 'cta'], $jar);
$block2 = (int) DB::value('SELECT MAX(id) FROM content_blocks WHERE page_id = ?', [$newPageId]);
$order = fn () => array_map('intval', DB::column('SELECT id FROM content_blocks WHERE page_id = ? ORDER BY sort_order', [$newPageId]));
$o1 = $order();
http('POST', "/admin/blocks/$block2/move", ['_csrf' => $csrf, 'dir' => 'up'], $jar);
check('Block reorder', $order() !== $o1 && $order()[array_search($block2, $order(), true)] === $block2 && array_search($block2, $order(), true) < array_search($block2, $o1, true));
http('POST', "/admin/blocks/$blockId/toggle", ['_csrf' => $csrf], $jar);
check('Block hide', !str_contains(http('GET', '/karriere-bei-uns')['body'], 'Offene Stellen'));
http('POST', "/admin/blocks/$blockId/toggle", ['_csrf' => $csrf], $jar);
http('POST', "/admin/blocks/$blockId/duplicate", ['_csrf' => $csrf], $jar);
check('Block duplicate', (int) DB::value('SELECT COUNT(*) FROM content_blocks WHERE page_id = ?', [$newPageId]) === 4);
$dup = (int) DB::value('SELECT MAX(id) FROM content_blocks WHERE page_id = ?', [$newPageId]);
http('POST', "/admin/blocks/$dup/delete", ['_csrf' => $csrf], $jar);
check('Block delete', (int) DB::value('SELECT COUNT(*) FROM content_blocks WHERE page_id = ?', [$newPageId]) === 3);
$revs = (int) DB::value('SELECT COUNT(*) FROM page_revisions WHERE page_id = ?', [$newPageId]);
check('Revisions recorded', $revs >= 4, (string) $revs);
$firstRev = (int) DB::value('SELECT MIN(id) FROM page_revisions WHERE page_id = ?', [$newPageId]);
http('POST', "/admin/pages/$newPageId/revisions/$firstRev/restore", ['_csrf' => $csrf], $jar);
check('Revision restore', !str_contains(http('GET', '/karriere-bei-uns')['body'], 'Offene Stellen'));

// navigation link to new page
http('POST', '/admin/navigation', ['_csrf' => $csrf, 'menu' => 'footer', 'label' => 'Karriere', 'link_type' => 'page', 'page_id' => $newPageId, 'is_visible' => '1'], $jar);
check('Navigation item appears in footer', str_contains(http('GET', '/')['body'], '>Karriere</a>'));
http('POST', "/admin/pages/$newPageId/delete", ['_csrf' => $csrf], $jar);
check('Page linked in navigation cannot be deleted', (bool) DB::value('SELECT id FROM pages WHERE id = ?', [$newPageId]));
$navId = (int) DB::value('SELECT id FROM nav_items WHERE page_id = ?', [$newPageId]);
http('POST', "/admin/navigation/$navId/delete", ['_csrf' => $csrf], $jar);
http('POST', "/admin/pages/$newPageId/delete", ['_csrf' => $csrf], $jar);
check('Page deleted after removing nav item', !DB::value('SELECT id FROM pages WHERE id = ?', [$newPageId]));
$imprint = (int) DB::value("SELECT id FROM pages WHERE system_key = 'imprint'");
http('POST', "/admin/pages/$imprint/delete", ['_csrf' => $csrf], $jar);
http('POST', "/admin/pages/$imprint", ['_csrf' => $csrf, 'title' => 'Impressum', 'template' => 'legal', 'status' => 'draft'], $jar);
check('Legal page cannot be deleted or unpublished', http('GET', '/impressum')['status'] === 200);

// ------------------------------------------------------------------ media
$tmp = sys_get_temp_dir() . '/waf-test-' . bin2hex(random_bytes(4));
$im = imagecreatetruecolor(800, 600);
imagefill($im, 0, 0, imagecolorallocate($im, 197, 2, 7));
imagejpeg($im, $tmp . '.jpg', 85);
file_put_contents($tmp . '-evil.jpg', "<?php echo 'pwned'; ?>");
file_put_contents($tmp . '.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
file_put_contents($tmp . '.php', "<?php phpinfo();");
$mc = (int) DB::value('SELECT COUNT(*) FROM media');
$up = http('POST', '/admin/media/upload', ['_csrf' => $csrf], $jar, ['files[]' => [$tmp . '.jpg', 'image/jpeg', 'baustelle.jpg']]);
$mediaId = (int) DB::value('SELECT MAX(id) FROM media');
$m = DB::row('SELECT * FROM media WHERE id = ?', [$mediaId]);
check('Image upload accepted and re-encoded', (int) DB::value('SELECT COUNT(*) FROM media') === $mc + 1 && is_file(public_path('uploads/' . $m['path'] . '-sm.' . $m['ext'])));
check('Uploaded image is served', http('GET', '/uploads/' . $m['path'] . '-sm.' . $m['ext'])['status'] === 200);
foreach ([[$tmp . '-evil.jpg', 'image/jpeg', 'evil.jpg', 'PHP disguised as JPG'], [$tmp . '.svg', 'image/svg+xml', 'x.svg', 'SVG'], [$tmp . '.php', 'image/jpeg', 'shell.php', 'PHP file']] as [$f, $mime, $name, $label]) {
    http('POST', '/admin/media/upload', ['_csrf' => $csrf], $jar, ['files[]' => [$f, $mime, $name]]);
    check("Dangerous upload rejected: $label", (int) DB::value('SELECT COUNT(*) FROM media') === $mc + 1);
}
$oldPath = $m['path'];
$rep = http('POST', "/admin/media/$mediaId/replace", ['_csrf' => $csrf], $jar, ['file' => [$tmp . '.jpg', 'image/jpeg', 'neu.jpg']]);
$m2 = DB::row('SELECT * FROM media WHERE id = ?', [$mediaId]);
check('Image replace keeps id, swaps files', $m2['path'] !== $oldPath && !is_file(public_path('uploads/' . $oldPath . '-sm.' . $m['ext'])));
$usedId = (int) DB::value('SELECT image_media_id FROM services WHERE image_media_id IS NOT NULL LIMIT 1');
http('POST', "/admin/media/$usedId/delete", ['_csrf' => $csrf], $jar);
check('Used image cannot be deleted', (bool) DB::value('SELECT id FROM media WHERE id = ?', [$usedId]));

// ------------------------------------------------------------------ services & projects & galleries
http('POST', '/admin/services', ['_csrf' => $csrf, 'title' => 'Pflasterarbeiten Test', 'parent_id' => '0', 'status' => 'draft', 'teaser' => 'Test', 'image_media_id' => $mediaId], $jar);
$svc = DB::row("SELECT * FROM services WHERE slug = 'pflasterarbeiten-test'");
check('Service created (draft hidden)', $svc && http('GET', '/leistungen/pflasterarbeiten-test')['status'] === 404);
http('POST', '/admin/services/' . $svc['id'], ['_csrf' => $csrf, 'title' => 'Pflasterarbeiten Test', 'slug' => 'pflasterarbeiten-test', 'parent_id' => '0', 'status' => 'published', 'teaser' => 'Test', 'image_media_id' => $mediaId], $jar);
check('Published service appears on overview', http('GET', '/leistungen/pflasterarbeiten-test')['status'] === 200 && str_contains(http('GET', '/leistungen')['body'], 'Pflasterarbeiten Test'));
http('POST', '/admin/projects', ['_csrf' => $csrf, 'title' => 'Testprojekt', 'status' => 'published'], $jar);
check('Project publish without cover rejected', !DB::value("SELECT id FROM projects WHERE slug = 'testprojekt'"));
http('POST', '/admin/projects', ['_csrf' => $csrf, 'title' => 'Testprojekt', 'status' => 'published', 'cover_media_id' => $mediaId, 'service_id' => $svc['id'], 'summary' => 'Kurzbeschreibung'], $jar);
$proj = DB::row("SELECT * FROM projects WHERE slug = 'testprojekt'");
http('POST', '/admin/galleries/' . $proj['gallery_id'] . '/items', ['_csrf' => $csrf, 'media_ids' => [$mediaId, $usedId]], $jar);
check('Project gallery images added', (int) DB::value('SELECT COUNT(*) FROM gallery_items WHERE gallery_id = ?', [$proj['gallery_id']]) === 2);
check('Published project on /referenzen and detail page', str_contains(http('GET', '/referenzen')['body'], 'Testprojekt') && http('GET', '/referenzen/testprojekt')['status'] === 200);
http('POST', '/admin/projects/' . $proj['id'] . '/delete', ['_csrf' => $csrf], $jar);
http('POST', '/admin/services/' . $svc['id'] . '/delete', ['_csrf' => $csrf], $jar);
check('Project and service deleted', !DB::value('SELECT id FROM projects WHERE id = ?', [$proj['id']]) && !DB::value('SELECT id FROM services WHERE id = ?', [$svc['id']]));
$parent = (int) DB::value("SELECT id FROM services WHERE slug = 'hoch-und-tiefbau'");
http('POST', "/admin/services/$parent/delete", ['_csrf' => $csrf], $jar);
check('Service with sub-services cannot be deleted', (bool) DB::value('SELECT id FROM services WHERE id = ?', [$parent]));

// ------------------------------------------------------------------ FAQ, settings, enquiries
http('POST', '/admin/faq', ['_csrf' => $csrf, 'question' => 'Testfrage Integration?', 'answer' => '<p>Antwort</p>', 'status' => 'published'], $jar);
check('FAQ appears on contact page', str_contains(http('GET', '/kontakt')['body'], 'Testfrage Integration?'));
$settingsForm = http('GET', '/admin/settings', [], $jar)['body'];
preg_match_all('/name="([a-z_]+)"[^>]*value="([^"]*)"/', $settingsForm, $mm, PREG_SET_ORDER);
$post = ['_csrf' => $csrf];
foreach (\App\Services\Settings::all() as $k => $v) {
    $post[$k] = $v;
}
$post['phone'] = '0511 000 111 22';
http('POST', '/admin/settings', $post, $jar);
check('Settings change visible on frontend', str_contains(http('GET', '/')['body'], '0511 000 111 22'));
$post['phone'] = '0511 999 333 36';
http('POST', '/admin/settings', $post, $jar);
$show = http('GET', "/admin/enquiries/$enquiryId", [], $jar)['body'];
check('Enquiry detail escaped', str_contains($show, 'Max &lt;b&gt;Muster&lt;/b&gt;') && !str_contains($show, '<script>x</script>'));
http('POST', "/admin/enquiries/$enquiryId/status", ['_csrf' => $csrf, 'status' => 'answered'], $jar);
http('POST', "/admin/enquiries/$enquiryId/notes", ['_csrf' => $csrf, 'note' => 'Rückruf erledigt'], $jar);
check('Enquiry status + note', DB::value('SELECT status FROM enquiries WHERE id = ?', [$enquiryId]) === 'answered' && (int) DB::value('SELECT COUNT(*) FROM enquiry_notes WHERE enquiry_id = ?', [$enquiryId]) === 1);

// ------------------------------------------------------------------ roles / access control
http('POST', '/admin/users', ['_csrf' => $csrf, 'name' => 'Erika Redaktion', 'email' => 'redaktion@example.test', 'role' => 'editor', 'is_active' => '1', 'password' => 'Kellerdecke-Beton-77!', 'password_confirm' => 'Kellerdecke-Beton-77!'], $jar);
$ejar = tempnam(sys_get_temp_dir(), 'wafe');
$ec = csrfFrom(http('GET', '/admin/login', [], $ejar)['body']);
http('POST', '/admin/login', ['_csrf' => $ec, 'email' => 'redaktion@example.test', 'password' => 'Kellerdecke-Beton-77!'], $ejar);
check('Editor can edit pages', http('GET', '/admin/pages', [], $ejar)['status'] === 200);
check('Editor blocked from users/settings/backups (403)', http('GET', '/admin/users', [], $ejar)['status'] === 403 && http('GET', '/admin/settings', [], $ejar)['status'] === 403 && http('GET', '/admin/backups', [], $ejar)['status'] === 403);
check('Editor blocked from legal pages (403)', http('GET', "/admin/pages/$imprint", [], $ejar)['status'] === 403);

// ------------------------------------------------------------------ backups
http('POST', '/admin/backups/database', ['_csrf' => $csrf], $jar);
$backups = \App\Services\Backup::list();
check('Database backup created', $backups && $backups[0]['type'] === 'db');
$dl = http('GET', '/admin/backups/download/' . $backups[0]['name'], [], $jar);
check('Backup download for admin', $dl['status'] === 200 && strlen($dl['body']) > 1000);
check('Backup download blocked without login', http('GET', '/admin/backups/download/' . $backups[0]['name'])['status'] === 303);
check('Backup path traversal blocked', http('GET', '/admin/backups/download/..%2F..%2Fconfig%2Fapp.php', [], $jar)['status'] === 404);
http('POST', '/admin/backups/restore', ['_csrf' => $csrf, 'name' => $backups[0]['name'], 'password' => 'falsch', 'confirm' => 'WIEDERHERSTELLEN'], $jar);
check('Restore requires password', str_contains(http('GET', '/admin/backups', [], $jar)['body'], 'Passwort nicht korrekt'));

// ------------------------------------------------------------------ logout & throttling
$csrf = csrfFrom(http('GET', '/admin', [], $jar)['body']);
http('POST', '/admin/logout', ['_csrf' => $csrf], $jar);
check('Logout ends session', http('GET', '/admin', [], $jar)['status'] === 303);
$tjar = tempnam(sys_get_temp_dir(), 'waft');
for ($i = 0; $i < 6; $i++) {
    $c = csrfFrom(http('GET', '/admin/login', [], $tjar)['body']);
    http('POST', '/admin/login', ['_csrf' => $c, 'email' => 'redaktion@example.test', 'password' => 'falsch' . $i], $tjar);
}
$c = csrfFrom(http('GET', '/admin/login', [], $tjar)['body']);
http('POST', '/admin/login', ['_csrf' => $c, 'email' => 'redaktion@example.test', 'password' => 'Kellerdecke-Beton-77!'], $tjar);
check('Login throttled after repeated failures', str_contains(http('GET', '/admin/login', [], $tjar)['body'], 'Zu viele Anmeldeversuche'));
check('Audit log records events', (int) DB::value("SELECT COUNT(*) FROM audit_log WHERE action IN ('login.failed','page.created','backup.created')") >= 3);

foreach ([$tmp . '.jpg', $tmp . '-evil.jpg', $tmp . '.svg', $tmp . '.php', $jar, $ejar, $tjar] as $f) {
    @unlink($f);
}
