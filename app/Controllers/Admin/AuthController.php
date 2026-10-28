<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\ErrorHandler;
use App\Core\Response;
use App\Core\Session;
use App\Security\Auth;
use App\Security\RateLimiter;
use App\Services\Mailer;

final class AuthController extends AdminController
{
    public function loginForm(): Response
    {
        if ($this->user) {
            return $this->redirect('/admin');
        }
        return $this->view('login', ['mailEnabled' => Mailer::isConfigured()], 'Anmeldung');
    }

    public function login(): Response
    {
        $result = Auth::attempt($this->request, $this->request->str('email', 190), (string) $this->request->input('password', ''));
        if (!$result['ok']) {
            Session::flashInput(['email' => $this->request->str('email', 190)]);
            return $this->redirect('/admin/login', 'error', $result['error']);
        }
        $intended = (string) Session::get('_intended', '/admin');
        Session::forget('_intended');
        return $this->redirect(str_starts_with($intended, '/admin') ? $intended : '/admin');
    }

    public function logout(): Response
    {
        $this->audit('logout', 'admin', $this->user['id'] ?? null, 'Abmeldung');
        Auth::logout();
        Session::start($this->request);
        return $this->redirect('/admin/login', 'success', 'Sie wurden abgemeldet.');
    }

    public function account(): Response
    {
        return $this->view('account', [], 'Mein Konto');
    }

    public function changePassword(): Response
    {
        $r = $this->request;
        $row = DB::row('SELECT password_hash FROM admins WHERE id = ?', [$this->user['id']]);
        if (!$row || !password_verify((string) $r->input('current_password', ''), $row['password_hash'])) {
            return $this->invalid('/admin/konto', ['current_password' => 'Das aktuelle Passwort ist nicht korrekt.']);
        }
        $errors = Auth::validatePassword((string) $r->input('password', ''), (string) $r->input('password_confirm', ''), $this->user['email'], $this->user['name']);
        if ($errors) {
            return $this->invalid('/admin/konto', ['password' => implode(' ', $errors)]);
        }
        DB::update('admins', ['password_hash' => Auth::hash((string) $r->input('password')), 'password_changed_at' => now()], 'id = :id', ['id' => $this->user['id']]);
        Auth::refreshStamp();
        $this->audit('password.changed', 'admin', (int) $this->user['id'], 'Eigenes Passwort geändert');
        return $this->redirect('/admin/konto', 'success', 'Ihr Passwort wurde geändert. Andere Sitzungen wurden abgemeldet.');
    }

    // ---------------------------------------------------------------- password reset (only with working mail)

    public function forgotForm(): Response
    {
        return $this->view('forgot', ['mailEnabled' => Mailer::isConfigured()], 'Passwort vergessen');
    }

    public function forgot(): Response
    {
        if (!Mailer::isConfigured()) {
            return $this->redirect('/admin/passwort-vergessen');
        }
        $generic = 'Falls ein Konto mit dieser E-Mail-Adresse existiert, wurde ein Link zum Zurücksetzen versendet. Der Link ist 60 Minuten gültig.';
        if (RateLimiter::tooMany('pw_reset', $this->request->ip(), 5, 3600)) {
            return $this->redirect('/admin/passwort-vergessen', 'success', $generic);
        }
        RateLimiter::hit('pw_reset', $this->request->ip(), 3600);
        $email = mb_strtolower($this->request->str('email', 190));
        $admin = DB::row('SELECT id, name, email FROM admins WHERE email = ? AND is_active = 1', [$email]);
        if ($admin) {
            $token = bin2hex(random_bytes(32));
            DB::exec('DELETE FROM password_resets WHERE admin_id = ?', [$admin['id']]);
            DB::insert('password_resets', ['admin_id' => $admin['id'], 'token_hash' => hash('sha256', $token), 'expires_at' => date('Y-m-d H:i:s', time() + 3600)]);
            try {
                Mailer::send($admin['email'], 'Passwort zurücksetzen – ' . setting('company_name'),
                    "Hallo {$admin['name']},\n\nfür Ihr Konto wurde das Zurücksetzen des Passworts angefordert. Über den folgenden Link können Sie innerhalb von 60 Minuten ein neues Passwort festlegen:\n\n"
                    . abs_url('/admin/passwort-zuruecksetzen?token=' . $token) . "\n\nFalls Sie dies nicht angefordert haben, ignorieren Sie diese E-Mail.\n");
            } catch (\Throwable $e) {
                ErrorHandler::log($e, 'WARNING');
            }
            $this->audit('password.reset_requested', 'admin', (int) $admin['id'], 'Passwort-Zurücksetzen angefordert');
        }
        return $this->redirect('/admin/passwort-vergessen', 'success', $generic);
    }

    private function resetRow(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return DB::row('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?', [hash('sha256', $token), now()]);
    }

    public function resetForm(): Response
    {
        $token = $this->request->q('token');
        return $this->view('reset', ['token' => $token, 'valid' => $this->resetRow($token) !== null], 'Neues Passwort');
    }

    public function reset(): Response
    {
        $token = $this->request->str('token', 64);
        $row = $this->resetRow($token);
        if (!$row) {
            return $this->redirect('/admin/passwort-vergessen', 'error', 'Der Link ist ungültig oder abgelaufen. Bitte fordern Sie einen neuen an.');
        }
        $admin = DB::row('SELECT * FROM admins WHERE id = ?', [$row['admin_id']]);
        $errors = Auth::validatePassword((string) $this->request->input('password', ''), (string) $this->request->input('password_confirm', ''), (string) $admin['email'], (string) $admin['name']);
        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            return Response::redirect('/admin/passwort-zuruecksetzen?token=' . $token);
        }
        DB::transaction(function () use ($row) {
            DB::update('admins', ['password_hash' => Auth::hash((string) $this->request->input('password')), 'password_changed_at' => now()], 'id = :id', ['id' => $row['admin_id']]);
            DB::exec('UPDATE password_resets SET used_at = ? WHERE id = ?', [now(), $row['id']]);
        });
        $this->audit('password.reset', 'admin', (int) $row['admin_id'], 'Passwort per E-Mail-Link zurückgesetzt');
        return $this->redirect('/admin/login', 'success', 'Ihr Passwort wurde geändert. Sie können sich jetzt anmelden.');
    }
}
