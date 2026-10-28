<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Response;
use App\Services\Settings;

final class EnquiryAdminController extends AdminController
{
    public const STATUSES = ['new' => 'Neu', 'in_progress' => 'In Bearbeitung', 'answered' => 'Beantwortet', 'closed' => 'Abgeschlossen'];

    private function retentionDays(): int
    {
        return max(30, (int) Settings::get('enquiry_retention_days', '365'));
    }

    private function filters(): array
    {
        $status = $this->request->q('status');
        $q = $this->request->q('q');
        $where = ' WHERE 1=1';
        $params = [];
        if (isset(self::STATUSES[$status])) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where .= ' AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR message LIKE ? OR project_type LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
        }
        return [$where, $params, $status, $q];
    }

    public function index(): Response
    {
        [$where, $params, $status, $q] = $this->filters();
        $pager = $this->paginator((int) DB::value('SELECT COUNT(*) FROM enquiries' . $where, $params), 25);
        $rows = DB::all('SELECT id, name, email, project_type, status, mail_sent, created_at FROM enquiries' . $where . ' ORDER BY created_at DESC LIMIT ' . $pager->perPage . ' OFFSET ' . $pager->offset, $params);
        $cutoff = date('Y-m-d H:i:s', time() - $this->retentionDays() * 86400);
        return $this->view('enquiries/index', [
            'rows' => $rows, 'pager' => $pager, 'status' => $status, 'q' => $q,
            'counts' => DB::all('SELECT status, COUNT(*) AS n FROM enquiries GROUP BY status'),
            'expired' => (int) DB::value("SELECT COUNT(*) FROM enquiries WHERE status IN ('answered', 'closed') AND updated_at < ?", [$cutoff]),
            'days' => $this->retentionDays(),
        ], 'Kontaktanfragen');
    }

    public function show(int $id): Response
    {
        $row = $this->findOr404(DB::row('SELECT * FROM enquiries WHERE id = ?', [$id]));
        return $this->view('enquiries/show', [
            'e' => $row,
            'notes' => DB::all('SELECT n.*, a.name AS admin_name FROM enquiry_notes n LEFT JOIN admins a ON a.id = n.admin_id WHERE n.enquiry_id = ? ORDER BY n.created_at', [$id]),
        ], 'Anfrage #' . $id);
    }

    public function status(int $id): Response
    {
        $row = $this->findOr404(DB::row('SELECT * FROM enquiries WHERE id = ?', [$id]));
        $status = $this->request->str('status', 12);
        if (!isset(self::STATUSES[$status])) {
            return $this->redirect('/admin/enquiries/' . $id, 'error', 'Ungültiger Status.');
        }
        DB::update('enquiries', ['status' => $status, 'handled_at' => in_array($status, ['answered', 'closed'], true) ? ($row['handled_at'] ?? now()) : null], 'id = :id', ['id' => $id]);
        $this->audit('enquiry.status', 'enquiry', $id, 'Anfrage #' . $id . ' → ' . self::STATUSES[$status]);
        return $this->redirect('/admin/enquiries/' . $id, 'success', 'Status auf „' . self::STATUSES[$status] . '“ gesetzt.');
    }

    public function note(int $id): Response
    {
        $this->findOr404(DB::row('SELECT id FROM enquiries WHERE id = ?', [$id]));
        $note = $this->request->str('note', 3000);
        if ($note === '') {
            return $this->redirect('/admin/enquiries/' . $id, 'error', 'Die Notiz ist leer.');
        }
        DB::insert('enquiry_notes', ['enquiry_id' => $id, 'admin_id' => $this->user['id'], 'note' => $note, 'created_at' => now()]);
        DB::exec("UPDATE enquiries SET status = IF(status = 'new', 'in_progress', status), updated_at = ? WHERE id = ?", [now(), $id]);
        return $this->redirect('/admin/enquiries/' . $id . '#notizen', 'success', 'Notiz gespeichert.');
    }

    public function delete(int $id): Response
    {
        $this->findOr404(DB::row('SELECT id FROM enquiries WHERE id = ?', [$id]));
        DB::exec('DELETE FROM enquiries WHERE id = ?', [$id]);
        $this->audit('enquiry.deleted', 'enquiry', $id, 'Anfrage #' . $id . ' gelöscht');
        return $this->redirect('/admin/enquiries', 'success', 'Die Anfrage wurde endgültig gelöscht.');
    }

    /** Deletes answered/closed enquiries older than the configured retention period. */
    public function purge(): Response
    {
        $cutoff = date('Y-m-d H:i:s', time() - $this->retentionDays() * 86400);
        $n = DB::exec("DELETE FROM enquiries WHERE status IN ('answered', 'closed') AND updated_at < ?", [$cutoff]);
        $this->audit('enquiry.purged', 'enquiry', null, $n . ' abgeschlossene Anfragen nach Ablauf der Aufbewahrungsfrist gelöscht');
        return $this->redirect('/admin/enquiries', 'success', $n . ' Anfrage(n) gelöscht.');
    }

    /** CSV export (administrators only). Cells are protected against spreadsheet formula injection. */
    public function export(): Response
    {
        [$where, $params] = $this->filters();
        $rows = DB::all('SELECT id, created_at, status, name, email, phone, project_type, location, preferred_contact, message FROM enquiries' . $where . ' ORDER BY created_at DESC', $params);
        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Nr.', 'Eingang', 'Status', 'Name', 'E-Mail', 'Telefon', 'Art des Vorhabens', 'Ort', 'Kontaktwunsch', 'Nachricht'], ';');
        foreach ($rows as $r) {
            $r['status'] = self::STATUSES[$r['status']] ?? $r['status'];
            fputcsv($out, array_map(fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v, array_values($r)), ';');
        }
        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);
        $this->audit('enquiry.export', 'enquiry', null, count($rows) . ' Anfragen als CSV exportiert');
        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="anfragen-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
