<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\ErrorHandler;
use App\Core\Response;
use App\Services\Media;
use App\Services\MediaException;

final class MediaAdminController extends AdminController
{
    private function query(string $q, string $filter): array
    {
        $where = ' WHERE 1=1';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (original_name LIKE ? OR alt LIKE ? OR caption LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        if ($filter === 'placeholder') {
            $where .= ' AND is_placeholder = 1';
        } elseif ($filter === 'noalt') {
            $where .= " AND alt = ''";
        }
        return [$where, $params];
    }

    public function index(): Response
    {
        $q = $this->request->q('q');
        $filter = $this->request->q('filter');
        [$where, $params] = $this->query($q, $filter);
        $pager = $this->paginator((int) DB::value('SELECT COUNT(*) FROM media' . $where, $params), 48);
        $items = DB::all('SELECT * FROM media' . $where . ' ORDER BY created_at DESC, id DESC LIMIT ' . $pager->perPage . ' OFFSET ' . $pager->offset, $params);
        return $this->view('media/index', ['items' => $items, 'q' => $q, 'filter' => $filter, 'pager' => $pager, 'maxMb' => (int) round((int) config('uploads.max_bytes') / 1048576)], 'Medien');
    }

    /** JSON list for the media picker dialog. */
    public function picker(): Response
    {
        [$where, $params] = $this->query($this->request->q('q'), '');
        $page = max(1, $this->request->qInt('seite', 1));
        $rows = DB::all('SELECT * FROM media' . $where . ' ORDER BY created_at DESC, id DESC LIMIT 31 OFFSET ' . (($page - 1) * 30), $params);
        $more = count($rows) > 30;
        return Response::json([
            'items' => array_map(fn ($m) => ['id' => (int) $m['id'], 'thumb' => Media::url($m, 'sm'), 'alt' => $m['alt'], 'name' => $m['original_name'], 'w' => (int) $m['width'], 'h' => (int) $m['height']], array_slice($rows, 0, 30)),
            'more' => $more,
        ]);
    }

    public function upload(): Response
    {
        $files = $this->request->fileList('files');
        if (!$files && ($single = $this->request->file('file'))) {
            $files = [$single];
        }
        $ok = [];
        $failed = [];
        foreach (array_slice($files, 0, 20) as $file) {
            try {
                $id = Media::upload($file, (int) $this->user['id'], ['alt' => $this->request->str('alt', 255)]);
                $ok[] = $id;
                $this->audit('media.uploaded', 'media', $id, 'Bild „' . mb_substr((string) $file['name'], 0, 80) . '“ hochgeladen');
            } catch (MediaException $e) {
                $failed[] = mb_substr((string) $file['name'], 0, 80) . ': ' . $e->getMessage();
            } catch (\Throwable $e) {
                ErrorHandler::log($e);
                $failed[] = mb_substr((string) $file['name'], 0, 80) . ': Unerwarteter Fehler beim Verarbeiten.';
            }
        }
        if ($this->request->wantsJson()) {
            if (!$ok) {
                return Response::json(['error' => $failed[0] ?? 'Keine Datei empfangen.'], 422);
            }
            $m = Media::find($ok[0]);
            return Response::json(['id' => $ok[0], 'thumb' => Media::url($m, 'sm'), 'alt' => $m['alt'], 'name' => $m['original_name']]);
        }
        if (!$files) {
            return $this->redirect('/admin/media', 'error', 'Bitte wählen Sie mindestens eine Datei aus.');
        }
        foreach ($failed as $f) {
            \App\Core\Session::flash('error', $f);
        }
        if ($ok) {
            \App\Core\Session::flash('success', count($ok) . ' Bild(er) hochgeladen. Bitte ergänzen Sie für jedes Bild einen Alternativtext.');
            return $this->redirect(count($ok) === 1 ? '/admin/media/' . $ok[0] : '/admin/media?filter=noalt');
        }
        return $this->redirect('/admin/media');
    }

    public function edit(int $id): Response
    {
        $m = $this->findOr404(Media::find($id));
        return $this->view('media/edit', ['m' => $m, 'usage' => Media::usage($id), 'maxMb' => (int) round((int) config('uploads.max_bytes') / 1048576)], 'Bild bearbeiten');
    }

    public function update(int $id): Response
    {
        $this->findOr404(Media::find($id));
        $r = $this->request;
        $source = $r->str('source_url', 500);
        if ($source !== '' && !preg_match('#^https?://#i', $source)) {
            return $this->invalid('/admin/media/' . $id, ['source_url' => 'Bitte eine vollständige Adresse (https://…) angeben.']);
        }
        DB::update('media', [
            'alt' => $r->str('alt', 255),
            'caption' => $r->str('caption', 255),
            'credit' => $r->str('credit', 255),
            'license' => $r->str('license', 80),
            'source_url' => $source,
            'is_placeholder' => $r->bool('is_placeholder') ? 1 : 0,
        ], 'id = :id', ['id' => $id]);
        Media::forget($id);
        $this->audit('media.updated', 'media', $id, 'Bildangaben bearbeitet');
        return $this->redirect('/admin/media/' . $id, 'success', 'Die Bildangaben wurden gespeichert.');
    }

    public function replace(int $id): Response
    {
        $this->findOr404(Media::find($id));
        $file = $this->request->file('file');
        if (!$file) {
            return $this->redirect('/admin/media/' . $id, 'error', 'Bitte eine Datei auswählen.');
        }
        try {
            Media::replace($id, $file);
        } catch (MediaException $e) {
            return $this->redirect('/admin/media/' . $id, 'error', $e->getMessage());
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            return $this->redirect('/admin/media/' . $id, 'error', 'Das Bild konnte nicht ersetzt werden.');
        }
        $this->audit('media.replaced', 'media', $id, 'Bilddatei ersetzt');
        return $this->redirect('/admin/media/' . $id, 'success', 'Die Bilddatei wurde ersetzt. Alle Verwendungen zeigen jetzt das neue Bild. Bitte Alternativtext und Bildnachweis prüfen.');
    }

    public function delete(int $id): Response
    {
        $m = $this->findOr404(Media::find($id));
        try {
            Media::delete($id);
        } catch (MediaException $e) {
            return $this->redirect('/admin/media/' . $id, 'error', $e->getMessage());
        }
        $this->audit('media.deleted', 'media', $id, 'Bild „' . $m['original_name'] . '“ gelöscht');
        return $this->redirect('/admin/media', 'success', 'Das Bild wurde gelöscht.');
    }
}
