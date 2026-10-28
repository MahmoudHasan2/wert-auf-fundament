<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Registry of all content block types. The admin form for each block is generated from
 * the field definitions here; the public markup lives in app/Views/blocks/{type}.php.
 *
 * Field types: text, textarea, richtext, link, media, select, bool, number, repeater,
 *              services, gallery, faq_category, library
 */
final class BlockTypes
{
    public const ICONS = [
        'hard-hat' => 'Schutzhelm', 'shovel' => 'Schaufel', 'construction' => 'Baustelle', 'building-2' => 'Gebäude',
        'brick-wall' => 'Mauerwerk', 'layers' => 'Schichten', 'ruler' => 'Lineal', 'trees' => 'Bäume', 'sprout' => 'Pflanze',
        'leaf' => 'Blatt', 'flower-2' => 'Blume', 'fence' => 'Zaun', 'route' => 'Weg', 'cable' => 'Kabel', 'droplets' => 'Wasser',
        'truck' => 'Lkw', 'recycle' => 'Recycling', 'trash-2' => 'Entsorgung', 'hammer' => 'Hammer', 'drill' => 'Bohrer',
        'pickaxe' => 'Spitzhacke', 'mountain' => 'Erdreich', 'container' => 'Container', 'factory' => 'Industrie',
        'shield-check' => 'Sicherheit', 'clipboard-check' => 'Prüfung', 'clipboard-list' => 'Planung', 'handshake' => 'Partnerschaft',
        'clock' => 'Termintreue', 'users' => 'Team', 'scale' => 'Fairness', 'target' => 'Ziel', 'gauge' => 'Effizienz',
        'message-square' => 'Kommunikation', 'circle-check' => 'Haken', 'wrench' => 'Werkzeug', 'house' => 'Haus', 'blocks' => 'Bausteine',
        'waves' => 'Wellen', 'zap' => 'Energie', 'milestone' => 'Meilenstein', 'package' => 'Material', 'tractor' => 'Maschine',
    ];

    private const BG = ['name' => 'bg', 'type' => 'select', 'label' => 'Hintergrund', 'options' => ['white' => 'Weiß', 'sand' => 'Hell (warmes Grau)', 'dark' => 'Dunkel (Anthrazit)'], 'default' => 'white'];
    private const EYEBROW = ['name' => 'eyebrow', 'type' => 'text', 'label' => 'Dachzeile (kleine Überschrift)'];

    public static function all(): array
    {
        $bg = self::BG;
        $eyebrow = self::EYEBROW;
        $icons = ['' => '– kein Symbol –'] + self::ICONS;
        return [
            'hero' => [
                'label' => 'Bühne / Hero', 'icon' => 'panel-top',
                'desc' => 'Großer Einstiegsbereich mit Hintergrundbild, Überschrift und Buttons.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'title', 'type' => 'text', 'label' => 'Hauptüberschrift', 'required' => true],
                    ['name' => 'text', 'type' => 'textarea', 'label' => 'Einleitungstext'],
                    ['name' => 'image', 'type' => 'media', 'label' => 'Hintergrundbild'],
                    ['name' => 'button1_label', 'type' => 'text', 'label' => 'Button 1 – Beschriftung'],
                    ['name' => 'button1_url', 'type' => 'link', 'label' => 'Button 1 – Ziel'],
                    ['name' => 'button2_label', 'type' => 'text', 'label' => 'Button 2 – Beschriftung'],
                    ['name' => 'button2_url', 'type' => 'link', 'label' => 'Button 2 – Ziel'],
                    ['name' => 'variant', 'type' => 'select', 'label' => 'Größe', 'options' => ['large' => 'Groß (Startseite)', 'medium' => 'Mittel', 'compact' => 'Kompakt (Unterseiten, ohne Bild möglich)'], 'default' => 'medium'],
                ],
            ],
            'text' => [
                'label' => 'Textabschnitt', 'icon' => 'file-text',
                'desc' => 'Überschrift mit formatiertem Fließtext.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'body', 'type' => 'richtext', 'label' => 'Text'],
                    ['name' => 'layout', 'type' => 'select', 'label' => 'Layout', 'options' => ['narrow' => 'Schmale Textspalte', 'split' => 'Überschrift links, Text rechts', 'wide' => 'Breit'], 'default' => 'narrow'],
                    $bg,
                ],
            ],
            'image_text' => [
                'label' => 'Bild mit Text', 'icon' => 'image',
                'desc' => 'Bild und Text nebeneinander, optional mit Button.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'body', 'type' => 'richtext', 'label' => 'Text'],
                    ['name' => 'image', 'type' => 'media', 'label' => 'Bild'],
                    ['name' => 'caption', 'type' => 'text', 'label' => 'Bildunterschrift (optional)'],
                    ['name' => 'image_position', 'type' => 'select', 'label' => 'Bildposition', 'options' => ['left' => 'Links', 'right' => 'Rechts'], 'default' => 'right'],
                    ['name' => 'button_label', 'type' => 'text', 'label' => 'Button – Beschriftung'],
                    ['name' => 'button_url', 'type' => 'link', 'label' => 'Button – Ziel'],
                    $bg,
                ],
            ],
            'checklist' => [
                'label' => 'Leistungsliste', 'icon' => 'clipboard-list',
                'desc' => 'Aufzählung von Leistungen oder Punkten mit kurzer Beschreibung, optional mit Bild.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'intro', 'type' => 'textarea', 'label' => 'Einleitung'],
                    ['name' => 'image', 'type' => 'media', 'label' => 'Bild (optional)'],
                    ['name' => 'items', 'type' => 'repeater', 'label' => 'Punkte', 'max' => 20, 'fields' => [
                        ['name' => 'title', 'type' => 'text', 'label' => 'Titel'],
                        ['name' => 'text', 'type' => 'textarea', 'label' => 'Beschreibung'],
                    ]],
                    $bg,
                ],
            ],
            'features' => [
                'label' => 'Merkmale / Stärken', 'icon' => 'layout-grid',
                'desc' => 'Raster aus Kacheln mit Symbol, Titel und Text.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'intro', 'type' => 'textarea', 'label' => 'Einleitung'],
                    ['name' => 'items', 'type' => 'repeater', 'label' => 'Kacheln', 'max' => 12, 'fields' => [
                        ['name' => 'icon', 'type' => 'select', 'label' => 'Symbol', 'options' => $icons],
                        ['name' => 'title', 'type' => 'text', 'label' => 'Titel'],
                        ['name' => 'text', 'type' => 'textarea', 'label' => 'Text'],
                    ]],
                    ['name' => 'columns', 'type' => 'select', 'label' => 'Spalten', 'options' => ['2' => '2', '3' => '3', '4' => '4'], 'default' => '3'],
                    $bg,
                ],
            ],
            'steps' => [
                'label' => 'Ablauf / Arbeitsweise', 'icon' => 'milestone',
                'desc' => 'Nummerierte Schritte, z. B. „So arbeiten wir“.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'intro', 'type' => 'textarea', 'label' => 'Einleitung'],
                    ['name' => 'items', 'type' => 'repeater', 'label' => 'Schritte', 'max' => 8, 'fields' => [
                        ['name' => 'title', 'type' => 'text', 'label' => 'Titel'],
                        ['name' => 'text', 'type' => 'textarea', 'label' => 'Text'],
                    ]],
                    $bg,
                ],
            ],
            'service_cards' => [
                'label' => 'Leistungskarten', 'icon' => 'blocks',
                'desc' => 'Zeigt Leistungen aus der Leistungsverwaltung als Karten an.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'intro', 'type' => 'textarea', 'label' => 'Einleitung'],
                    ['name' => 'source', 'type' => 'select', 'label' => 'Welche Leistungen?', 'options' => [
                        'top' => 'Alle Hauptleistungen', 'featured' => 'Auf der Startseite hervorgehobene', 'children' => 'Unterleistungen der aktuellen Leistung', 'manual' => 'Manuelle Auswahl',
                    ], 'default' => 'top'],
                    ['name' => 'services', 'type' => 'services', 'label' => 'Manuelle Auswahl', 'help' => 'Nur bei „Manuelle Auswahl“.'],
                    ['name' => 'layout', 'type' => 'select', 'label' => 'Darstellung', 'options' => ['grid' => 'Bildkarten', 'feature' => 'Bildkarten, erste Karte groß', 'compact' => 'Kompakte Liste ohne Bilder'], 'default' => 'grid'],
                    ['name' => 'link_label', 'type' => 'text', 'label' => 'Link-Text auf Karten', 'default' => 'Mehr erfahren'],
                    $bg,
                ],
            ],
            'gallery' => [
                'label' => 'Bildergalerie', 'icon' => 'images',
                'desc' => 'Zeigt eine Galerie aus der Galerieverwaltung.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'intro', 'type' => 'textarea', 'label' => 'Einleitung'],
                    ['name' => 'gallery', 'type' => 'gallery', 'label' => 'Galerie', 'required' => true],
                    ['name' => 'layout', 'type' => 'select', 'label' => 'Layout', 'options' => ['grid' => 'Gleichmäßiges Raster', 'mosaic' => 'Mosaik (erstes Bild groß)'], 'default' => 'grid'],
                    $bg,
                ],
            ],
            'projects' => [
                'label' => 'Referenzen', 'icon' => 'building-2',
                'desc' => 'Zeigt veröffentlichte Referenzprojekte. Ohne Projekte wird der Leertext angezeigt oder der Block ausgeblendet.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'intro', 'type' => 'textarea', 'label' => 'Einleitung'],
                    ['name' => 'limit', 'type' => 'number', 'label' => 'Maximale Anzahl (0 = alle)', 'min' => 0, 'max' => 60, 'default' => '0'],
                    ['name' => 'featured_only', 'type' => 'bool', 'label' => 'Nur hervorgehobene Projekte'],
                    ['name' => 'show_filter', 'type' => 'bool', 'label' => 'Filter nach Leistungsbereich anzeigen'],
                    ['name' => 'hide_when_empty', 'type' => 'bool', 'label' => 'Block ausblenden, solange keine Projekte veröffentlicht sind'],
                    ['name' => 'empty_text', 'type' => 'textarea', 'label' => 'Text, solange keine Projekte veröffentlicht sind'],
                    $bg,
                ],
            ],
            'stats' => [
                'label' => 'Kennzahlen', 'icon' => 'gauge',
                'desc' => 'Nur verifizierte Zahlen verwenden! Keine geschätzten oder erfundenen Werte veröffentlichen.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'items', 'type' => 'repeater', 'label' => 'Kennzahlen', 'max' => 6, 'fields' => [
                        ['name' => 'value', 'type' => 'text', 'label' => 'Wert (z. B. „2024“)'],
                        ['name' => 'label', 'type' => 'text', 'label' => 'Beschriftung'],
                    ]],
                    ['name' => 'note', 'type' => 'text', 'label' => 'Fußnote / Quelle'],
                    $bg,
                ],
            ],
            'faq' => [
                'label' => 'Häufige Fragen (FAQ)', 'icon' => 'circle-help',
                'desc' => 'Zeigt veröffentlichte Fragen aus der FAQ-Verwaltung.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'intro', 'type' => 'textarea', 'label' => 'Einleitung'],
                    ['name' => 'category', 'type' => 'faq_category', 'label' => 'Kategorie'],
                    $bg,
                ],
            ],
            'notice' => [
                'label' => 'Hinweiskasten', 'icon' => 'info',
                'desc' => 'Hervorgehobener Hinweis, z. B. zu rechtlichen Voraussetzungen.',
                'fields' => [
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'body', 'type' => 'richtext', 'label' => 'Text'],
                    ['name' => 'tone', 'type' => 'select', 'label' => 'Art', 'options' => ['info' => 'Information', 'warning' => 'Wichtiger Hinweis'], 'default' => 'info'],
                ],
            ],
            'cta' => [
                'label' => 'Handlungsaufforderung (CTA)', 'icon' => 'send',
                'desc' => 'Auffälliger Abschnitt mit Aufforderung zur Kontaktaufnahme.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift', 'required' => true],
                    ['name' => 'text', 'type' => 'textarea', 'label' => 'Text'],
                    ['name' => 'button1_label', 'type' => 'text', 'label' => 'Button 1 – Beschriftung'],
                    ['name' => 'button1_url', 'type' => 'link', 'label' => 'Button 1 – Ziel'],
                    ['name' => 'button2_label', 'type' => 'text', 'label' => 'Button 2 – Beschriftung'],
                    ['name' => 'button2_url', 'type' => 'link', 'label' => 'Button 2 – Ziel'],
                    ['name' => 'show_phone', 'type' => 'bool', 'label' => 'Telefonnummer anzeigen'],
                    ['name' => 'image', 'type' => 'media', 'label' => 'Hintergrundbild (optional)'],
                    ['name' => 'variant', 'type' => 'select', 'label' => 'Farbe', 'options' => ['dark' => 'Anthrazit', 'red' => 'Markenrot', 'light' => 'Hell'], 'default' => 'dark'],
                ],
            ],
            'contact_details' => [
                'label' => 'Kontaktdaten', 'icon' => 'map-pin',
                'desc' => 'Adressen, Telefon und E-Mail aus den Unternehmenseinstellungen.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'text', 'type' => 'textarea', 'label' => 'Text'],
                    ['name' => 'show_registered', 'type' => 'bool', 'label' => 'Firmensitz anzeigen', 'default' => '1'],
                    ['name' => 'show_office', 'type' => 'bool', 'label' => 'Büroadresse anzeigen', 'default' => '1'],
                    ['name' => 'show_hours', 'type' => 'bool', 'label' => 'Bürozeiten anzeigen (falls hinterlegt)', 'default' => '1'],
                    $bg,
                ],
            ],
            'contact_form' => [
                'label' => 'Kontaktformular', 'icon' => 'mail',
                'desc' => 'Anfrageformular. Optionen und Texte unter „Unternehmenseinstellungen → Kontaktformular“.',
                'fields' => [
                    $eyebrow,
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift'],
                    ['name' => 'intro', 'type' => 'textarea', 'label' => 'Einleitung'],
                    ['name' => 'aside', 'type' => 'bool', 'label' => 'Kontaktdaten neben dem Formular anzeigen', 'default' => '1'],
                ],
            ],
            'company_legal' => [
                'label' => 'Impressumsangaben', 'icon' => 'scale',
                'desc' => 'Gibt die Pflichtangaben aus den Unternehmenseinstellungen strukturiert aus. Fehlende Angaben werden nicht angezeigt, aber in der Checkliste gemeldet.',
                'fields' => [
                    ['name' => 'heading', 'type' => 'text', 'label' => 'Überschrift', 'default' => 'Angaben gemäß § 5 DDG'],
                ],
            ],
            'library_ref' => [
                'label' => 'Wiederverwendbarer Baustein', 'icon' => 'copy',
                'desc' => 'Bindet einen Baustein aus der Bibliothek ein. Änderungen am Baustein wirken überall.',
                'fields' => [
                    ['name' => 'library', 'type' => 'library', 'label' => 'Baustein', 'required' => true],
                ],
            ],
        ];
    }

    public static function get(string $type): ?array
    {
        return self::all()[$type] ?? null;
    }

    /** Short human readable summary for block lists in the admin. */
    public static function summary(string $type, array $data): string
    {
        foreach (['title', 'heading', 'eyebrow', 'text', 'intro'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) {
                return str_excerpt($data[$k], 90);
            }
        }
        if ($type === 'library_ref' && !empty($data['library'])) {
            $name = \App\Core\DB::value('SELECT library_name FROM content_blocks WHERE id = ?', [(int) $data['library']]);
            return 'Baustein: ' . ($name ?: '(gelöscht)');
        }
        if (!empty($data['body'])) {
            return str_excerpt((string) $data['body'], 90);
        }
        return '';
    }
}
