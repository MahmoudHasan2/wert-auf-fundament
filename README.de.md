# Wert auf Fundament GmbH – Website & CMS

[🇬🇧 English](README.md) · 🇩🇪 Deutsch

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL%20%2F%20MariaDB-utf8mb4-4479A1?logo=mysql&logoColor=white)
![Ohne Composer](https://img.shields.io/badge/ohne%20Composer%20%C2%B7%20ohne%20Node-reines%20PHP-2ea44f)
![Hostinger](https://img.shields.io/badge/Hosting-Hostinger%20Shared-673DE6?logo=hostinger&logoColor=white)

Mehrseitige Unternehmenswebsite mit vollständigem Content-Management-System (deutsche Oberfläche), gebaut für **Hostinger Shared Hosting**.
Reines PHP 8.2+ und MySQL/MariaDB – **kein Composer, kein Node.js, kein SSH und kein Cron nötig**.

- Öffentliche Website: Startseite, Leistungsübersicht, 4 Leistungsbereiche mit Unterseiten, Referenzen, Unternehmen, Kontakt, Impressum, Datenschutz und beliebig viele weitere CMS-Seiten.
- Admin-Bereich (`/admin`): Seiten & Inhaltsblöcke, Startseite, Leistungen, Referenzen/Galerien, Medienbibliothek, Navigation, FAQ, wiederverwendbare Blöcke, Kontaktanfragen, Unternehmenseinstellungen, Rechtstexte mit Versionsverlauf, Benutzer & Rollen, Audit-Log, Backups, Systemstatus, Checkliste vor dem Livegang.

---

## 1. Voraussetzungen

| | |
|---|---|
| PHP | 8.2 oder neuer (8.3 empfohlen) |
| PHP-Erweiterungen | `pdo_mysql`, `mbstring`, `gd` (mit WebP), `fileinfo`, `dom`, `openssl`; optional: `zip` (Medien-Backups), `exif` (automatisches Drehen von Handyfotos), `zlib` (komprimierte Backups) |
| Datenbank | MySQL 5.7+/8.x oder MariaDB 10.4+ (utf8mb4) |
| Webserver | Apache oder LiteSpeed mit `.htaccess` + `mod_rewrite` (Standard bei Hostinger) |

All das ist bei Hostinger Standard. Prüfen unter **hPanel → Erweitert → PHP-Konfiguration**.

## 2. Verzeichnisstruktur

```
app/            Anwendungscode (Controller, Services, Views)          – privat
bin/            Installer für die Kommandozeile                        – privat
config/         app.php (Standardwerte), env.example.php (Vorlage)     – privat
database/       Migrationen (SQL) + Startinhalte und Startbilder        – privat
docs/           zusätzliche Dokumentation                               – privat
public/         DAS EINZIGE ÖFFENTLICHE VERZEICHNIS (index.php, Assets, Uploads)
storage/        Logs, Sessions, Backups, installed.lock                 – privat
tests/          automatisierte Tests                                    – privat
```

Jedes private Verzeichnis enthält eine `.htaccess` mit `Deny all`. `public/uploads/.htaccess` erlaubt nur erzeugte Bilddateien und verhindert die Ausführung von Skripten.

## 3. Deployment bei Hostinger (ohne SSH)

### Schnellstart (mit Apache getestet; dieselben `.htaccess`-Regeln gelten für LiteSpeed bei Hostinger)
1. hPanel → PHP-Konfiguration: **PHP 8.3** wählen. hPanel → Datenbanken: eine MySQL-Datenbank + Benutzer anlegen.
2. Den ganzen Projektordner zippen, die ZIP-Datei mit dem Dateimanager nach `public_html` hochladen und dort entpacken (Hostingers `default.php` löschen, falls vorhanden). Darauf achten, dass die versteckten `.htaccess`-Dateien dabei sind.
3. **Sofort** `https://deine-domain/admin/install` öffnen:
   - Schritt 1: Datenbankdaten eingeben → der Installer testet die Verbindung und schreibt `env.php` automatisch nach `domains/deine-domain/waf-private/` (außerhalb von `public_html`).
   - Schritt 2: Administratorkonto anlegen → Tabellen, Inhalte und Fotos werden installiert (≈ 30 s).
4. Unter `/admin` anmelden und die Checkliste im Dashboard abarbeiten.

Die ausführliche bzw. manuelle Variante folgt unten.

### 3.1 Datenbank anlegen
hPanel → **Datenbanken → MySQL-Datenbanken**: Datenbank und Benutzer anlegen (z. B. `u123456789_waf`), Name, Benutzer und Passwort notieren. Der Benutzer braucht SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES und LOCK TABLES, und zwar nur für diese Datenbank.

### 3.2 Dateien hochladen – empfohlene Struktur
Das Projekt (per Dateimanager als ZIP oder per FTP) **neben** `public_html` hochladen, nicht hinein:

```
/home/u123456789/domains/wertauffundament.de/
├── waf/                 ← ganzes Projekt (app, config, public, storage …)
├── waf-private/
│   └── env.php          ← Konfiguration mit Zugangsdaten (außerhalb jedes Web-Roots)
└── public_html/         ← Document Root
```

Danach den Document Root auf `waf/public` zeigen lassen:
- **Variante A (bevorzugt):** hPanel → Websites → *deine Domain* → **Erweitert → Website-Root / Document Root ändern** (falls verfügbar) → `domains/wertauffundament.de/waf/public`.
- **Variante B:** Lässt sich der Document Root nicht ändern, das **ganze Projekt nach `public_html`** hochladen. Die mitgelieferte `.htaccess` im Projektstamm leitet jede Anfrage nach `/public` und sperrt `app/`, `config/`, `storage/` usw. In diesem Fall `env.php` nach `domains/wertauffundament.de/waf-private/env.php` legen (eine Ebene über `public_html`) – die App sucht dort automatisch.

`tests/` und `docs/` **nicht** auf den Produktivserver hochladen, wenn du sie nicht brauchst (sie sind ohnehin geschützt).

### 3.3 Konfiguration (`env.php`)
`config/env.example.php` nach `../waf-private/env.php` kopieren (relativ zum Projektordner) und ausfüllen:

- `app.url` – z. B. `https://www.wertauffundament.de` (ohne abschließenden Schrägstrich)
- `app.key` – 64 zufällige Hex-Zeichen. Lokal erzeugen: `php -r "echo bin2hex(random_bytes(32));"` oder einen beliebigen Passwortgenerator nutzen (Buchstaben/Ziffern, ≥ 32 Zeichen)
- `db.*` – Werte aus 3.1 (`host` ist bei Hostinger `localhost`)
- `install.token` – eine lange zufällige Zeichenkette, nur einmal für den Installer nötig
- `mail.*` – siehe Abschnitt 5 (kann vorerst `none` bleiben)

Suchreihenfolge: Umgebungsvariable `WAF_ENV_FILE` → `../waf-private/env.php` → `config/env.php`. `env.php` niemals committen.

### 3.4 Dateirechte
Verzeichnisse `755`, Dateien `644`. Folgende müssen für PHP beschreibbar sein (bei Hostinger Standard): `storage/`, `storage/logs`, `storage/cache`, `storage/backups`, `public/uploads`.

### 3.5 HTTPS
hPanel → **Sicherheit → SSL**: das kostenlose Zertifikat installieren. Dann `app.url` auf `https://…` setzen und `app.force_https => true`. Optional die HTTPS-Weiterleitung in `public/.htaccess` einkommentieren. `app.hsts` erst aktivieren, wenn HTTPS für alle Subdomains zuverlässig funktioniert.

### 3.6 Installer ausführen
`https://deine-domain/admin/install` öffnen, Installations-Token, Name, E-Mail und ein Passwort eingeben (≥ 12 Zeichen, darf weder Name noch E-Mail enthalten). Der Installer:
1. legt alle Tabellen an (`database/migrations/*.sql`),
2. importiert die deutschen Startinhalte und 24 lizenzierte Platzhalterfotos,
3. legt den ersten Administrator an,
4. schreibt `storage/installed.lock` – **danach ist der Installer dauerhaft deaktiviert**.

Anschließend **`install.token` aus env.php entfernen**. Es gibt kein Standardpasswort und keine öffentliche Registrierung.

Alternative mit SSH: `php bin/install.php --name="Max Mustermann" --email=admin@…` (das Passwort wird abgefragt).

## 4. Erste Schritte nach der Installation

Das Dashboard zeigt eine **Checkliste vor dem Livegang**. Arbeite sie ab:

1. **Unternehmenseinstellungen:** Geschäftsführer, Registergericht + Registernummer (Pflichtangaben im Impressum, derzeit leer), Hosting-Anbieter und Bürozeiten ergänzen.
2. **Datenschutzerklärung / Impressum:** alle Markierungen `[BITTE PRÜFEN …]` ersetzen; beide Texte vom Unternehmen oder von einer Fachperson für deutsches Datenschutz-/Medienrecht prüfen lassen, danach den Hinweisblock zum Entwurf löschen.
3. **Aussagen bestätigen**, die in der Checkliste stehen (ausgeblendete Blöcke: eigene Kräne, Gartenpflege, belegte Zahlen; Leistungen mit Hinweisen: Garten-Unterleistungen, Verfahren der Baugrundverbesserung, Umfang/Qualifikationen bei der Altlastensanierung).
4. **Fotos:** die 24 Platzhalter von Wikimedia Commons (markiert mit „Platzhalter“) über *Medien → Bild → Bilddatei ersetzen* durch eigene Fotos ersetzen. Bis dahin *Bildnachweise anzeigen* eingeschaltet lassen (die Namensnennung ist eine Lizenzpflicht, CC BY-SA).
5. **Referenzen:** nur echte Projekte eintragen (*Referenzen*). Solange keine existieren, zeigt die Website einen neutralen Text.
6. **E-Mail:** SMTP einrichten (Abschnitt 5) und eine Testnachricht senden (*Systemstatus*).
7. **Livegang:** *Unternehmenseinstellungen → SEO → „Suchmaschinen dürfen die Website indexieren“*. Bis dahin sind alle Seiten `noindex`, und robots.txt verbietet das Crawling.

## 5. E-Mail / SMTP

Ohne Mail-Konfiguration werden Anfragen **sicher im Dashboard gespeichert**; es wird keine Benachrichtigung verschickt, und „Passwort vergessen“ ist deaktiviert.

Hostinger: unter hPanel → E-Mails ein Postfach anlegen (z. B. `website@wertauffundament.de`), dann in env.php:

```php
'mail' => [
    'transport' => 'smtp', 'host' => 'smtp.hostinger.com', 'port' => 465, 'encryption' => 'ssl',
    'username' => 'website@wertauffundament.de', 'password' => '…',
    'from_address' => 'website@wertauffundament.de', 'from_name' => 'Website Wert auf Fundament',
],
```
Testen über *Systemstatus → Testnachricht senden*. Empfänger der Benachrichtigungen über Anfragen: *Unternehmenseinstellungen → Kontaktformular*.

## 6. Backups

- **Im Admin-Bereich (Backups):** Datenbank-Dump (`.sql.gz`) und ZIP aller Bilder; gespeichert in `storage/backups` (nicht über das Web erreichbar), nur von Administratoren herunterladbar. Die Wiederherstellung verlangt dein Passwort + die Eingabe von `WIEDERHERSTELLEN`; vorher wird automatisch ein Sicherheits-Backup des aktuellen Stands erstellt.
- **Hostinger:** hPanel → Dateien → Backups (automatisch, je nach Tarif täglich/wöchentlich).
- **Empfohlene Routine (monatlich und vor jedem Update):** im Admin ein DB-Backup + Medienarchiv erstellen, beides herunterladen und außerhalb des Servers aufbewahren; zusätzlich eine Kopie von `env.php` sichern.
- **Manuell (ohne Admin-Bereich):** die Datenbank in phpMyAdmin exportieren (hPanel → Datenbanken → phpMyAdmin → Exportieren) und `public/uploads` per Dateimanager herunterladen.
- Shared Hosting kann ohne Cron keine geplanten Aufgaben zuverlässig ausführen, deshalb sind die Backups in der App bewusst manuell.

## 7. Die Anwendung aktualisieren
1. Ein DB-Backup + Medienarchiv erstellen und herunterladen.
2. Den neuen Code hochladen (`app/`, `public/assets/`, `database/`, `config/app.php` überschreiben; `env.php`, `storage/` und `public/uploads/` **niemals** überschreiben).
3. *Systemstatus* öffnen: Stehen Datenbank-Updates an, auf *Jetzt aktualisieren* klicken (führt neue Dateien aus `database/migrations` aus).

## 8. Sicherheit im Überblick
- Überall PDO Prepared Statements; kontextabhängiges Escaping in allen Templates; HTML-Sanitizer mit Allow-List für formatierten Text.
- Zentrale Durchsetzung (`App\Core\Kernel`): Jede Admin-Route deklariert eine Berechtigung; jede Admin-Anfrage außer GET braucht ein CSRF-Token.
- Rollen: *Administrator* (alles) und *Redakteur* (Inhalte, Medien, Anfragen – keine Rechtstexte, Einstellungen, Benutzer, Backups, kein System).
- Sessions nur unter `/admin` (HttpOnly, SameSite=Lax, Secure bei HTTPS, Strict Mode, neue Session-ID beim Login, Leerlauf-/Absolut-Timeouts; ungültig bei Passwortänderung/Deaktivierung).
- Login-Drosselung (5 Versuche pro Konto+IP / 20 pro IP in 15 Min.), allgemeine Fehlermeldungen, bcrypt über `password_hash()`.
- Uploads: Endung + MIME + `getimagesize` + vollständige Neukodierung mit GD (Metadaten entfernt), zufällige Dateinamen, kein SVG, keine Skriptausführung in `uploads/`.
- Die öffentliche Website setzt **keine Cookies**; das Kontaktformular nutzt ein HMAC-signiertes Token (Mindestausfüllzeit), einen Honeypot und ein IP-Ratenlimit (nur als HMAC-Hash gespeichert).
- Sicherheits-Header: strenge CSP (keine Inline-Skripte/-Styles), `nosniff`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, optional HSTS. Admin-Seiten: `noindex`, `no-store`.
- Fehler werden in `storage/logs` protokolliert; Besucher sehen nie Stacktraces (`app.debug = false` lassen).
- Audit-Log für Logins, Inhaltsänderungen, Veröffentlichungen, Löschungen, Einstellungen, Benutzer und Backups (IP gekürzt, keine Passwörter oder Inhalte von Anfragen).

## 9. Hinweise zum Datenschutz (technische Fakten für die Datenschutzerklärung)
- Keine Analytics, kein Tracking, keine Werbung, keine externen Schriften, CDNs, Karten oder eingebetteten Videos. Schriften/Icons liegen auf dem eigenen Server.
- Cookie: nur `waf_admin` (Session, nur im Admin-Bereich). Für die aktuelle Umsetzung ist kein Cookie-Banner nötig; werden später Tracking oder Einbettungen ergänzt, muss vorher eine Einwilligungslösung umgesetzt werden.
- Daten aus dem Kontaktformular werden in der Tabelle `enquiries` gespeichert; Löschung über *Kontaktanfragen* (einzeln oder gesammelt nach der in den Einstellungen festgelegten Aufbewahrungsfrist, Standard 365 Tage).
- Einträge des Ratenlimits enthalten nur HMAC-Hashes von IP-Adressen und werden automatisch bereinigt.

## 10. Tests

```bash
php tests/run.php                       # Unit-Tests
# Integrationstests gegen eine FRISCHE Testinstallation (legt Daten an/löscht sie – nie auf dem Produktivsystem):
WAF_ENV_FILE=/pfad/test-env.php WAF_TEST_BASE=http://127.0.0.1:8001 \
WAF_TEST_EMAIL=… WAF_TEST_PASSWORD=… php tests/run.php
```
Lokale Entwicklung: `php -S 127.0.0.1:8000 -t public public/index.php`, mit `WAF_ENV_FILE` auf eine Entwicklungs-env.php zeigend.

## 11. Fehlerbehebung
| Problem | Lösung |
|---|---|
| „Unsere Website wird gerade eingerichtet“ | Noch nicht installiert → `/admin/install` öffnen. |
| Installer: „Voraussetzungen nicht erfüllt“ | Die aufgelisteten Punkte lesen: Ort/Werte von env.php, DB-Zugangsdaten, beschreibbares `storage/`, PHP-Erweiterungen. |
| Fehler 500 / leere Seite | `storage/logs/app-JJJJ-MM-TT.log` prüfen (Dateimanager). Nur wenn nötig vorübergehend `app.debug => true` setzen, danach wieder false. |
| 404 für jede Seite außer der Startseite | `mod_rewrite`/`.htaccess` nicht aktiv, oder der Document Root zeigt nicht auf `public/` (siehe 3.2). |
| Styles/Bilder fehlen | Falsche `app.url` (muss zur Domain passen, inkl. https und ggf. Unterordner). |
| Upload scheitert mit „zu groß“ | `upload_max_filesize`/`post_max_size` in hPanel → PHP-Konfiguration erhöhen (Limit der App: 10 MB, `uploads.max_bytes`). |
| Keine E-Mails zu Anfragen | Mail nicht eingerichtet oder SMTP-Daten falsch → *Systemstatus → Testnachricht*; das Log enthält die Antwort des SMTP-Servers. |
| Nach Fehlversuchen beim Login gesperrt | 15 Minuten warten. Ein anderer Administrator kann das Passwort unter *Benutzer* zurücksetzen. |
| Alle Admin-Passwörter verloren | In phpMyAdmin bei einem Admin `password_hash` auf die Ausgabe von `php -r "echo password_hash('NEUES-PASSWORT', PASSWORD_DEFAULT);"` setzen. |
