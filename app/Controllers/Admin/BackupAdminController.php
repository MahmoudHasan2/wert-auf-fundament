<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\ErrorHandler;
use App\Core\HttpException;
use App\Core\Response;
use App\Services\Backup;

final class BackupAdminController extends AdminController
{
    public function index(): Response
    {
        return $this->view('backups', ['backups' => Backup::list(), 'zip' => class_exists(\ZipArchive::class)], 'Backups');
    }

    public function database(): Response
    {
        try {
            $name = Backup::dumpDatabase();
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            return $this->redirect('/admin/backups', 'error', 'Das Datenbank-Backup ist fehlgeschlagen. Details stehen im Fehlerprotokoll.');
        }
        $this->audit('backup.created', 'backup', null, 'Datenbank-Backup ' . $name . ' erstellt');
        return $this->redirect('/admin/backups', 'success', 'Datenbank-Backup erstellt. Laden Sie es herunter und bewahren Sie es zusätzlich außerhalb des Servers auf.');
    }

    public function media(): Response
    {
        try {
            $name = Backup::archiveMedia();
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            return $this->redirect('/admin/backups', 'error', $e instanceof \RuntimeException ? $e->getMessage() : 'Das Medien-Archiv konnte nicht erstellt werden.');
        }
        $this->audit('backup.created', 'backup', null, 'Medien-Archiv ' . $name . ' erstellt');
        return $this->redirect('/admin/backups', 'success', 'Medien-Archiv erstellt.');
    }

    public function download(string $name): Response
    {
        $path = Backup::path($name) ?? throw HttpException::notFound();
        $this->audit('backup.downloaded', 'backup', null, 'Backup ' . $name . ' heruntergeladen');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        readfile($path);
        exit;
    }

    public function delete(): Response
    {
        $name = $this->request->str('name', 100);
        if (!Backup::delete($name)) {
            return $this->redirect('/admin/backups', 'error', 'Backup nicht gefunden.');
        }
        $this->audit('backup.deleted', 'backup', null, 'Backup ' . $name . ' gelöscht');
        return $this->redirect('/admin/backups', 'success', 'Backup gelöscht.');
    }

    /** Restore requires the current password AND typing the confirmation word. */
    public function restore(): Response
    {
        $name = $this->request->str('name', 100);
        $row = DB::row('SELECT password_hash FROM admins WHERE id = ?', [$this->user['id']]);
        if (!$row || !password_verify((string) $this->request->input('password', ''), $row['password_hash'])) {
            return $this->redirect('/admin/backups', 'error', 'Passwort nicht korrekt. Die Wiederherstellung wurde nicht ausgeführt.');
        }
        if ($this->request->str('confirm', 30) !== 'WIEDERHERSTELLEN') {
            return $this->redirect('/admin/backups', 'error', 'Bitte zur Bestätigung WIEDERHERSTELLEN eingeben.');
        }
        if (!Backup::path($name) || !str_starts_with($name, 'db-')) {
            return $this->redirect('/admin/backups', 'error', 'Backup nicht gefunden.');
        }
        try {
            $safety = Backup::dumpDatabase();
            Backup::restoreDatabase($name);
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            return $this->redirect('/admin/backups', 'error', 'Die Wiederherstellung ist fehlgeschlagen: ' . ($e instanceof \RuntimeException ? $e->getMessage() : 'siehe Fehlerprotokoll') . ' Ein Sicherungs-Backup des vorherigen Stands liegt vor.');
        }
        $this->audit('backup.restored', 'backup', null, 'Datenbank aus ' . $name . ' wiederhergestellt (vorheriger Stand: ' . $safety . ')');
        return $this->redirect('/admin/backups', 'success', 'Die Datenbank wurde wiederhergestellt. Der vorherige Stand wurde als ' . $safety . ' gesichert. Bitte melden Sie sich ggf. neu an.');
    }
}
