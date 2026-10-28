<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Response;
use App\Repositories\ContentRepository;

final class GalleryAdminController extends AdminController
{
    private function usage(int $id): array
    {
        $out = [];
        foreach (DB::all('SELECT id, title FROM projects WHERE gallery_id = ?', [$id]) as $p) {
            $out[] = ['label' => 'Referenz „' . $p['title'] . '“', 'url' => '/admin/projects/' . $p['id']];
        }
        foreach (DB::all("SELECT b.id, COALESCE(p.title, s.title, b.library_name) AS owner FROM content_blocks b LEFT JOIN pages p ON p.id = b.page_id LEFT JOIN services s ON s.id = b.service_id
                WHERE b.type = 'gallery' AND CAST(JSON_UNQUOTE(JSON_EXTRACT(b.data, '$.gallery')) AS UNSIGNED) = ?", [$id]) as $b) {
            $out[] = ['label' => 'Galerie-Block auf „' . $b['owner'] . '“', 'url' => '/admin/blocks/' . $b['id']];
        }
        return $out;
    }

    public function index(): Response
    {
        $galleries = ContentRepository::galleries();
        foreach ($galleries as &$g) {
            $g['usage'] = $this->usage((int) $g['id']);
            $g['first'] = DB::value('SELECT media_id FROM gallery_items WHERE gallery_id = ? ORDER BY sort_order, id LIMIT 1', [$g['id']]);
        }
        return $this->view('galleries/index', ['galleries' => $galleries], 'Galerien');
    }

    public function store(): Response
    {
        $name = $this->request->str('name', 160);
        if ($name === '') {
            return $this->invalid('/admin/galleries', ['name' => 'Bitte einen Namen angeben.']);
        }
        $id = DB::insert('galleries', ['name' => $name]);
        $this->audit('gallery.created', 'gallery', $id, 'Galerie „' . $name . '“ angelegt');
        return $this->redirect('/admin/galleries/' . $id, 'success', 'Galerie angelegt. Fügen Sie jetzt Bilder hinzu.');
    }

    public function edit(int $id): Response
    {
        $g = $this->findOr404(DB::row('SELECT * FROM galleries WHERE id = ?', [$id]));
        return $this->view('galleries/edit', ['g' => $g, 'items' => ContentRepository::galleryItems($id), 'usage' => $this->usage($id)], 'Galerie: ' . $g['name']);
    }

    public function update(int $id): Response
    {
        $this->findOr404(DB::row('SELECT * FROM galleries WHERE id = ?', [$id]));
        $name = $this->request->str('name', 160);
        DB::transaction(function () use ($id, $name) {
            if ($name !== '') {
                DB::update('galleries', ['name' => $name], 'id = :id', ['id' => $id]);
            }
            foreach ($this->request->arr('captions') as $itemId => $caption) {
                DB::exec('UPDATE gallery_items SET caption = ? WHERE id = ? AND gallery_id = ?', [mb_substr(trim((string) $caption), 0, 255), (int) $itemId, $id]);
            }
        });
        $this->audit('gallery.updated', 'gallery', $id, 'Galerie bearbeitet');
        return $this->redirect('/admin/galleries/' . $id, 'success', 'Die Galerie wurde gespeichert.');
    }

    public function addItems(int $id): Response
    {
        $this->findOr404(DB::row('SELECT * FROM galleries WHERE id = ?', [$id]));
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->request->arr('media_ids')))));
        if (!$ids && $this->request->int('media_id')) {
            $ids = [$this->request->int('media_id')];
        }
        $added = 0;
        DB::transaction(function () use ($id, $ids, &$added) {
            $sort = (int) DB::value('SELECT COALESCE(MAX(sort_order), 0) FROM gallery_items WHERE gallery_id = ?', [$id]);
            foreach ($ids as $mid) {
                if (DB::value('SELECT id FROM media WHERE id = ?', [$mid])) {
                    $sort += 10;
                    DB::insert('gallery_items', ['gallery_id' => $id, 'media_id' => $mid, 'sort_order' => $sort]);
                    $added++;
                }
            }
        });
        return $this->redirect('/admin/galleries/' . $id, $added ? 'success' : 'error', $added ? $added . ' Bild(er) hinzugefügt.' : 'Keine gültigen Bilder ausgewählt.');
    }

    public function moveItem(int $id, int $itemId): Response
    {
        $ids = array_map('intval', DB::column('SELECT id FROM gallery_items WHERE gallery_id = ? ORDER BY sort_order, id', [$id]));
        $pos = array_search($itemId, $ids, true);
        $dir = $this->request->str('dir', 6);
        $swap = match ($dir) { 'up' => $pos - 1, 'first' => 0, default => $pos + 1 };
        if ($pos !== false && $swap >= 0 && $swap < count($ids)) {
            if ($dir === 'first') {
                array_splice($ids, $pos, 1);
                array_unshift($ids, $itemId);
            } else {
                [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            }
            DB::transaction(function () use ($ids) {
                foreach ($ids as $i => $gid) {
                    DB::exec('UPDATE gallery_items SET sort_order = ? WHERE id = ?', [($i + 1) * 10, $gid]);
                }
            });
        }
        return $this->redirect('/admin/galleries/' . $id . '#item-' . $itemId);
    }

    public function removeItem(int $id, int $itemId): Response
    {
        DB::exec('DELETE FROM gallery_items WHERE id = ? AND gallery_id = ?', [$itemId, $id]);
        return $this->redirect('/admin/galleries/' . $id, 'success', 'Bild aus der Galerie entfernt (bleibt in der Mediathek).');
    }

    public function delete(int $id): Response
    {
        $g = $this->findOr404(DB::row('SELECT * FROM galleries WHERE id = ?', [$id]));
        if ($this->usage($id)) {
            return $this->redirect('/admin/galleries/' . $id, 'error', 'Die Galerie wird noch verwendet und kann nicht gelöscht werden.');
        }
        DB::exec('DELETE FROM galleries WHERE id = ?', [$id]);
        $this->audit('gallery.deleted', 'gallery', $id, 'Galerie „' . $g['name'] . '“ gelöscht');
        return $this->redirect('/admin/galleries', 'success', 'Die Galerie wurde gelöscht. Die Bilder bleiben in der Mediathek.');
    }
}
