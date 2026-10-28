<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\DB;
use App\Core\ErrorHandler;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\View;
use App\Repositories\PageRepository;
use App\Security\FormToken;
use App\Security\RateLimiter;
use App\Services\Mailer;
use App\Services\Settings;

/**
 * Public contact form. No session/cookie: protection via signed form token (with minimum fill time),
 * honeypot field, IP-based rate limit (IP stored only as HMAC hash) and strict server-side validation.
 */
final class ContactController extends SiteController
{
    public const FORM = 'contact';

    public function submit(): Response
    {
        $req = $this->request;
        $page = PageRepository::find($req->int('_page'));
        if (!$page || !PageRepository::isLive($page)) {
            $page = PageRepository::system('contact');
        }
        if (!$page) {
            throw HttpException::notFound();
        }
        $back = PageRepository::path($page);

        $old = [
            'name' => $req->str('name', 120),
            'email' => $req->str('email', 190),
            'phone' => $req->str('phone', 40),
            'project_type' => $req->str('project_type', 120),
            'preferred_contact' => $req->str('preferred_contact', 12),
            'location' => $req->str('location', 120),
            'message' => $req->str('message', 5000),
            'privacy' => $req->bool('privacy'),
        ];

        // Honeypot / too-fast submissions: pretend success, store nothing.
        $token = FormToken::check(self::FORM, $req->str('_token', 200));
        if ($req->str('website', 200) !== '' || $token === 'too_fast') {
            return Response::redirect($back . '?gesendet=1#kontaktformular');
        }

        $errors = [];
        if ($token !== 'ok') {
            $errors['_form'] = 'Das Formular ist abgelaufen. Bitte prüfen Sie Ihre Angaben und senden Sie es erneut ab.';
        }
        if (mb_strlen($old['name']) < 2) {
            $errors['name'] = 'Bitte geben Sie Ihren Namen an.';
        }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
        }
        if ($old['phone'] !== '' && !preg_match('/^[0-9 +()\/-]{5,40}$/', $old['phone'])) {
            $errors['phone'] = 'Bitte geben Sie eine gültige Telefonnummer an (nur Ziffern, Leerzeichen, + ( ) / -).';
        }
        $types = Settings::lines('contact_project_types');
        if ($old['project_type'] !== '' && !in_array($old['project_type'], $types, true)) {
            $old['project_type'] = '';
        }
        if (!in_array($old['preferred_contact'], ['', 'email', 'phone'], true)) {
            $old['preferred_contact'] = '';
        }
        if ($old['preferred_contact'] === 'phone' && $old['phone'] === '') {
            $errors['phone'] = 'Für einen Rückruf benötigen wir Ihre Telefonnummer.';
        }
        if (mb_strlen($old['message']) < 10) {
            $errors['message'] = 'Bitte beschreiben Sie Ihr Anliegen kurz (mindestens 10 Zeichen).';
        } elseif (preg_match_all('#https?://#i', $old['message']) > 3) {
            $errors['message'] = 'Bitte fügen Sie höchstens drei Links ein.';
        }
        if (!$old['privacy']) {
            $errors['privacy'] = 'Bitte bestätigen Sie, dass Sie die Datenschutzhinweise zur Kenntnis genommen haben.';
        }

        if (!$errors && RateLimiter::tooMany('contact', $req->ip(), 5, 3600)) {
            $errors['_form'] = 'Sie haben in kurzer Zeit mehrere Anfragen gesendet. Bitte versuchen Sie es später erneut oder rufen Sie uns an.';
        }

        if ($errors) {
            View::share('contactForm', ['errors' => $errors, 'old' => $old]);
            return self::renderPage($page, $req, false, 422);
        }

        RateLimiter::hit('contact', $req->ip(), 3600);
        $id = DB::insert('enquiries', [
            'name' => $old['name'],
            'email' => mb_strtolower($old['email']),
            'phone' => $old['phone'],
            'project_type' => $old['project_type'],
            'preferred_contact' => $old['preferred_contact'],
            'location' => $old['location'],
            'message' => $old['message'],
            'status' => 'new',
            'privacy_ack_at' => now(),
            'created_at' => now(),
        ]);

        if (Mailer::isConfigured()) {
            $to = Settings::get('contact_recipient') ?: Settings::get('email');
            $body = "Neue Anfrage über die Website (Nr. $id)\n\n"
                . "Name: {$old['name']}\nE-Mail: {$old['email']}\nTelefon: " . ($old['phone'] ?: '–') . "\n"
                . 'Art des Vorhabens: ' . ($old['project_type'] ?: '–') . "\n"
                . 'Ort des Vorhabens: ' . ($old['location'] ?: '–') . "\n"
                . 'Bevorzugter Kontakt: ' . ['email' => 'E-Mail', 'phone' => 'Telefon', '' => '–'][$old['preferred_contact']] . "\n\n"
                . "Nachricht:\n{$old['message']}\n\n"
                . 'Die Anfrage ist im Verwaltungsbereich gespeichert: ' . abs_url('/admin/enquiries/' . $id) . "\n";
            try {
                Mailer::send($to, 'Neue Website-Anfrage: ' . mb_substr($old['name'], 0, 60), $body, $old['email']);
                DB::exec('UPDATE enquiries SET mail_sent = 1 WHERE id = ?', [$id]);
            } catch (\Throwable $e) {
                ErrorHandler::log($e, 'WARNING');
            }
        }
        return Response::redirect($back . '?gesendet=1#kontaktformular');
    }
}
