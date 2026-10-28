<?php
declare(strict_types=1);

/**
 * Test runner without external dependencies.
 *
 *   php tests/run.php                       – unit tests only
 *   WAF_TEST_BASE=http://127.0.0.1:8001 WAF_TEST_EMAIL=… WAF_TEST_PASSWORD=… php tests/run.php
 *                                           – unit + HTTP integration tests against a FRESHLY INSTALLED
 *                                             test instance (never run against production: it creates data)
 * The env file of the test instance must be passed via WAF_ENV_FILE so the DB checks hit the same database.
 */
define('WAF_TESTING', true);
require dirname(__DIR__) . '/app/bootstrap.php';
restore_error_handler();

use App\Core\DB;
use App\Core\Migrator;
use App\Security\FormToken;
use App\Security\HtmlSanitizer;
use App\Services\FieldForm;
use App\Support\Slug;

$pass = 0;
$fail = 0;
function check(string $name, bool $ok, string $info = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? "  \033[32mPASS\033[0m " : "  \033[31mFAIL\033[0m ") . $name . ($ok || $info === '' ? '' : " – $info") . "\n";
}

echo "Unit tests\n";
check('Slug: Umlaute', Slug::make('Garten- & Landschaftsbau Größe') === 'garten-und-landschaftsbau-groesse');
check('Slug: valid', Slug::valid('a-b-1') && !Slug::valid('A b') && !Slug::valid('-x') && !Slug::valid('../x'));
check('Slug: reserved', Slug::reserved('admin') && Slug::reserved('leistungen') && !Slug::reserved('faq'));

$xss = HtmlSanitizer::clean('<p onclick="x()">Hi <b>bold</b></p><script>alert(1)</script><a href="javascript:alert(1)">bad</a><a href="https://example.com" target="_blank">ok</a><img src=x onerror=alert(1)><iframe src="//evil"></iframe><a href=" jav&#x09;ascript:alert(1)">t</a><style>*{}</style>');
check('Sanitizer: removes script/iframe/img/style', !preg_match('/script|iframe|<img|<style|onerror/i', $xss), $xss);
check('Sanitizer: removes event handlers', !str_contains($xss, 'onclick'), $xss);
check('Sanitizer: removes javascript: links', !str_contains(strtolower($xss), 'javascript'), $xss);
check('Sanitizer: keeps safe markup', str_contains($xss, '<strong>bold</strong>') && str_contains($xss, 'href="https://example.com"') && !str_contains($xss, 'target'), $xss);
check('Sanitizer: safeHref', HtmlSanitizer::safeHref('/kontakt') && HtmlSanitizer::safeHref('mailto:a@b.de') && !HtmlSanitizer::safeHref('//evil.com') && !HtmlSanitizer::safeHref('data:text/html,x'));

check('Links: valid', FieldForm::validLink('/kontakt#kontaktformular') && FieldForm::validLink('https://example.com/x') && FieldForm::validLink('tel:+4951199933336'));
check('Links: invalid', !FieldForm::validLink('javascript:alert(1)') && !FieldForm::validLink('//evil.com') && !FieldForm::validLink('/a\\b') && !FieldForm::validLink('/x y'));
check('link_href falls back to # for unsafe input', link_href('javascript:alert(1)') === '#' && link_href('//evil') === '#');

$t = FormToken::issue('contact');
check('FormToken: too fast', FormToken::check('contact', $t) === 'too_fast');
check('FormToken: tampered', FormToken::check('contact', $t . 'x') === 'invalid' && FormToken::check('other', $t) !== 'ok');
$old = (time() - 10) . '.abcdef0123456789';
$signed = $old . '.' . hash_hmac('sha256', 'contact|' . $old, (string) config('app.key'));
check('FormToken: valid after delay', FormToken::check('contact', $signed) === 'ok');

check('Migrator: statement split', count(Migrator::split("CREATE TABLE a (x INT);\n-- c\nCREATE TABLE b (y INT);\n")) === 2);
check('anon_ip', anon_ip('192.168.10.77') === '192.168.10.0' && anon_ip('2001:db8:1:2:3:4:5:6') === '2001:db8:1::');
check('e() escapes', e('<"\'&>') === '&lt;&quot;&apos;&amp;&gt;');

$base = rtrim((string) getenv('WAF_TEST_BASE'), '/');
if ($base === '') {
    echo "\nIntegration tests skipped (WAF_TEST_BASE not set).\n";
} else {
    echo "\nIntegration tests against $base\n";
    require __DIR__ . '/integration.php';
}

echo "\n" . ($fail === 0 ? "\033[32m" : "\033[31m") . "$pass passed, $fail failed\033[0m\n";
exit($fail === 0 ? 0 : 1);
