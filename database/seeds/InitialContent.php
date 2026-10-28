<?php
declare(strict_types=1);

use App\Core\DB;
use App\Repositories\BlockRepository;
use App\Security\HtmlSanitizer;
use App\Services\Media;
use App\Services\Settings;

/**
 * Initial content for Wert auf Fundament GmbH.
 *
 * Rules followed here:
 *  - Only company data supplied by the client is filled in. Missing legal data stays empty and
 *    is reported by the compliance checklist.
 *  - Claims that need confirmation (e.g. own cranes, maintenance services) are seeded as HIDDEN
 *    blocks with an admin note, or flagged via services.admin_note.
 *  - Legal texts contain "[BITTE PRÜFEN …]" markers where the company/legal counsel must decide.
 *  - Photos are licensed Wikimedia Commons images, flagged as placeholders with credits.
 */
final class InitialContent
{
    private array $media = [];
    private int $libCta = 0;

    public function __construct(private int $adminId)
    {
    }

    public function run(): void
    {
        DB::transaction(function () {
            $this->settings();
            $this->importMedia();
            $this->library();
            $this->faqs();
            $services = $this->services();
            $pages = $this->pages();
            $this->navigation($pages, $services);
        });
    }

    // ------------------------------------------------------------------ helpers

    private function m(string $key): int
    {
        return $this->media[$key] ?? 0;
    }

    private function rt(string $html): string
    {
        return HtmlSanitizer::clean($html);
    }

    private function blocks(string $owner, int $ownerId, array $blocks): void
    {
        foreach ($blocks as $b) {
            BlockRepository::create($owner, $ownerId, $b[0], $b[1], null, $b[2] ?? true, $b[3] ?? '');
        }
    }

    private function cta(): array
    {
        return ['library_ref', ['library' => $this->libCta]];
    }

    // ------------------------------------------------------------------ settings

    private function settings(): void
    {
        Settings::setMany([
            'company_name' => 'Wert auf Fundament GmbH',
            'legal_form' => 'Gesellschaft mit beschränkter Haftung (GmbH)',
            'managing_directors' => '',
            'register_court' => '',
            'register_number' => '',
            'vat_id' => 'DE460 571 549',
            'responsible_person' => '',
            'professional_info' => '',
            'registered_street' => 'Robert-Koch-Str. 67',
            'registered_zip' => '30853',
            'registered_city' => 'Langenhagen',
            'office_street' => 'Siemensstraße 5',
            'office_zip' => '30827',
            'office_city' => 'Garbsen',
            'email' => 'info@wertauffundament.de',
            'phone' => '0511 999 333 36',
            'fax' => '',
            'opening_hours' => '',
            'maps_url' => '',
            'contact_recipient' => '',
            'contact_project_types' => "Garten- und Landschaftsbau\nHoch- und Tiefbau\nAbbruch und Altlastensanierung\nRohbau\nSonstiges Vorhaben",
            'contact_success' => 'Wir haben Ihre Nachricht erhalten und melden uns zeitnah bei Ihnen.',
            'contact_privacy_note' => 'Ihre Angaben verwenden wir ausschließlich zur Bearbeitung Ihrer Anfrage. Weitere Informationen, auch zu Ihren Rechten, finden Sie in unserer Datenschutzerklärung.',
            'enquiry_retention_days' => '365',
            'site_tagline' => 'Bauunternehmen in der Region Hannover – Werte schaffen.',
            'header_cta_label' => 'Projekt anfragen',
            'header_cta_url' => '/kontakt#kontaktformular',
            'footer_text' => 'Ihr Bauunternehmen für Hoch- und Tiefbau, Rohbau, Abbruch sowie Garten- und Landschaftsbau – mit Sitz in Langenhagen und Büro in Garbsen.',
            'show_media_credits' => '1',
            'seo_indexing' => '0',
            'seo_title_suffix' => ' | Wert auf Fundament GmbH',
            'seo_default_description' => 'Wert auf Fundament GmbH – Bauunternehmen aus Langenhagen für Hoch- und Tiefbau, Rohbau, Abbruch und Rückbau sowie Garten- und Landschaftsbau in der Region Hannover.',
            'hosting_provider' => '',
            'locale' => 'de_DE',
        ]);
    }

    private function importMedia(): void
    {
        $dir = __DIR__ . '/images';
        $manifest = json_decode((string) file_get_contents($dir . '/manifest.json'), true) ?: [];
        foreach ($manifest as $m) {
            $key = pathinfo($m['file'], PATHINFO_FILENAME);
            $this->media[$key] = Media::importLocal($dir . '/' . $m['file'], $this->adminId, [
                'alt' => $m['alt'],
                'credit' => $m['credit'] . ' / Wikimedia Commons',
                'license' => $m['license'],
                'source_url' => $m['source'],
                'is_placeholder' => true,
            ]);
        }
    }

    // ------------------------------------------------------------------ library & FAQ

    private function library(): void
    {
        $this->libCta = BlockRepository::create('library', null, 'cta', [
            'eyebrow' => 'Ihr Vorhaben',
            'heading' => 'Lassen Sie uns über Ihr Projekt sprechen.',
            'text' => "Ob Außenanlage, Baugrube, Rückbau oder Rohbau: Schildern Sie uns Ihr Vorhaben. Wir melden uns mit einer ersten Einschätzung und vereinbaren bei Bedarf einen Termin vor Ort.",
            'button1_label' => 'Anfrage senden',
            'button1_url' => '/kontakt#kontaktformular',
            'button2_label' => '',
            'button2_url' => '',
            'show_phone' => '1',
            'image' => $this->m('baustelle-betonpumpe'),
            'variant' => 'dark',
        ], 'Kontakt-Aufforderung (Standard)');
    }

    private function faqs(): void
    {
        $cat = DB::insert('faq_categories', ['name' => 'Allgemein', 'sort_order' => 10]);
        $faqs = [
            ['Wie erhalte ich ein Angebot?', '<p>Am einfachsten über unser Kontaktformular oder telefonisch. Beschreiben Sie kurz Ihr Vorhaben, den Ort und den gewünschten Zeitraum. Bei den meisten Projekten ist anschließend ein Termin vor Ort sinnvoll, damit wir den Aufwand realistisch einschätzen und ein belastbares Angebot erstellen können.</p>'],
            ['Welche Unterlagen helfen bei einer Anfrage?', '<p>Hilfreich sind – soweit vorhanden – Lagepläne, Baupläne, Fotos der Situation vor Ort, ein Baugrundgutachten sowie Angaben zum gewünschten Zeitrahmen. Fehlende Unterlagen sind kein Hindernis: Vieles klären wir gemeinsam im Gespräch.</p>'],
            ['Wo befindet sich Ihr Unternehmen?', '<p>Unser Firmensitz liegt in Langenhagen, unser Büro in Garbsen – beide in der Region Hannover. Ob und in welchem Umfang wir Ihr Vorhaben übernehmen können, besprechen wir gern persönlich mit Ihnen.</p>'],
            ['Kümmern Sie sich auch um die Entsorgung von Aushub und Abbruchmaterial?', '<p>Im Rahmen unserer Erd-, Abbruch- und Rückbauarbeiten organisieren wir die Trennung und die fachgerechte Entsorgung bzw. Verwertung der anfallenden Materialien. Den genauen Leistungsumfang legen wir projektbezogen im Angebot fest.</p>'],
        ];
        foreach ($faqs as $i => [$q, $a]) {
            DB::insert('faqs', ['category_id' => $cat, 'question' => $q, 'answer' => $this->rt($a), 'status' => 'published', 'sort_order' => ($i + 1) * 10]);
        }
    }

    // ------------------------------------------------------------------ services

    private function service(array $s, ?int $parent = null): int
    {
        return DB::insert('services', [
            'parent_id' => $parent,
            'slug' => $s['slug'],
            'title' => $s['title'],
            'teaser' => $s['teaser'],
            'intro' => $s['intro'],
            'icon' => $s['icon'],
            'image_media_id' => $this->m($s['image']) ?: null,
            'status' => $s['status'] ?? 'published',
            'is_featured' => $s['featured'] ?? 0,
            'sort_order' => $s['sort'],
            'meta_title' => $s['meta_title'] ?? '',
            'meta_description' => $s['meta_description'] ?? '',
            'admin_note' => $s['note'] ?? '',
        ]);
    }

    private function services(): array
    {
        // ---------------------------------------------------- Garten- und Landschaftsbau
        $garden = $this->service([
            'slug' => 'garten-und-landschaftsbau', 'title' => 'Garten- und Landschaftsbau', 'icon' => 'trees', 'image' => 'gartenweg', 'sort' => 10, 'featured' => 1,
            'teaser' => 'Terrassen, Wege, Pflasterflächen, Rasen, Pflanzungen und Einfriedungen – Außenanlagen, die funktional geplant und handwerklich sauber umgesetzt sind.',
            'intro' => 'Ein Außenbereich ist mehr als die Fläche rund ums Haus. Wir planen und bauen Gärten und Außenanlagen, die zur Nutzung, zum Gebäude und zum Grundstück passen – von der ersten Idee bis zur fertigen Fläche.',
            'meta_description' => 'Garten- und Landschaftsbau in der Region Hannover: Terrassen, Pflasterflächen, Wege, Rasen, Pflanzungen, Zäune und Einfriedungen von der Wert auf Fundament GmbH.',
            'note' => 'Bitte bestätigen: Sind alle aufgeführten Unterleistungen freigegeben? Pflegeleistungen und die Bildergalerie sind bis zur Bestätigung ausgeblendet.',
        ]);
        $this->blocks('service', $garden, [
            ['text', [
                'eyebrow' => 'Garten- und Landschaftsbau', 'heading' => 'Außenanlagen mit solidem Unterbau', 'layout' => 'split', 'bg' => 'white',
                'body' => $this->rt('<p>Ob Terrasse, Hofeinfahrt oder Gartenweg: Wie lange eine Fläche schön und belastbar bleibt, entscheidet sich zum großen Teil unter der Oberfläche. Ein fachgerecht aufgebauter Unterbau mit tragfähiger Schicht, ausreichender Verdichtung und durchdachtem Gefälle verhindert Setzungen, Pfützen und Frostschäden.</p><p>Weil wir auch im Erd- und Tiefbau zu Hause sind, betrachten wir Außenanlagen ganzheitlich – von der Geländemodellierung über die Entwässerung bis zur letzten Pflanzung. So entstehen Flächen, die nicht nur am ersten Tag überzeugen.</p>'),
            ]],
            ['checklist', [
                'eyebrow' => 'Leistungen', 'heading' => 'Was wir für Ihren Außenbereich umsetzen', 'intro' => 'Den genauen Leistungsumfang stimmen wir individuell mit Ihnen ab.', 'image' => $this->m('pflasterarbeiten'), 'bg' => 'sand',
                'items' => [
                    ['title' => 'Gartenplanung und -gestaltung', 'text' => 'Gemeinsam entwickeln wir ein Konzept für Ihren Außenbereich – abgestimmt auf Nutzung, Pflegeaufwand, Budget und die Gegebenheiten des Grundstücks.'],
                    ['title' => 'Terrassen und Pflasterflächen', 'text' => 'Terrassen, Hofflächen und Stellplätze aus Betonstein, Naturstein oder Keramik – mit fachgerecht aufgebautem Unterbau und geplanter Entwässerung.'],
                    ['title' => 'Wege und Zufahrten', 'text' => 'Gartenwege, Hauszugänge und befahrbare Zufahrten, dimensioniert nach der späteren Belastung und sauber an bestehende Flächen angeschlossen.'],
                    ['title' => 'Rasen und Pflanzungen', 'text' => 'Bodenvorbereitung, Rollrasen oder Rasenansaat sowie Pflanzungen von Stauden, Gehölzen und Hecken.'],
                    ['title' => 'Zäune, Hecken und Einfriedungen', 'text' => 'Grundstücksgrenzen klar und dauerhaft gestalten – mit Zaunanlagen, Toren, Mauern, Gabionen oder Heckenpflanzungen.'],
                    ['title' => 'Erdarbeiten und Geländemodellierung', 'text' => 'Abtrag, Auftrag und Profilierung des Geländes als belastbare Grundlage für die gesamte Außenanlage.'],
                ],
            ]],
            ['checklist', [
                'eyebrow' => 'Pflege', 'heading' => 'Garten- und Gehölzpflege', 'intro' => '', 'image' => 0, 'bg' => 'white',
                'items' => [
                    ['title' => 'Rückschnitt von Hecken und Gehölzen', 'text' => 'Form- und Pflegeschnitt zur passenden Jahreszeit.'],
                    ['title' => 'Saisonale Gartenpflege', 'text' => 'Pflege von Rasen-, Beet- und Pflanzflächen.'],
                ],
            ], false, 'Nur einblenden, wenn Pflegeleistungen (Rückschnitt, Saisonpflege, ggf. Baumpflege) tatsächlich angeboten werden.'],
            ['image_text', [
                'eyebrow' => 'Vorgehen', 'heading' => 'Vom Rohgelände zur nutzbaren Fläche', 'image' => $this->m('rollrasen'), 'image_position' => 'left', 'caption' => '', 'button_label' => '', 'button_url' => '', 'bg' => 'white',
                'body' => $this->rt('<p>Am Anfang steht die Bestandsaufnahme: Höhen, Bodenverhältnisse, vorhandene Leitungen und die gewünschte Nutzung. Daraus entwickeln wir einen Vorschlag für Aufbau, Materialien und Entwässerung.</p><p>Materialien, Ausführung und Zeitplan stimmen wir vorab transparent mit Ihnen ab. Während der Arbeiten haben Sie einen festen Ansprechpartner – und nach Abschluss eine Fläche, die für die tägliche Nutzung bereit ist.</p>'),
            ]],
            ['gallery', ['eyebrow' => 'Einblicke', 'heading' => 'Projekte im Garten- und Landschaftsbau', 'intro' => '', 'gallery' => 0, 'layout' => 'mosaic', 'bg' => 'sand'], false, 'Galerie mit eigenen Projektfotos anlegen, hier auswählen und dann einblenden.'],
            $this->cta(),
        ]);

        // ---------------------------------------------------- Hoch- und Tiefbau
        $civil = $this->service([
            'slug' => 'hoch-und-tiefbau', 'title' => 'Hoch- und Tiefbau', 'icon' => 'construction', 'image' => 'rohrverlegung', 'sort' => 20, 'featured' => 1,
            'teaser' => 'Kabel- und Rohrverlegung, Fundamentarbeiten, Erdarbeiten und Baugrundverbesserung – die technische Grundlage für Gebäude, Leitungen und Verkehrsflächen.',
            'intro' => 'Was unter der Oberfläche liegt, entscheidet über die Qualität des gesamten Bauwerks. Im Hoch- und Tiefbau schaffen wir tragfähige Grundlagen – präzise eingemessen, fachgerecht ausgeführt und nachvollziehbar dokumentiert.',
            'meta_description' => 'Hoch- und Tiefbau aus Langenhagen: Kabel- und Rohrverlegung, Fundamentarbeiten, Erdarbeiten und Baugrundverbesserung für private und gewerbliche Bauvorhaben.',
        ]);
        $this->blocks('service', $civil, [
            ['text', [
                'eyebrow' => 'Hoch- und Tiefbau', 'heading' => 'Tragfähige Grundlagen für jedes Bauvorhaben', 'layout' => 'split', 'bg' => 'white',
                'body' => $this->rt('<p>Bevor ein Gebäude in die Höhe wächst oder eine Leitung in Betrieb geht, muss die Basis stimmen: ein tragfähiger Baugrund, sauber hergestellte Gräben und Baugruben, Fundamente nach statischer Vorgabe.</p><p>Wir verbinden Tiefbau und Hochbau zu einem durchgängigen Ablauf. Das reduziert Schnittstellen, vermeidet Wartezeiten zwischen den Gewerken und gibt Ihnen einen Ansprechpartner für die gesamte Gründungsphase.</p>'),
            ]],
            ['service_cards', ['eyebrow' => 'Leistungsbereiche', 'heading' => 'Hoch- und Tiefbau im Detail', 'intro' => '', 'source' => 'children', 'services' => [], 'layout' => 'grid', 'link_label' => 'Zur Leistung', 'bg' => 'sand']],
            ['steps', [
                'eyebrow' => 'Ablauf', 'heading' => 'So läuft eine Tiefbaumaßnahme ab', 'intro' => '', 'bg' => 'white',
                'items' => [
                    ['title' => 'Planung und Erkundung', 'text' => 'Abstimmung der Unterlagen, Einholen von Leitungsauskünften und Klärung der Bodenverhältnisse vor dem ersten Spatenstich.'],
                    ['title' => 'Baustelleneinrichtung', 'text' => 'Einrichtung und Absicherung der Baustelle – bei Arbeiten im öffentlichen Raum nach den jeweiligen verkehrsrechtlichen Vorgaben.'],
                    ['title' => 'Ausführung', 'text' => 'Aushub, Verbau, Verlegung oder Gründung und anschließend lagenweise Verfüllung und Verdichtung.'],
                    ['title' => 'Wiederherstellung und Dokumentation', 'text' => 'Wiederherstellen der Oberflächen, Aufräumen der Baustelle und Übergabe mit den vereinbarten Nachweisen.'],
                ],
            ]],
            ['features', [
                'eyebrow' => 'Unser Anspruch', 'heading' => 'Worauf wir im Tiefbau achten', 'intro' => '', 'columns' => '4', 'bg' => 'dark',
                'items' => [
                    ['icon' => 'clipboard-check', 'title' => 'Sorgfältige Vorbereitung', 'text' => 'Leitungsauskünfte und Abstimmungen erfolgen vor Beginn – nicht während der Arbeiten.'],
                    ['icon' => 'shield-check', 'title' => 'Sichere Baustelle', 'text' => 'Absicherung, Verbau und Arbeitsschutz haben für uns Vorrang.'],
                    ['icon' => 'layers', 'title' => 'Verdichtung in Lagen', 'text' => 'Verfüllungen werden lagenweise eingebaut und verdichtet, um spätere Setzungen zu vermeiden.'],
                    ['icon' => 'clipboard-list', 'title' => 'Nachvollziehbare Dokumentation', 'text' => 'Die ausgeführten Arbeiten werden für Sie und spätere Gewerke nachvollziehbar festgehalten.'],
                ],
            ]],
            $this->cta(),
        ]);

        $cable = $this->service([
            'slug' => 'kabel-und-rohrverlegung', 'title' => 'Kabel- und Rohrverlegung', 'icon' => 'cable', 'image' => 'kabelverlegung', 'sort' => 10,
            'teaser' => 'Leerrohre, Kabel sowie Ver- und Entsorgungsleitungen in offener Bauweise – inklusive Grabenaushub, Bettung, Verfüllung und Wiederherstellung der Oberfläche.',
            'intro' => 'Ob Hausanschluss, Leerrohrtrasse oder Entwässerungsleitung: Wir stellen Leitungsgräben her, verlegen Rohre und Kabel nach den Vorgaben des Netzbetreibers bzw. der Planung und stellen die Oberfläche anschließend wieder her.',
            'meta_description' => 'Kabel- und Rohrverlegung in der Region Hannover: Leitungsgräben, Leerrohre, Kabel, Wasser- und Abwasserleitungen sowie Hausanschlüsse.',
        ], $civil);
        $this->blocks('service', $cable, [
            ['checklist', [
                'eyebrow' => 'Leistungen', 'heading' => 'Leitungsbau in offener Bauweise', 'intro' => '', 'image' => 0, 'bg' => 'white',
                'items' => [
                    ['title' => 'Leitungsgräben', 'text' => 'Herstellen von Gräben in der erforderlichen Tiefe und Breite – mit Verbau, wo es die Situation verlangt.'],
                    ['title' => 'Leerrohr- und Kabelverlegung', 'text' => 'Verlegung von Schutz- und Leerrohren sowie Kabeln, z. B. für Strom, Telekommunikation und Glasfaser.'],
                    ['title' => 'Ver- und Entsorgungsleitungen', 'text' => 'Rohrleitungen für Trinkwasser, Schmutz- und Regenwasser nach Planung und Vorgaben der Versorger.'],
                    ['title' => 'Hausanschlüsse', 'text' => 'Erdarbeiten und Leitungsführung für Hausanschlüsse in Abstimmung mit den zuständigen Netzbetreibern.'],
                    ['title' => 'Bettung und Verfüllung', 'text' => 'Leitungszone nach Vorgabe, anschließend lagenweise Verfüllung und Verdichtung.'],
                    ['title' => 'Wiederherstellung von Oberflächen', 'text' => 'Rasen, Pflaster oder befestigte Flächen werden nach Abschluss wiederhergestellt.'],
                ],
            ]],
            ['image_text', [
                'eyebrow' => 'Vorbereitung', 'heading' => 'Sorgfalt vor dem ersten Spatenstich', 'image' => $this->m('kabelverlegung'), 'image_position' => 'right', 'caption' => '', 'button_label' => '', 'button_url' => '', 'bg' => 'sand',
                'body' => $this->rt('<p>Im Untergrund liegen oft mehr Leitungen, als auf den ersten Blick zu vermuten ist. Deshalb holen wir vor Beginn der Arbeiten die erforderlichen Leitungsauskünfte ein und stimmen uns mit den betroffenen Netzbetreibern ab.</p><p>So werden Schäden an bestehenden Leitungen vermieden, und die neue Trasse fügt sich sauber in den Bestand ein.</p>'),
            ]],
            $this->cta(),
        ]);

        $foundation = $this->service([
            'slug' => 'fundamentarbeiten', 'title' => 'Fundamentarbeiten', 'icon' => 'layers', 'image' => 'fundament-bewehrung', 'sort' => 20,
            'teaser' => 'Streifen-, Einzel- und Plattenfundamente sowie Bodenplatten – nach Vorgabe der Tragwerksplanung geschalt, bewehrt und betoniert.',
            'intro' => 'Das Fundament trägt das gesamte Bauwerk. Wir führen Gründungsarbeiten nach den Vorgaben der Tragwerksplanung aus – vom Aushub über die Sauberkeitsschicht bis zur betonierten Bodenplatte.',
            'meta_description' => 'Fundamentarbeiten: Streifenfundamente, Einzelfundamente und Bodenplatten nach statischer Vorgabe – Wert auf Fundament GmbH, Langenhagen.',
        ], $civil);
        $this->blocks('service', $foundation, [
            ['checklist', [
                'eyebrow' => 'Leistungen', 'heading' => 'Gründungen nach statischer Vorgabe', 'intro' => '', 'image' => $this->m('bodenplatte-beton'), 'bg' => 'white',
                'items' => [
                    ['title' => 'Streifen- und Einzelfundamente', 'text' => 'Für Wohngebäude, Nebengebäude, Mauern und Anlagen.'],
                    ['title' => 'Bodenplatten', 'text' => 'Herstellung von Bodenplatten inklusive Unterbau, Sauberkeitsschicht und Betonage.'],
                    ['title' => 'Schalung und Bewehrung', 'text' => 'Schalungsarbeiten und Verlegen der Bewehrung nach Bewehrungsplan.'],
                    ['title' => 'Vorbereitende Erdarbeiten', 'text' => 'Aushub, Herstellung des Planums und – falls erforderlich – Bodenaustausch.'],
                ],
            ]],
            ['notice', [
                'heading' => 'Grundlage ist immer die Statik',
                'body' => $this->rt('<p>Fundamentarbeiten führen wir auf Basis der Tragwerksplanung und – soweit vorhanden – eines Baugrundgutachtens aus. Liegen diese Unterlagen noch nicht vor, besprechen wir gern, welche Planungsschritte vor Baubeginn notwendig sind.</p>'),
                'tone' => 'info',
            ]],
            $this->cta(),
        ]);

        $earth = $this->service([
            'slug' => 'erdarbeiten', 'title' => 'Erdarbeiten', 'icon' => 'shovel', 'image' => 'erdarbeiten-hausbau', 'sort' => 30,
            'teaser' => 'Mutterbodenabtrag, Baugruben, Bodenaushub, Verfüllung und Verdichtung – die Vorbereitung für Hochbau, Tiefbau und Außenanlagen.',
            'intro' => 'Erdarbeiten stehen am Anfang fast jedes Bauvorhabens. Wir bereiten Grundstücke vor, heben Baugruben aus und schaffen mit sauber profilierten und verdichteten Flächen die Basis für alle folgenden Arbeiten.',
            'meta_description' => 'Erdarbeiten in der Region Hannover: Mutterbodenabtrag, Baugrubenaushub, Geländeprofilierung, Verfüllung, Verdichtung und Abtransport von Aushub.',
        ], $civil);
        $this->blocks('service', $earth, [
            ['checklist', [
                'eyebrow' => 'Leistungen', 'heading' => 'Erdarbeiten aus einer Hand', 'intro' => '', 'image' => 0, 'bg' => 'white',
                'items' => [
                    ['title' => 'Mutterbodenabtrag und -sicherung', 'text' => 'Abtragen und fachgerechtes Lagern des Oberbodens zur späteren Wiederverwendung.'],
                    ['title' => 'Baugrubenaushub', 'text' => 'Aushub von Baugruben für Keller, Bodenplatten und Fundamente – mit Böschung oder Verbau.'],
                    ['title' => 'Geländeprofilierung', 'text' => 'Herstellen von Höhen und Gefällen nach Planung.'],
                    ['title' => 'Verfüllung und Verdichtung', 'text' => 'Lagenweiser Einbau geeigneter Materialien und Verdichtung nach den Anforderungen der Planung.'],
                    ['title' => 'Abtransport und Entsorgung', 'text' => 'Abfuhr von Bodenaushub und Zuführung zur Verwertung bzw. Entsorgung entsprechend seiner Einstufung.'],
                ],
            ]],
            ['image_text', [
                'eyebrow' => 'Planung', 'heading' => 'Die Bodenverhältnisse im Blick', 'image' => $this->m('baugrube'), 'image_position' => 'left', 'caption' => '', 'button_label' => 'Baugrundverbesserung', 'button_url' => '/leistungen/hoch-und-tiefbau/baugrundverbesserung', 'bg' => 'sand',
                'body' => $this->rt('<p>Welche Böschungswinkel möglich sind, ob ein Verbau notwendig ist und wie der Aushub einzustufen ist, hängt vom Boden ab. Wir berücksichtigen vorhandene Gutachten und stimmen das Vorgehen mit Ihnen und Ihren Planern ab.</p><p>Ist der Untergrund nicht ausreichend tragfähig, zeigen wir Ihnen Möglichkeiten der Baugrundverbesserung auf.</p>'),
            ]],
            $this->cta(),
        ]);

        $ground = $this->service([
            'slug' => 'baugrundverbesserung', 'title' => 'Baugrundverbesserung', 'icon' => 'mountain', 'image' => 'baugrube', 'sort' => 40,
            'teaser' => 'Bodenaustausch, Verdichtung und Herstellung tragfähiger Schichten – damit der Baugrund die geplanten Lasten sicher aufnehmen kann.',
            'intro' => 'Nicht jeder Boden ist von Natur aus ausreichend tragfähig. Auf Grundlage eines Baugrundgutachtens bzw. der Vorgaben der Fachplanung bereiten wir den Untergrund so vor, dass Gebäude, Verkehrsflächen und Leitungen sicher gegründet werden können.',
            'meta_description' => 'Baugrundverbesserung und Baustellenvorbereitung: Bodenaustausch, Verdichtung, Tragschichten und Arbeitsflächen – Wert auf Fundament GmbH.',
            'note' => 'Bitte bestätigen, welche Verfahren der Baugrundverbesserung angeboten werden (z. B. Bodenaustausch, Verdichtung, Bodenverfestigung mit Bindemitteln).',
        ], $civil);
        $this->blocks('service', $ground, [
            ['checklist', [
                'eyebrow' => 'Leistungen', 'heading' => 'Baugrund vorbereiten und verbessern', 'intro' => '', 'image' => 0, 'bg' => 'white',
                'items' => [
                    ['title' => 'Bodenaustausch', 'text' => 'Entfernen nicht tragfähiger Schichten und Ersatz durch geeignetes, verdichtungsfähiges Material.'],
                    ['title' => 'Verdichtung', 'text' => 'Lagenweise Verdichtung mit geeignetem Gerät entsprechend den Anforderungen der Planung.'],
                    ['title' => 'Tragschichten und Planum', 'text' => 'Herstellen von Tragschichten und Planum als Grundlage für Bodenplatten und Verkehrsflächen.'],
                    ['title' => 'Baustraßen und Arbeitsflächen', 'text' => 'Befestigte Zufahrten und Flächen für Baustellenverkehr, Lagerung und Maschinen.'],
                ],
            ]],
            ['notice', [
                'heading' => 'Das passende Verfahren hängt vom Boden ab',
                'body' => $this->rt('<p>Welche Maßnahme sinnvoll ist, ergibt sich aus den Bodenverhältnissen und den geplanten Lasten. Grundlage sind das Baugrundgutachten bzw. die Vorgaben der Fachplanung. Wir beraten Sie gern zu den nächsten Schritten.</p>'),
                'tone' => 'info',
            ]],
            $this->cta(),
        ]);

        // ---------------------------------------------------- Abbruch und Altlastensanierung
        $demo = $this->service([
            'slug' => 'abbruch-und-altlastensanierung', 'title' => 'Abbruch und Altlastensanierung', 'icon' => 'hammer', 'image' => 'abbruch-bagger', 'sort' => 30, 'featured' => 1,
            'teaser' => 'Abbruch und Rückbau von Gebäuden und Bauteilen, Erdarbeiten und Entsorgung – sowie die Sanierung belasteter Flächen nach den geltenden Vorschriften.',
            'intro' => 'Vor dem Neubau steht oft der Rückbau. Wir übernehmen Abbruch- und Rückbauarbeiten kontrolliert und sicher, trennen Materialien sortenrein und sorgen für die fachgerechte Entsorgung – damit Ihr Grundstück für den nächsten Schritt bereit ist.',
            'meta_description' => 'Abbruch, Rückbau, Entkernung, Erdarbeiten und Entsorgung sowie Altlastensanierung nach den geltenden Vorschriften – Wert auf Fundament GmbH, Region Hannover.',
            'note' => 'Altlastensanierung: Leistungsumfang, vorhandene Sachkunde (z. B. TRGS 519 / TRGS 524) und ggf. Kooperationspartner vor dem Livegang bestätigen und Text bei Bedarf anpassen.',
        ]);
        $this->blocks('service', $demo, [
            ['text', [
                'eyebrow' => 'Abbruch und Rückbau', 'heading' => 'Kontrollierter Rückbau statt bloßem Abriss', 'layout' => 'split', 'bg' => 'white',
                'body' => $this->rt('<p>Ein Abbruch beginnt nicht mit dem Bagger, sondern mit der Planung: Welche Bauteile werden zurückgebaut, welche Materialien fallen an, und wie lassen sich Lärm, Staub und Erschütterungen für die Nachbarschaft möglichst gering halten?</p><p>Wir trennen anfallende Materialien bereits auf der Baustelle, damit möglichst viel verwertet werden kann – entsprechend den Vorgaben des Kreislaufwirtschaftsrechts. Was nicht verwertbar ist, wird ordnungsgemäß entsorgt.</p>'),
            ]],
            ['checklist', [
                'eyebrow' => 'Leistungen', 'heading' => 'Unser Leistungsspektrum', 'intro' => '', 'image' => $this->m('rueckbau'), 'bg' => 'sand',
                'items' => [
                    ['title' => 'Abbruch', 'text' => 'Abbruch von Gebäuden, Nebengebäuden, Garagen und baulichen Anlagen.'],
                    ['title' => 'Rückbau und Entkernung', 'text' => 'Selektiver Rückbau einzelner Bauteile sowie Entkernung von Gebäuden vor Umbau oder Sanierung.'],
                    ['title' => 'Erdarbeiten', 'text' => 'Entfernen von Fundamenten und Bodenplatten, Verfüllung und Herstellung eines planen Geländes.'],
                    ['title' => 'Entsorgung und Verwertung', 'text' => 'Trennung nach Abfallarten, Abtransport und Zuführung zur Verwertung bzw. Entsorgung mit den erforderlichen Nachweisen.'],
                    ['title' => 'Altlastensanierung', 'text' => 'Sanierung belasteter Flächen in Abstimmung mit Gutachtern und Behörden – im Rahmen der jeweils erforderlichen Qualifikationen und Genehmigungen.'],
                ],
            ]],
            ['notice', [
                'heading' => 'Abbruch ist nicht gleich Schadstoffsanierung',
                'body' => $this->rt('<p>Gebäude und Böden können Schadstoffe enthalten – etwa Asbest, künstliche Mineralfasern, PAK oder PCB. Arbeiten in solchen Bereichen unterliegen besonderen Vorschriften, unter anderem der Gefahrstoffverordnung und den Technischen Regeln für Gefahrstoffe (z. B. TRGS 519 für Asbest, TRGS 524 für kontaminierte Bereiche). Sie setzen eine vorherige Erkundung, sachkundiges Personal sowie die erforderlichen Anzeigen bzw. Genehmigungen voraus.</p><p>Welche Leistungen in diesem Bereich wir selbst erbringen und welche gegebenenfalls durch spezialisierte Fachbetriebe ausgeführt werden, klären wir projektbezogen und transparent mit Ihnen.</p>'),
                'tone' => 'warning',
            ]],
            ['steps', [
                'eyebrow' => 'Ablauf', 'heading' => 'In vier Schritten zum freien Grundstück', 'intro' => '', 'bg' => 'white',
                'items' => [
                    ['title' => 'Bestandsaufnahme', 'text' => 'Begehung des Objekts, Klärung des Umfangs und – wo erforderlich – Veranlassung einer Schadstofferkundung.'],
                    ['title' => 'Genehmigung und Planung', 'text' => 'Klärung der baurechtlichen Anforderungen (Genehmigung oder Anzeige) und Planung von Ablauf und Logistik.'],
                    ['title' => 'Rückbau und Trennung', 'text' => 'Kontrollierter Rückbau mit sortenreiner Trennung der anfallenden Materialien.'],
                    ['title' => 'Entsorgung und Übergabe', 'text' => 'Abtransport, Verwertung bzw. Entsorgung mit Nachweisen und Übergabe des vorbereiteten Grundstücks.'],
                ],
            ]],
            $this->cta(),
        ]);

        // ---------------------------------------------------- Rohbau
        $shell = $this->service([
            'slug' => 'rohbau', 'title' => 'Rohbau', 'icon' => 'brick-wall', 'image' => 'rohbau-mauerwerk', 'sort' => 40, 'featured' => 1,
            'teaser' => 'Mauerwerks- und Betonarbeiten für Wohn- und Gewerbebauten – vom Fundament bis zur fertigen Rohbauhülle.',
            'intro' => 'Ein solides Fundament und fachgerecht ausgeführtes Mauerwerk sind die Grundlage für die Langlebigkeit eines Gebäudes. Wir errichten Rohbauten zuverlässig, mit geeigneten Baustoffen und effizienten Abläufen.',
            'meta_description' => 'Rohbau aus Langenhagen: Fundamente, Bodenplatten, Mauerwerksbau sowie Beton- und Stahlbetonarbeiten für Wohn- und Gewerbebauten in der Region Hannover.',
        ]);
        $this->blocks('service', $shell, [
            ['text', [
                'eyebrow' => 'Rohbau', 'heading' => 'Die Grundlage für alles Weitere', 'layout' => 'split', 'bg' => 'white',
                'body' => $this->rt('<p>Im Rohbau entsteht die tragende Struktur eines Gebäudes – und damit die Basis für alle folgenden Gewerke. Maßhaltigkeit, saubere Anschlüsse und die richtige Ausführung entscheiden darüber, wie reibungslos der Innenausbau verläuft und wie lange das Gebäude Bestand hat.</p><p>Bauweise und Baustoffe wählen wir passend zu den Anforderungen des jeweiligen Projekts – etwa Kalksandstein, Porenbeton, Ziegel oder Stahlbeton. Grundlage sind immer die Planung und die Vorgaben der Tragwerksplanung.</p>'),
            ]],
            ['checklist', [
                'eyebrow' => 'Leistungen', 'heading' => 'Unsere Rohbauleistungen', 'intro' => '', 'image' => $this->m('mauerwerk-handwerk'), 'bg' => 'sand',
                'items' => [
                    ['title' => 'Erd- und Fundamentarbeiten', 'text' => 'Baugrube, Fundamente und Bodenplatte – auf Wunsch aus einer Hand mit unserem Tiefbau.'],
                    ['title' => 'Mauerwerksbau', 'text' => 'Tragende und nichttragende Wände aus dem jeweils geeigneten Mauerwerk.'],
                    ['title' => 'Beton- und Stahlbetonarbeiten', 'text' => 'Wände, Stützen, Unterzüge und Decken nach statischer Vorgabe.'],
                    ['title' => 'Schalungs- und Bewehrungsarbeiten', 'text' => 'Schalung und Bewehrung nach Schal- und Bewehrungsplänen.'],
                ],
            ]],
            ['image_text', [
                'eyebrow' => 'Logistik', 'heading' => 'Eigene Krantechnik', 'image' => $this->m('neubau-baustelle'), 'image_position' => 'right', 'caption' => '', 'button_label' => '', 'button_url' => '', 'bg' => 'white',
                'body' => $this->rt('<p>Für den Materialtransport auf unseren Rohbaustellen setzen wir eigene Krane ein. Das verkürzt Abstimmungswege und macht uns bei der Bauablaufplanung unabhängiger.</p>'),
            ], false, 'Aussage zu eigenen Kranen vor Veröffentlichung vom Unternehmen bestätigen lassen (Anzahl/Typen) und Text ggf. ergänzen.'],
            ['features', [
                'eyebrow' => 'Qualität', 'heading' => 'Worauf es im Rohbau ankommt', 'intro' => '', 'columns' => '4', 'bg' => 'dark',
                'items' => [
                    ['icon' => 'ruler', 'title' => 'Maßhaltigkeit', 'text' => 'Exakte Ausführung schafft die Grundlage für einen reibungslosen Ausbau.'],
                    ['icon' => 'package', 'title' => 'Geeignete Baustoffe', 'text' => 'Materialien werden nach den Anforderungen des Projekts ausgewählt.'],
                    ['icon' => 'layers', 'title' => 'Abgestimmte Abläufe', 'text' => 'Gründung und Rohbau greifen ohne unnötige Schnittstellen ineinander.'],
                    ['icon' => 'shield-check', 'title' => 'Sicheres Arbeiten', 'text' => 'Arbeitsschutz und Ordnung auf der Baustelle sind fester Bestandteil.'],
                ],
            ]],
            ['image_text', [
                'eyebrow' => 'Betonbau', 'heading' => 'Beton- und Stahlbetonarbeiten', 'image' => $this->m('betonage'), 'image_position' => 'left', 'caption' => '', 'button_label' => 'Fundamentarbeiten', 'button_url' => '/leistungen/hoch-und-tiefbau/fundamentarbeiten', 'bg' => 'white',
                'body' => $this->rt('<p>Bodenplatten, Wände und Decken aus Stahlbeton bilden das Rückgrat vieler Gebäude. Wir führen Schalungs-, Bewehrungs- und Betonarbeiten nach den Vorgaben der Tragwerksplanung aus und achten auf eine sorgfältige Nachbehandlung des Betons.</p>'),
            ]],
            $this->cta(),
        ]);

        foreach ([[$garden, $civil], [$demo, $civil], [$shell, $civil], [$shell, $demo], [$civil, $shell], [$civil, $garden], [$garden, $demo]] as [$a, $b]) {
            DB::insert('service_relations', ['service_id' => $a, 'related_id' => $b]);
        }
        return compact('garden', 'civil', 'demo', 'shell');
    }

    // ------------------------------------------------------------------ pages

    private function page(string $key, string $slug, string $title, string $template, string $metaTitle, string $metaDescription, array $blocks): int
    {
        $id = DB::insert('pages', [
            'slug' => $slug, 'title' => $title, 'template' => $template, 'status' => 'published', 'published_at' => null,
            'meta_title' => $metaTitle, 'meta_description' => $metaDescription, 'system_key' => $key, 'updated_by' => $this->adminId,
        ]);
        $this->blocks('page', $id, $blocks);
        \App\Services\Revisions::snapshot($id, 'Startinhalt (Installation)');
        return $id;
    }

    private function pages(): array
    {
        $p = [];
        $p['home'] = $this->page('home', 'startseite', 'Startseite', 'landing',
            'Wert auf Fundament GmbH – Bauunternehmen in der Region Hannover',
            'Hoch- und Tiefbau, Rohbau, Abbruch sowie Garten- und Landschaftsbau aus einer Hand: die Wert auf Fundament GmbH aus Langenhagen.',
            [
                ['hero', [
                    'eyebrow' => 'Bauunternehmen in der Region Hannover', 'title' => 'Bauen auf einem soliden Fundament.',
                    'text' => 'Von der Baugrube bis zur fertigen Außenanlage: Wir realisieren Hoch- und Tiefbau, Rohbau, Abbruch sowie Garten- und Landschaftsbau – zuverlässig, sauber koordiniert und mit klarer Kommunikation.',
                    'image' => $this->m('neubau-baustelle'), 'button1_label' => 'Projekt anfragen', 'button1_url' => '/kontakt#kontaktformular',
                    'button2_label' => 'Unsere Leistungen', 'button2_url' => '/leistungen', 'variant' => 'large',
                ]],
                ['text', [
                    'eyebrow' => 'Wert auf Fundament', 'heading' => 'Werte schaffen, die Bestand haben.', 'layout' => 'split', 'bg' => 'white',
                    'body' => $this->rt('<p>Unser Name ist unser Anspruch: Jedes Bauvorhaben braucht eine tragfähige Grundlage – technisch wie partnerschaftlich. Als Bauunternehmen mit Sitz in Langenhagen und Büro in Garbsen unterstützen wir private und gewerbliche Bauherren bei Projekten im Hoch- und Tiefbau, im Rohbau, beim Abbruch sowie im Garten- und Landschaftsbau.</p><p>Weil diese Leistungen bei uns zusammenlaufen, entstehen weniger Schnittstellen und kürzere Wege. Sie haben einen Ansprechpartner – von den ersten Erdarbeiten bis zur fertigen Fläche.</p>'),
                ]],
                ['service_cards', ['eyebrow' => 'Leistungen', 'heading' => 'Vier Leistungsbereiche. Ein Ansprechpartner.', 'intro' => 'Jeder Bereich für sich – und alle zusammen, wenn Ihr Projekt es verlangt.', 'source' => 'featured', 'services' => [], 'layout' => 'feature', 'link_label' => 'Mehr erfahren', 'bg' => 'sand']],
                ['features', [
                    'eyebrow' => 'Unser Anspruch', 'heading' => 'Worauf Sie sich verlassen können', 'intro' => 'Gute Bauprojekte entstehen durch klare Absprachen und saubere Arbeit. Daran messen wir uns.', 'columns' => '3', 'bg' => 'dark',
                    'items' => [
                        ['icon' => 'message-square', 'title' => 'Klare Kommunikation', 'text' => 'Ein fester Ansprechpartner, verständliche Angebote und offene Information, wenn sich etwas ändert.'],
                        ['icon' => 'clipboard-check', 'title' => 'Sorgfältige Ausführung', 'text' => 'Fachgerechte Arbeit nach Planung und den anerkannten Regeln der Technik – auch dort, wo man sie später nicht mehr sieht.'],
                        ['icon' => 'shield-check', 'title' => 'Sicherheit auf der Baustelle', 'text' => 'Arbeitsschutz, Absicherung und Ordnung sind für uns selbstverständlicher Teil jedes Auftrags.'],
                        ['icon' => 'clock', 'title' => 'Verlässliche Planung', 'text' => 'Realistische Zeitpläne und frühzeitige Abstimmung mit Ihnen und den beteiligten Gewerken.'],
                        ['icon' => 'layers', 'title' => 'Leistungen aus einer Hand', 'text' => 'Tiefbau, Rohbau, Abbruch und Außenanlagen greifen ineinander – ohne unnötige Schnittstellen.'],
                        ['icon' => 'map-pin', 'title' => 'Regional verankert', 'text' => 'Mit Sitz in Langenhagen und Büro in Garbsen sind wir in der Region Hannover zu Hause.'],
                    ],
                ]],
                ['steps', [
                    'eyebrow' => 'Zusammenarbeit', 'heading' => 'So arbeiten wir mit Ihnen', 'intro' => '', 'bg' => 'white',
                    'items' => [
                        ['title' => 'Anfrage', 'text' => 'Sie schildern uns Ihr Vorhaben – per Formular, E-Mail oder Telefon.'],
                        ['title' => 'Besichtigung und Angebot', 'text' => 'Wir sehen uns die Situation vor Ort an und erstellen ein nachvollziehbares Angebot.'],
                        ['title' => 'Ausführung', 'text' => 'Wir setzen die vereinbarten Leistungen um und halten Sie über den Fortschritt auf dem Laufenden.'],
                        ['title' => 'Übergabe', 'text' => 'Gemeinsame Abnahme und Übergabe einer sauberen, fertigen Leistung.'],
                    ],
                ]],
                ['projects', ['eyebrow' => 'Referenzen', 'heading' => 'Ausgewählte Projekte', 'intro' => '', 'limit' => '3', 'featured_only' => '', 'show_filter' => '', 'hide_when_empty' => '1', 'empty_text' => '', 'bg' => 'sand']],
                $this->cta(),
            ]);

        $p['services'] = $this->page('services', 'leistungen', 'Leistungen', 'standard',
            'Unsere Leistungen',
            'Garten- und Landschaftsbau, Hoch- und Tiefbau, Abbruch und Altlastensanierung sowie Rohbau – alle Leistungen der Wert auf Fundament GmbH im Überblick.',
            [
                ['hero', ['eyebrow' => 'Leistungen', 'title' => 'Unsere Leistungen im Überblick', 'text' => 'Vom Rückbau über Erd- und Gründungsarbeiten bis zum Rohbau und zur fertigen Außenanlage: Wählen Sie einen Leistungsbereich, um mehr zu erfahren.', 'image' => 0, 'button1_label' => '', 'button1_url' => '', 'button2_label' => '', 'button2_url' => '', 'variant' => 'compact']],
                ['service_cards', ['eyebrow' => '', 'heading' => '', 'intro' => '', 'source' => 'top', 'services' => [], 'layout' => 'grid', 'link_label' => 'Zum Leistungsbereich', 'bg' => 'white']],
                ['text', [
                    'eyebrow' => 'Koordination', 'heading' => 'Ein Ansprechpartner für mehrere Gewerke', 'layout' => 'split', 'bg' => 'sand',
                    'body' => $this->rt('<p>Viele Bauvorhaben berühren mehr als einen Leistungsbereich: Auf den Abbruch folgen Erdarbeiten, auf die Gründung der Rohbau, zum Schluss die Außenanlage. Wenn diese Schritte aus einer Hand kommen, sinkt der Abstimmungsaufwand – und Übergaben zwischen den Gewerken werden einfacher.</p><p>Gern besprechen wir mit Ihnen, welche Leistungen für Ihr Projekt sinnvoll zusammengefasst werden können.</p>'),
                ]],
                $this->cta(),
            ]);

        $p['projects'] = $this->page('projects', 'referenzen', 'Referenzen', 'standard',
            'Referenzen',
            'Referenzprojekte der Wert auf Fundament GmbH aus den Bereichen Hoch- und Tiefbau, Rohbau, Abbruch sowie Garten- und Landschaftsbau.',
            [
                ['hero', ['eyebrow' => 'Referenzen', 'title' => 'Projekte, die für sich sprechen', 'text' => 'Eine Auswahl unserer Arbeit aus den verschiedenen Leistungsbereichen.', 'image' => 0, 'button1_label' => '', 'button1_url' => '', 'button2_label' => '', 'button2_url' => '', 'variant' => 'compact']],
                ['projects', ['eyebrow' => '', 'heading' => '', 'intro' => '', 'limit' => '0', 'featured_only' => '', 'show_filter' => '1', 'hide_when_empty' => '', 'empty_text' => 'An dieser Stelle stellen wir in Kürze ausgewählte Projekte vor. Gern sprechen wir mit Ihnen persönlich über vergleichbare Vorhaben.', 'bg' => 'white']],
                $this->cta(),
            ]);

        $p['about'] = $this->page('about', 'unternehmen', 'Unternehmen', 'standard',
            'Unternehmen',
            'Die Wert auf Fundament GmbH ist ein Bauunternehmen mit Sitz in Langenhagen und Büro in Garbsen – für Hoch- und Tiefbau, Rohbau, Abbruch und Außenanlagen.',
            [
                ['hero', ['eyebrow' => 'Unternehmen', 'title' => 'Ein Unternehmen, das auf Werte baut.', 'text' => 'Hinter Wert auf Fundament steht ein einfacher Gedanke: Gute Bauwerke brauchen eine solide Grundlage – und Bauherren einen Partner, auf den sie sich verlassen können.', 'image' => $this->m('mauerwerk-handwerk'), 'button1_label' => 'Kontakt aufnehmen', 'button1_url' => '/kontakt#kontaktformular', 'button2_label' => '', 'button2_url' => '', 'variant' => 'medium']],
                ['text', [
                    'eyebrow' => 'Wer wir sind', 'heading' => 'Bauunternehmen aus der Region Hannover', 'layout' => 'split', 'bg' => 'white',
                    'body' => $this->rt('<p>Die Wert auf Fundament GmbH hat ihren Firmensitz in Langenhagen und ihr Büro in Garbsen. Unser Leistungsspektrum umfasst den Hoch- und Tiefbau, den Rohbau, Abbruch- und Rückbauarbeiten sowie den Garten- und Landschaftsbau.</p><p>Diese Bereiche hängen auf der Baustelle eng zusammen. Deshalb denken wir Projekte vom Untergrund her: Wer den Boden, die Gründung und die Abläufe kennt, kann auch die sichtbaren Teile eines Bauwerks dauerhaft gut ausführen.</p>'),
                ]],
                ['features', [
                    'eyebrow' => 'Werte', 'heading' => 'Wofür wir stehen', 'intro' => '', 'columns' => '4', 'bg' => 'sand',
                    'items' => [
                        ['icon' => 'handshake', 'title' => 'Verlässlichkeit', 'text' => 'Absprachen gelten. Wenn sich etwas ändert, erfahren Sie es von uns – rechtzeitig.'],
                        ['icon' => 'clipboard-check', 'title' => 'Qualität', 'text' => 'Wir arbeiten so, dass das Ergebnis auch nach Jahren noch überzeugt.'],
                        ['icon' => 'scale', 'title' => 'Fairness', 'text' => 'Nachvollziehbare Angebote und ein partnerschaftlicher Umgang mit Kunden und Nachunternehmern.'],
                        ['icon' => 'shield-check', 'title' => 'Verantwortung', 'text' => 'Für Sicherheit auf der Baustelle, für die Nachbarschaft und für einen sorgfältigen Umgang mit Ressourcen.'],
                    ],
                ]],
                ['stats', ['eyebrow' => 'Zahlen & Fakten', 'heading' => 'Wert auf Fundament in Zahlen', 'items' => [
                    ['value' => '[BITTE PRÜFEN]', 'label' => 'Gründungsjahr'],
                    ['value' => '[BITTE PRÜFEN]', 'label' => 'Mitarbeitende'],
                ], 'note' => '', 'bg' => 'white'], false, 'Nur mit verifizierten Angaben (z. B. Gründungsjahr, Mitarbeitende, Maschinenpark) befüllen und erst dann einblenden.'],
                ['image_text', [
                    'eyebrow' => 'Arbeitsweise', 'heading' => 'Vom Untergrund her gedacht', 'image' => $this->m('fundament-bewehrung'), 'image_position' => 'left', 'caption' => '', 'button_label' => 'Unsere Leistungen', 'button_url' => '/leistungen', 'bg' => 'white',
                    'body' => $this->rt('<p>Ein Bauwerk ist nur so gut wie seine Grundlage. Deshalb legen wir besonderen Wert auf die Phasen, die später unsichtbar werden: Baugrund, Gründung, Leitungen und Unterbau.</p><p>Sorgfalt in diesen Schritten zahlt sich aus – in weniger Nacharbeiten, in einem reibungsloseren Bauablauf und in Bauwerken, die lange Bestand haben.</p>'),
                ]],
                $this->cta(),
            ]);

        $p['contact'] = $this->page('contact', 'kontakt', 'Kontakt', 'standard',
            'Kontakt',
            'Kontakt zur Wert auf Fundament GmbH: Telefon 0511 999 333 36, E-Mail info@wertauffundament.de, Büro in Garbsen, Firmensitz in Langenhagen.',
            [
                ['hero', ['eyebrow' => 'Kontakt', 'title' => 'Sprechen Sie mit uns über Ihr Vorhaben.', 'text' => 'Ob konkretes Projekt oder erste Frage: Schreiben Sie uns über das Formular, per E-Mail oder rufen Sie uns an.', 'image' => 0, 'button1_label' => '', 'button1_url' => '', 'button2_label' => '', 'button2_url' => '', 'variant' => 'compact']],
                ['contact_form', ['eyebrow' => 'Anfrage', 'heading' => 'Ihre Nachricht an uns', 'intro' => 'Je genauer Sie Ihr Vorhaben beschreiben, desto gezielter können wir Ihnen antworten.', 'aside' => '1']],
                ['faq', ['eyebrow' => 'FAQ', 'heading' => 'Häufige Fragen', 'intro' => 'Antworten auf Fragen, die uns oft gestellt werden.', 'category' => 0, 'bg' => 'white']],
            ]);

        $p['imprint'] = $this->page('imprint', 'impressum', 'Impressum', 'legal', 'Impressum', 'Impressum der Wert auf Fundament GmbH.', [
            ['text', ['eyebrow' => '', 'heading' => 'Impressum', 'layout' => 'narrow', 'bg' => 'white', 'body' => $this->rt('<p>Angaben gemäß § 5 Digitale-Dienste-Gesetz (DDG)</p>')]],
            ['company_legal', ['heading' => 'Anbieter']],
            ['text', ['eyebrow' => '', 'heading' => 'Verbraucherstreitbeilegung', 'layout' => 'narrow', 'bg' => 'white', 'body' => $this->rt('<p>Wir sind nicht bereit und nicht verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p><p>[BITTE PRÜFEN: Aussage durch das Unternehmen bestätigen lassen.]</p>')]],
            ['text', ['eyebrow' => '', 'heading' => 'Haftung für Inhalte', 'layout' => 'narrow', 'bg' => 'white', 'body' => $this->rt('<p>Die Inhalte dieser Website wurden mit Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch keine Gewähr übernehmen. Als Diensteanbieter sind wir für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Wir sind jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen. Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben unberührt.</p>')]],
            ['text', ['eyebrow' => '', 'heading' => 'Haftung für Links', 'layout' => 'narrow', 'bg' => 'white', 'body' => $this->rt('<p>Diese Website kann Links zu externen Websites Dritter enthalten, auf deren Inhalte wir keinen Einfluss haben. Für diese fremden Inhalte ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich. Zum Zeitpunkt der Verlinkung waren keine Rechtsverstöße erkennbar. Werden uns Rechtsverletzungen bekannt, entfernen wir derartige Links umgehend.</p>')]],
            ['text', ['eyebrow' => '', 'heading' => 'Urheberrecht und Bildnachweise', 'layout' => 'narrow', 'bg' => 'white', 'body' => $this->rt('<p>Die Texte, Grafiken und das Logo dieser Website unterliegen dem deutschen Urheberrecht. Eine Vervielfältigung, Bearbeitung oder Verbreitung außerhalb der Grenzen des Urheberrechts bedarf der vorherigen Zustimmung.</p><p>Einzelne Fotos stammen von Wikimedia Commons und werden unter den jeweils angegebenen freien Lizenzen (z. B. CC BY-SA) verwendet. Urheber und Lizenz sind direkt am jeweiligen Bild angegeben.</p><p>[BITTE PRÜFEN: Abschnitt nach Austausch der Übergangsbilder durch eigene Fotos anpassen.]</p>')]],
        ]);

        $p['privacy'] = $this->page('privacy', 'datenschutz', 'Datenschutzerklärung', 'legal', 'Datenschutzerklärung', 'Informationen zur Verarbeitung personenbezogener Daten auf der Website der Wert auf Fundament GmbH.', $this->privacyBlocks());

        return $p;
    }

    private function privacyBlocks(): array
    {
        $t = fn (string $heading, string $html) => ['text', ['eyebrow' => '', 'heading' => $heading, 'layout' => 'narrow', 'bg' => 'white', 'body' => $this->rt($html)]];
        return [
            $t('Datenschutzerklärung', '<p>Mit dieser Datenschutzerklärung informieren wir Sie darüber, welche personenbezogenen Daten beim Besuch dieser Website und bei einer Kontaktaufnahme verarbeitet werden, zu welchen Zwecken dies geschieht und welche Rechte Ihnen zustehen.</p>'),
            ['notice', ['heading' => 'Entwurf – Prüfung erforderlich', 'tone' => 'warning', 'body' => $this->rt('<p>[BITTE PRÜFEN: Diese Datenschutzerklärung ist ein auf die technische Umsetzung dieser Website abgestimmter Entwurf. Sie muss vor der Veröffentlichung vom Unternehmen bzw. einer fachkundigen Person geprüft und vervollständigt werden. Diesen Hinweisblock anschließend löschen.]</p>')]],
            $t('1. Verantwortlicher', '<p>Verantwortlich für die Datenverarbeitung auf dieser Website ist:</p><p>Wert auf Fundament GmbH<br>Robert-Koch-Str. 67<br>30853 Langenhagen<br>Telefon: 0511 999 333 36<br>E-Mail: <a href="mailto:info@wertauffundament.de">info@wertauffundament.de</a></p><p>Vertreten durch die Geschäftsführung: [BITTE PRÜFEN: Namen der Geschäftsführer ergänzen]</p>'),
            $t('2. Datenschutzbeauftragter', '<p>[BITTE PRÜFEN: Falls ein Datenschutzbeauftragter benannt ist oder benannt werden muss (in der Regel, wenn mindestens 20 Personen ständig mit der automatisierten Verarbeitung personenbezogener Daten beschäftigt sind), hier Name bzw. Kontaktdaten angeben. Andernfalls diesen Abschnitt entfernen.]</p>'),
            $t('3. Hosting und Server-Logfiles', '<p>Diese Website wird bei einem externen Dienstleister gehostet: [BITTE PRÜFEN: Name und Anschrift des Hosting-Anbieters, z. B. Hostinger International Ltd., sowie Serverstandort ergänzen]. Mit dem Hosting-Anbieter besteht ein Vertrag zur Auftragsverarbeitung gemäß Art. 28 DSGVO. [BITTE PRÜFEN: Abschluss des Auftragsverarbeitungsvertrags bestätigen.]</p><p>Beim Aufruf der Website verarbeitet der Webserver automatisch Informationen, die Ihr Browser übermittelt, sogenannte Server-Logfiles. Dazu gehören in der Regel:</p><ul><li>IP-Adresse des anfragenden Geräts</li><li>Datum und Uhrzeit des Zugriffs</li><li>aufgerufene Seite bzw. Datei und übertragene Datenmenge</li><li>Website, von der aus der Zugriff erfolgt (Referrer)</li><li>verwendeter Browser und Betriebssystem</li><li>Statusmeldung (z. B. erfolgreicher Abruf)</li></ul><p>Die Verarbeitung erfolgt, um die Website auszuliefern sowie ihre Sicherheit und Stabilität zu gewährleisten. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO; unser berechtigtes Interesse liegt in einem sicheren und störungsfreien Betrieb der Website. Die Logfiles werden nach [BITTE PRÜFEN: Speicherdauer laut Hosting-Anbieter] gelöscht, sofern sie nicht zur Aufklärung eines Sicherheitsvorfalls benötigt werden.</p>'),
            $t('4. Cookies, Schriftarten und eingebundene Dienste', '<p>Die öffentlichen Seiten dieser Website setzen keine Cookies und verwenden keine Analyse-, Tracking- oder Werbedienste. Schriftarten und Symbole werden von unserem eigenen Server geladen; es findet dabei keine Verbindung zu Servern Dritter (z. B. Google Fonts) statt. Karten- oder Videodienste sind nicht eingebunden.</p><p>Nur im passwortgeschützten Verwaltungsbereich wird ein technisch notwendiges Sitzungs-Cookie gesetzt, das ausschließlich für die Anmeldung berechtigter Mitarbeiter erforderlich ist und mit dem Schließen des Browsers bzw. der Abmeldung gelöscht wird (§ 25 Abs. 2 Nr. 2 TDDDG, Art. 6 Abs. 1 lit. f DSGVO).</p><p>Verlinkt diese Website auf externe Angebote (z. B. einen Routenplaner oder soziale Netzwerke), werden Daten an den jeweiligen Anbieter erst übermittelt, wenn Sie den Link anklicken.</p>'),
            $t('5. Kontaktformular', '<p>Wenn Sie uns über das Kontaktformular eine Anfrage senden, verarbeiten wir die von Ihnen eingegebenen Daten: Name, E-Mail-Adresse, Ihre Nachricht sowie freiwillig Telefonnummer, Ort und Art des Vorhabens und Ihre bevorzugte Kontaktart. Zusätzlich speichern wir den Zeitpunkt der Anfrage.</p><p>Die Daten verwenden wir ausschließlich, um Ihre Anfrage zu bearbeiten und Ihnen zu antworten. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO, soweit Ihre Anfrage auf den Abschluss oder die Durchführung eines Vertrags gerichtet ist, im Übrigen Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an der Beantwortung von Anfragen).</p><p>Die Anfragen werden in der Datenbank dieser Website gespeichert und sind nur für berechtigte Mitarbeiter im geschützten Verwaltungsbereich einsehbar. Sofern eingerichtet, erhalten wir zusätzlich eine Benachrichtigung per E-Mail mit dem Inhalt Ihrer Anfrage.</p><p>Zum Schutz vor Missbrauch und Spam wird Ihre IP-Adresse kurzzeitig in nicht direkt lesbarer, pseudonymisierter Form (als Hash-Wert) verarbeitet, um die Anzahl der Anfragen je Absender zu begrenzen; diese Einträge werden regelmäßig, spätestens nach einigen Tagen, gelöscht.</p><p>Wir löschen Ihre Anfrage, sobald sie abschließend bearbeitet ist und keine gesetzlichen Aufbewahrungspflichten entgegenstehen. Abgeschlossene Anfragen werden spätestens nach [BITTE PRÜFEN: Frist, z. B. 12 Monaten] gelöscht. Führt Ihre Anfrage zu einem Vertrag, gelten die handels- und steuerrechtlichen Aufbewahrungsfristen.</p>'),
            $t('6. Kontakt per E-Mail oder Telefon', '<p>Wenn Sie uns per E-Mail oder telefonisch kontaktieren, verarbeiten wir Ihre Angaben (z. B. Name, Kontaktdaten, Inhalt der Anfrage) zur Bearbeitung Ihres Anliegens. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b bzw. lit. f DSGVO. Für den E-Mail-Verkehr nutzen wir [BITTE PRÜFEN: E-Mail-Anbieter angeben].</p>'),
            $t('7. Empfänger und Übermittlung in Drittländer', '<p>Ihre Daten werden grundsätzlich nicht an Dritte weitergegeben. Empfänger können technische Dienstleister sein, die uns im Rahmen einer Auftragsverarbeitung unterstützen (Hosting, E-Mail). Eine Übermittlung in Länder außerhalb der Europäischen Union bzw. des Europäischen Wirtschaftsraums findet nicht statt. [BITTE PRÜFEN: Angaben zu Dienstleistern und Serverstandorten bestätigen.]</p>'),
            $t('8. Ihre Rechte', '<p>Sie haben im Rahmen der gesetzlichen Vorgaben das Recht auf:</p><ul><li>Auskunft über Ihre bei uns gespeicherten Daten (Art. 15 DSGVO)</li><li>Berichtigung unrichtiger Daten (Art. 16 DSGVO)</li><li>Löschung (Art. 17 DSGVO)</li><li>Einschränkung der Verarbeitung (Art. 18 DSGVO)</li><li>Datenübertragbarkeit (Art. 20 DSGVO)</li></ul><p><strong>Widerspruchsrecht:</strong> Soweit wir Daten auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO verarbeiten, können Sie dieser Verarbeitung aus Gründen, die sich aus Ihrer besonderen Situation ergeben, jederzeit widersprechen (Art. 21 DSGVO).</p><p>Zur Ausübung Ihrer Rechte genügt eine formlose Nachricht an die oben genannten Kontaktdaten.</p>'),
            $t('9. Beschwerderecht bei einer Aufsichtsbehörde', '<p>Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu beschweren (Art. 77 DSGVO). Für uns zuständig ist:</p><p>Die Landesbeauftragte für den Datenschutz Niedersachsen<br>Prinzenstraße 5<br>30159 Hannover</p><p>[BITTE PRÜFEN: Bezeichnung und Anschrift der Aufsichtsbehörde vor Veröffentlichung kontrollieren.]</p>'),
            $t('10. Verschlüsselung', '<p>Diese Website nutzt aus Sicherheitsgründen eine SSL- bzw. TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennen Sie an „https://“ in der Adresszeile Ihres Browsers.</p>'),
            $t('11. Aktualität dieser Datenschutzerklärung', '<p>Wir passen diese Datenschutzerklärung an, wenn sich die Website oder die rechtlichen Anforderungen ändern. Es gilt die jeweils hier veröffentlichte Fassung.</p>'),
        ];
    }

    // ------------------------------------------------------------------ navigation

    private function navigation(array $p, array $s): void
    {
        $nav = function (string $menu, string $label, string $type, ?int $pageId, ?int $serviceId, int $sort, int $auto = 0, ?int $parent = null, string $url = '') {
            return DB::insert('nav_items', [
                'menu' => $menu, 'parent_id' => $parent, 'label' => $label, 'link_type' => $type, 'page_id' => $pageId, 'service_id' => $serviceId,
                'url' => $url, 'auto_children' => $auto, 'sort_order' => $sort,
            ]);
        };
        $nav('main', 'Leistungen', 'page', $p['services'], null, 10, 1);
        $nav('main', 'Referenzen', 'page', $p['projects'], null, 30);
        $nav('main', 'Unternehmen', 'page', $p['about'], null, 40);
        $nav('main', 'Kontakt', 'page', $p['contact'], null, 50);

        $nav('footer', 'Unternehmen', 'page', $p['about'], null, 10);
        $nav('footer', 'Referenzen', 'page', $p['projects'], null, 20);
        $nav('footer', 'Kontakt', 'page', $p['contact'], null, 30);

        $nav('legal', 'Impressum', 'page', $p['imprint'], null, 10);
        $nav('legal', 'Datenschutz', 'page', $p['privacy'], null, 20);
    }
}
