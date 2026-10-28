<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Repositories\PageRepository;

/**
 * Pre-launch checklist. Detects missing legal data, unreviewed placeholder texts,
 * placeholder images and unconfirmed claims that are still hidden.
 */
final class Compliance
{
    public const PLACEHOLDER_MARK = '[BITTE PRÜFEN';

    /** @return array<int, array{level:string, title:string, text:string, url:string}> level = error|warning|info */
    public static function checks(): array
    {
        $c = [];
        $add = function (string $level, string $title, string $text, string $url) use (&$c) {
            $c[] = compact('level', 'title', 'text', 'url');
        };

        foreach (Settings::schema() as $group) {
            foreach ($group as $key => $f) {
                if (!empty($f['required']) && Settings::get($key) === '') {
                    $add('error', $f['label'] . ' fehlt', 'Pflichtangabe in den Unternehmenseinstellungen.', '/admin/settings');
                }
            }
        }
        if (Settings::get('managing_directors') === '') {
            $add('error', 'Geschäftsführer fehlen', 'Die vertretungsberechtigten Personen sind im Impressum Pflicht (§ 5 DDG).', '/admin/settings');
        }
        if (Settings::get('register_court') === '' || Settings::get('register_number') === '') {
            $add('error', 'Handelsregister unvollständig', 'Registergericht und Registernummer der GmbH ergänzen.', '/admin/settings');
        }
        if (Settings::get('hosting_provider') === '') {
            $add('warning', 'Hosting-Anbieter nicht angegeben', 'Wird in der Datenschutzerklärung benötigt.', '/admin/settings');
        }

        foreach (['imprint' => 'Impressum', 'privacy' => 'Datenschutzerklärung'] as $key => $label) {
            $page = PageRepository::system($key);
            if (!$page) {
                $add('error', $label . ' fehlt', 'Die Systemseite existiert nicht.', '/admin/pages');
                continue;
            }
            if (!PageRepository::isLive($page)) {
                $add('error', $label . ' ist nicht veröffentlicht', 'Rechtstexte müssen jederzeit erreichbar sein.', '/admin/pages/' . $page['id']);
            }
            $n = (int) DB::value('SELECT COUNT(*) FROM content_blocks WHERE page_id = ? AND is_visible = 1 AND data LIKE ?', [$page['id'], '%' . self::PLACEHOLDER_MARK . '%']);
            if ($n > 0) {
                $add('warning', $label . ': ' . $n . ' Abschnitt(e) mit Prüfvermerk', 'Texte mit „' . self::PLACEHOLDER_MARK . '…]“ vor dem Livegang fachlich prüfen und anpassen.', '/admin/pages/' . $page['id']);
            }
        }

        $marked = (int) DB::value('SELECT COUNT(*) FROM content_blocks b LEFT JOIN pages p ON p.id = b.page_id WHERE b.is_visible = 1 AND b.data LIKE ? AND (p.system_key IS NULL OR p.system_key NOT IN (\'imprint\', \'privacy\'))', ['%' . self::PLACEHOLDER_MARK . '%']);
        if ($marked > 0) {
            $add('warning', $marked . ' weitere sichtbare Inhaltsblöcke mit Prüfvermerk', 'Suchen Sie nach „' . self::PLACEHOLDER_MARK . '“ in Seiten und Leistungen.', '/admin/pages');
        }

        $hidden = DB::all("SELECT b.id, b.admin_note, COALESCE(p.title, s.title, b.library_name) AS owner FROM content_blocks b LEFT JOIN pages p ON p.id = b.page_id LEFT JOIN services s ON s.id = b.service_id WHERE b.is_visible = 0 AND b.admin_note <> ''");
        foreach ($hidden as $h) {
            $add('info', 'Ausgeblendeter Inhalt wartet auf Freigabe: ' . $h['owner'], $h['admin_note'], '/admin/blocks/' . $h['id']);
        }
        foreach (DB::all("SELECT id, title, admin_note, status FROM services WHERE admin_note <> ''") as $s) {
            $add($s['status'] === 'published' ? 'warning' : 'info', 'Leistung „' . $s['title'] . '“ (' . status_label($s['status']) . ')', $s['admin_note'], '/admin/services/' . $s['id']);
        }

        $ph = (int) DB::value('SELECT COUNT(*) FROM media WHERE is_placeholder = 1');
        if ($ph > 0) {
            $add('warning', $ph . ' Platzhalterbilder in der Mediathek', 'Lizenzierte Übergangsbilder (Wikimedia Commons, Namensnennung erforderlich). Vor dem Livegang durch eigene Fotos ersetzen oder Bildnachweise aktiviert lassen.', '/admin/media?filter=placeholder');
        }
        if (!Mailer::isConfigured()) {
            $add('warning', 'E-Mail-Versand nicht eingerichtet', 'Anfragen werden gespeichert, aber es wird keine Benachrichtigung versendet. SMTP in env.php konfigurieren.', '/admin/system');
        }
        if (Settings::get('seo_indexing') !== '1') {
            $add('info', 'Suchmaschinen-Indexierung ist deaktiviert', 'Nach Abschluss der Prüfung unter „Unternehmenseinstellungen → SEO“ aktivieren.', '/admin/settings');
        }
        if (is_debug()) {
            $add('error', 'Debug-Modus ist aktiv', 'app.debug in env.php auf false setzen.', '/admin/system');
        }
        if ((string) config('install.token') !== '') {
            $add('warning', 'Installations-Token noch gesetzt', 'install.token in env.php leeren – die Installation ist abgeschlossen.', '/admin/system');
        }
        if (!str_starts_with((string) config('app.url'), 'https://')) {
            $add('warning', 'Website-Adresse ohne HTTPS', 'app.url in env.php auf https://… setzen, sobald das SSL-Zertifikat aktiv ist.', '/admin/system');
        }
        return $c;
    }

    public static function counts(): array
    {
        $counts = ['error' => 0, 'warning' => 0, 'info' => 0];
        foreach (self::checks() as $c) {
            $counts[$c['level']]++;
        }
        return $counts;
    }
}
