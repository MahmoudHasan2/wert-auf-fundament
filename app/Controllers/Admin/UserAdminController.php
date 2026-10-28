<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Response;
use App\Security\Auth;

final class UserAdminController extends AdminController
{
    public function index(): Response
    {
        return $this->view('users/index', ['users' => DB::all('SELECT id, name, email, role, is_active, last_login_at, created_at FROM admins ORDER BY name')], 'Benutzerverwaltung');
    }

    public function create(): Response
    {
        return $this->view('users/form', ['u' => null], 'Neuer Benutzer');
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate(null);
        $pw = (string) $this->request->input('password', '');
        $pwErrors = Auth::validatePassword($pw, (string) $this->request->input('password_confirm', ''), $data['email'], $data['name']);
        if ($pwErrors) {
            $errors['password'] = implode(' ', $pwErrors);
        }
        if ($errors) {
            return $this->invalid('/admin/users/create', $errors);
        }
        $id = DB::insert('admins', $data + ['password_hash' => Auth::hash($pw), 'password_changed_at' => now()]);
        $this->audit('user.created', 'admin', $id, 'Benutzer „' . $data['name'] . '“ (' . $data['role'] . ') angelegt');
        return $this->redirect('/admin/users', 'success', 'Benutzer angelegt. Teilen Sie das Passwort auf sicherem Weg mit.');
    }

    public function edit(int $id): Response
    {
        $u = $this->findOr404(DB::row('SELECT id, name, email, role, is_active, last_login_at FROM admins WHERE id = ?', [$id]));
        return $this->view('users/form', ['u' => $u], 'Benutzer: ' . $u['name']);
    }

    public function update(int $id): Response
    {
        $u = $this->findOr404(DB::row('SELECT * FROM admins WHERE id = ?', [$id]));
        [$data, $errors] = $this->validate($u);
        $pw = (string) $this->request->input('password', '');
        if ($pw !== '') {
            $pwErrors = Auth::validatePassword($pw, (string) $this->request->input('password_confirm', ''), $data['email'], $data['name']);
            if ($pwErrors) {
                $errors['password'] = implode(' ', $pwErrors);
            }
        }
        if ($id === (int) $this->user['id'] && ($data['role'] !== 'admin' || !$data['is_active'])) {
            $errors['role'] = 'Sie können sich nicht selbst die Administratorrechte entziehen oder sich deaktivieren.';
        }
        if ($u['role'] === 'admin' && ($data['role'] !== 'admin' || !$data['is_active']) && $this->activeAdmins() <= 1) {
            $errors['role'] = 'Es muss mindestens ein aktiver Administrator bestehen bleiben.';
        }
        if ($errors) {
            return $this->invalid('/admin/users/' . $id, $errors);
        }
        if ($pw !== '') {
            $data['password_hash'] = Auth::hash($pw);
            $data['password_changed_at'] = now(); // ends all sessions of that user
        }
        DB::update('admins', $data, 'id = :id', ['id' => $id]);
        if ($pw !== '' && $id === (int) $this->user['id']) {
            Auth::refreshStamp();
        }
        $this->audit('user.updated', 'admin', $id, 'Benutzer „' . $data['name'] . '“ bearbeitet' . ($pw !== '' ? ' (Passwort neu gesetzt)' : ''));
        return $this->redirect('/admin/users', 'success', 'Benutzer gespeichert.');
    }

    public function delete(int $id): Response
    {
        $u = $this->findOr404(DB::row('SELECT * FROM admins WHERE id = ?', [$id]));
        if ($id === (int) $this->user['id']) {
            return $this->redirect('/admin/users', 'error', 'Sie können Ihr eigenes Konto nicht löschen.');
        }
        if ($u['role'] === 'admin' && $u['is_active'] && $this->activeAdmins() <= 1) {
            return $this->redirect('/admin/users', 'error', 'Der letzte aktive Administrator kann nicht gelöscht werden.');
        }
        DB::exec('DELETE FROM admins WHERE id = ?', [$id]);
        $this->audit('user.deleted', 'admin', $id, 'Benutzer „' . $u['name'] . '“ gelöscht');
        return $this->redirect('/admin/users', 'success', 'Benutzer gelöscht.');
    }

    private function activeAdmins(): int
    {
        return (int) DB::value("SELECT COUNT(*) FROM admins WHERE role = 'admin' AND is_active = 1");
    }

    /** @return array{0:array,1:array} */
    private function validate(?array $u): array
    {
        $r = $this->request;
        $errors = [];
        $name = $r->str('name', 120);
        $email = mb_strtolower($r->str('email', 190));
        if (mb_strlen($name) < 2) {
            $errors['name'] = 'Bitte einen Namen angeben.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Bitte eine gültige E-Mail-Adresse angeben.';
        } elseif (DB::value('SELECT id FROM admins WHERE email = ? AND id <> ?', [$email, (int) ($u['id'] ?? 0)])) {
            $errors['email'] = 'Diese E-Mail-Adresse wird bereits verwendet.';
        }
        $role = $r->str('role', 20);
        if (!isset(Auth::ROLES[$role])) {
            $role = 'editor';
        }
        return [['name' => $name, 'email' => $email, 'role' => $role, 'is_active' => $r->bool('is_active') ? 1 : 0], $errors];
    }
}
