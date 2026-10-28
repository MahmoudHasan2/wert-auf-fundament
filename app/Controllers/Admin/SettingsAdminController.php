<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Response;
use App\Services\FieldForm;
use App\Services\Settings;

final class SettingsAdminController extends AdminController
{
    public function edit(): Response
    {
        return $this->view('settings', ['schema' => Settings::schema(), 'values' => Settings::all()], 'Unternehmenseinstellungen');
    }

    public function update(): Response
    {
        $errors = [];
        $values = [];
        foreach (Settings::schema() as $fields) {
            foreach ($fields as $key => $f) {
                $raw = $this->request->post[$key] ?? '';
                $raw = is_string($raw) ? trim(str_replace("\0", '', $raw)) : '';
                switch ($f['type']) {
                    case 'email':
                        if ($raw !== '' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) {
                            $errors[$key] = 'Bitte eine gültige E-Mail-Adresse angeben.';
                        }
                        break;
                    case 'url':
                        if ($raw !== '' && (!preg_match('#^https://#i', $raw) || !filter_var($raw, FILTER_VALIDATE_URL))) {
                            $errors[$key] = 'Bitte eine vollständige https://-Adresse angeben.';
                        }
                        break;
                    case 'link':
                        if ($raw !== '' && !FieldForm::validLink($raw)) {
                            $errors[$key] = 'Ungültiges Linkziel.';
                        }
                        break;
                    case 'media':
                        $raw = (int) $raw && DB::value('SELECT id FROM media WHERE id = ?', [(int) $raw]) ? (string) (int) $raw : '';
                        break;
                    case 'bool':
                        $raw = $raw !== '' ? '1' : '0';
                        break;
                    case 'number':
                        $raw = (string) max(30, min(3650, (int) $raw ?: 365));
                        break;
                    case 'select':
                        $raw = array_key_exists($raw, $f['options']) ? $raw : (string) array_key_first($f['options']);
                        break;
                }
                if (!empty($f['required']) && $raw === '') {
                    $errors[$key] = 'Pflichtangabe.';
                }
                $values[$key] = mb_substr($raw, 0, $f['type'] === 'textarea' ? 4000 : 500);
            }
        }
        if ($errors) {
            return $this->invalid('/admin/settings', $errors);
        }
        $before = Settings::all();
        Settings::setMany($values);
        $changed = array_keys(array_filter($values, fn ($v, $k) => ($before[$k] ?? '') !== $v, ARRAY_FILTER_USE_BOTH));
        $this->audit('settings.updated', 'settings', null, $changed ? 'Einstellungen geändert: ' . mb_substr(implode(', ', $changed), 0, 200) : 'Einstellungen gespeichert (ohne Änderungen)');
        return $this->redirect('/admin/settings', 'success', 'Die Einstellungen wurden gespeichert und sind sofort auf der Website sichtbar.');
    }
}
