<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/**
 * Central company & site settings – the single source of truth for company data
 * that appears in header, footer, contact page, Impressum and structured data.
 */
final class Settings
{
    private static ?array $cache = null;

    /**
     * Schema used to build the settings form. type: text|textarea|email|url|media|bool|number|select
     * "legal" marks information relevant for the Impressum checklist.
     */
    public static function schema(): array
    {
        return [
            'Unternehmen' => [
                'company_name'      => ['label' => 'Firmenname (inkl. Rechtsform)', 'type' => 'text', 'required' => true],
                'legal_form'        => ['label' => 'Rechtsform', 'type' => 'text', 'help' => 'z. B. Gesellschaft mit beschränkter Haftung (GmbH)'],
                'managing_directors'=> ['label' => 'Vertretungsberechtigte Geschäftsführer', 'type' => 'text', 'legal' => true, 'help' => 'Pflichtangabe im Impressum (§ 5 DDG). Mehrere Namen mit Komma trennen.'],
                'register_court'    => ['label' => 'Registergericht', 'type' => 'text', 'legal' => true, 'help' => 'z. B. Amtsgericht Hannover'],
                'register_number'   => ['label' => 'Handelsregisternummer', 'type' => 'text', 'legal' => true, 'help' => 'z. B. HRB 123456'],
                'vat_id'            => ['label' => 'Umsatzsteuer-Identifikationsnummer', 'type' => 'text', 'help' => 'gemäß § 27a UStG'],
                'responsible_person'=> ['label' => 'Verantwortlich für den Inhalt (§ 18 Abs. 2 MStV)', 'type' => 'text', 'help' => 'Name und Anschrift, falls journalistisch-redaktionelle Inhalte (z. B. News) veröffentlicht werden.'],
                'professional_info' => ['label' => 'Weitere Pflichtangaben (z. B. Kammer, Aufsichtsbehörde, Berufshaftpflicht)', 'type' => 'textarea', 'help' => 'Nur ausfüllen, wenn zutreffend – z. B. Eintragung in die Handwerksrolle.'],
            ],
            'Adressen & Kontakt' => [
                'registered_street' => ['label' => 'Sitz – Straße und Hausnummer', 'type' => 'text', 'required' => true],
                'registered_zip'    => ['label' => 'Sitz – PLZ', 'type' => 'text', 'required' => true],
                'registered_city'   => ['label' => 'Sitz – Ort', 'type' => 'text', 'required' => true],
                'office_street'     => ['label' => 'Büro – Straße und Hausnummer', 'type' => 'text'],
                'office_zip'        => ['label' => 'Büro – PLZ', 'type' => 'text'],
                'office_city'       => ['label' => 'Büro – Ort', 'type' => 'text'],
                'email'             => ['label' => 'E-Mail-Adresse', 'type' => 'email', 'required' => true],
                'phone'             => ['label' => 'Telefon', 'type' => 'text', 'required' => true],
                'fax'               => ['label' => 'Telefax', 'type' => 'text'],
                'opening_hours'     => ['label' => 'Bürozeiten', 'type' => 'textarea', 'help' => 'Eine Zeile pro Eintrag, z. B. „Mo–Fr: 7:00–16:00 Uhr“. Leer lassen, wenn nicht angegeben werden soll.'],
                'maps_url'          => ['label' => 'Link „Route planen“ (optional)', 'type' => 'url', 'help' => 'Externer Link (z. B. OpenStreetMap). Es wird KEINE Karte eingebettet und nichts vorab geladen.'],
            ],
            'Kontaktformular' => [
                'contact_recipient'    => ['label' => 'Empfänger für Benachrichtigungen', 'type' => 'email', 'help' => 'Nur wirksam, wenn der E-Mail-Versand in der Konfiguration eingerichtet ist. Leer = E-Mail-Adresse des Unternehmens.'],
                'contact_project_types'=> ['label' => 'Auswahl „Art des Vorhabens“', 'type' => 'textarea', 'help' => 'Eine Option pro Zeile.'],
                'contact_success'      => ['label' => 'Bestätigungstext nach dem Absenden', 'type' => 'textarea'],
                'contact_privacy_note' => ['label' => 'Datenschutzhinweis am Formular', 'type' => 'textarea'],
                'enquiry_retention_days' => ['label' => 'Aufbewahrungsdauer abgeschlossener Anfragen (Tage)', 'type' => 'number', 'help' => 'Abgeschlossene/beantwortete Anfragen, die älter sind, können unter „Kontaktanfragen“ gesammelt gelöscht werden.'],
            ],
            'Website & Darstellung' => [
                'site_tagline'      => ['label' => 'Kurzbeschreibung / Claim', 'type' => 'text'],
                'header_cta_label'  => ['label' => 'Button im Kopfbereich – Beschriftung', 'type' => 'text'],
                'header_cta_url'    => ['label' => 'Button im Kopfbereich – Ziel', 'type' => 'link'],
                'footer_text'       => ['label' => 'Text im Fußbereich', 'type' => 'textarea'],
                'logo_media_id'     => ['label' => 'Logo (optional, ersetzt das Standardlogo)', 'type' => 'media', 'help' => 'Querformat, transparenter Hintergrund (PNG/WebP) empfohlen.'],
                'logo_light_media_id' => ['label' => 'Logo für dunklen Hintergrund (Fußbereich, optional)', 'type' => 'media'],
                'favicon_media_id'  => ['label' => 'Favicon (optional, quadratisch, mind. 180 × 180 px)', 'type' => 'media'],
                'social_facebook'   => ['label' => 'Facebook-Profil (URL)', 'type' => 'url'],
                'social_instagram'  => ['label' => 'Instagram-Profil (URL)', 'type' => 'url'],
                'social_linkedin'   => ['label' => 'LinkedIn-Profil (URL)', 'type' => 'url'],
                'show_media_credits'=> ['label' => 'Bildnachweise unter Bildern anzeigen', 'type' => 'bool', 'help' => 'Erforderlich, solange lizenzierte Platzhalterbilder (z. B. CC BY-SA) verwendet werden.'],
            ],
            'SEO & Datenschutz' => [
                'seo_indexing'      => ['label' => 'Suchmaschinen dürfen die Website indexieren', 'type' => 'bool', 'help' => 'Erst nach Abschluss aller Inhalte und Rechtstexte aktivieren.'],
                'seo_title_suffix'  => ['label' => 'Titel-Zusatz', 'type' => 'text', 'help' => 'Wird an Seitentitel angehängt, z. B. „ | Wert auf Fundament GmbH“'],
                'seo_default_description' => ['label' => 'Standard-Meta-Beschreibung', 'type' => 'textarea'],
                'seo_og_media_id'   => ['label' => 'Standard-Vorschaubild für soziale Netzwerke', 'type' => 'media'],
                'hosting_provider'  => ['label' => 'Hosting-Anbieter (für die Datenschutzerklärung)', 'type' => 'text'],
                'locale'            => ['label' => 'Sprache / Locale', 'type' => 'select', 'options' => ['de_DE' => 'Deutsch (Deutschland)']],
            ],
        ];
    }

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (DB::all('SELECT setting_key, value FROM settings') as $row) {
                self::$cache[$row['setting_key']] = (string) $row['value'];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        try {
            $all = self::all();
        } catch (\Throwable) {
            return $default;
        }
        $v = $all[$key] ?? '';
        return $v !== '' ? $v : $default;
    }

    public static function setMany(array $values): void
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                DB::exec('INSERT INTO settings (setting_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$key, (string) $value]);
            }
        });
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    /** Lines of a textarea setting. */
    public static function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', self::get($key)) ?: [])));
    }

    public static function registeredAddress(): string
    {
        return trim(self::get('registered_street') . ', ' . self::get('registered_zip') . ' ' . self::get('registered_city'), ', ');
    }

    public static function officeAddress(): string
    {
        return trim(self::get('office_street') . ', ' . self::get('office_zip') . ' ' . self::get('office_city'), ', ');
    }
}
