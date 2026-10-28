<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Site\ServiceController;
use App\Core\DB;
use App\Core\Response;
use App\Repositories\BlockRepository;
use App\Repositories\ServiceRepository;
use App\Services\BlockTypes;
use App\Support\Slug;

final class ServiceAdminController extends AdminController
{
    public const STATUSES = ['published' => 'Veröffentlicht', 'draft' => 'Entwurf', 'archived' => 'Archiviert'];

    public function index(): Response
    {
        return $this->view('services/index', ['tree' => ServiceRepository::tree()], 'Dienstleistungen');
    }

    public function create(): Response
    {
        return $this->view('services/form', ['service' => null, 'related' => [], 'parentId' => $this->request->qInt('parent')], 'Neue Leistung');
    }

    public function store(): Response
    {
        [$data, $related, $errors] = $this->validate(null);
        if ($errors) {
            return $this->invalid('/admin/services/create', $errors);
        }
        $id = DB::transaction(function () use ($data, $related) {
            $data['sort_order'] = (int) DB::value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM services WHERE parent_id <=> ?', [$data['parent_id']]);
            $id = DB::insert('services', $data);
            $this->saveRelations($id, $related);
            return $id;
        });
        $this->audit('service.created', 'service', $id, 'Leistung „' . $data['title'] . '“ angelegt');
        return $this->redirect('/admin/services/' . $id, 'success', 'Die Leistung wurde angelegt. Ergänzen Sie jetzt Inhaltsblöcke für die Detailseite.');
    }

    public function edit(int $id): Response
    {
        $s = $this->findOr404(ServiceRepository::find($id));
        return $this->view('services/form', [
            'service' => $s,
            'related' => ServiceRepository::relatedIds($id),
            'blocks' => BlockRepository::forOwner('service', $id, true),
            'blockers' => ServiceRepository::deleteBlockers($id),
            'projectCount' => (int) DB::value('SELECT COUNT(*) FROM projects WHERE service_id = ?', [$id]),
        ], 'Leistung: ' . $s['title']);
    }

    public function update(int $id): Response
    {
        $s = $this->findOr404(ServiceRepository::find($id));
        [$data, $related, $errors] = $this->validate($s);
        if ($errors) {
            return $this->invalid('/admin/services/' . $id, $errors);
        }
        DB::transaction(function () use ($id, $data, $related) {
            DB::update('services', $data, 'id = :id', ['id' => $id]);
            $this->saveRelations($id, $related);
        });
        $action = $data['status'] !== $s['status'] ? 'service.status' : 'service.updated';
        $this->audit($action, 'service', $id, 'Leistung „' . $data['title'] . '“ ' . ($action === 'service.status' ? 'auf „' . self::STATUSES[$data['status']] . '“ gesetzt' : 'bearbeitet'));
        return $this->redirect('/admin/services/' . $id, 'success', 'Die Leistung wurde gespeichert.');
    }

    public function move(int $id): Response
    {
        $s = $this->findOr404(ServiceRepository::find($id));
        $ids = array_map('intval', DB::column('SELECT id FROM services WHERE parent_id <=> ? ORDER BY sort_order, title', [$s['parent_id']]));
        $pos = array_search($id, $ids, true);
        $swap = $pos + ($this->request->str('dir', 4) === 'up' ? -1 : 1);
        if ($pos !== false && $swap >= 0 && $swap < count($ids)) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            DB::transaction(function () use ($ids) {
                foreach ($ids as $i => $sid) {
                    DB::exec('UPDATE services SET sort_order = ? WHERE id = ?', [($i + 1) * 10, $sid]);
                }
            });
        }
        return $this->redirect('/admin/services#service-' . $id);
    }

    public function delete(int $id): Response
    {
        $s = $this->findOr404(ServiceRepository::find($id));
        $hard = array_values(array_filter(ServiceRepository::deleteBlockers($id), fn ($r) => !str_contains($r, 'Referenz')));
        if ($hard) {
            return $this->redirect('/admin/services/' . $id, 'error', 'Löschen nicht möglich: ' . implode(' ', $hard) . ' Alternativ können Sie die Leistung archivieren.');
        }
        DB::transaction(function () use ($id) {
            DB::exec('UPDATE projects SET service_id = NULL WHERE service_id = ?', [$id]);
            DB::exec('DELETE FROM services WHERE id = ?', [$id]);
        });
        $this->audit('service.deleted', 'service', $id, 'Leistung „' . $s['title'] . '“ gelöscht');
        return $this->redirect('/admin/services', 'success', 'Die Leistung „' . $s['title'] . '“ wurde gelöscht.');
    }

    public function preview(int $id): Response
    {
        $s = $this->findOr404(ServiceRepository::find($id));
        return ServiceController::renderService($s, $this->request, true)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    // ---------------------------------------------------------------- helpers

    private function saveRelations(int $id, array $related): void
    {
        DB::exec('DELETE FROM service_relations WHERE service_id = ?', [$id]);
        foreach ($related as $rid) {
            if ($rid !== $id) {
                DB::exec('INSERT IGNORE INTO service_relations (service_id, related_id) SELECT ?, id FROM services WHERE id = ?', [$id, $rid]);
            }
        }
        ServiceRepository::flush();
    }

    /** @return array{0:array,1:int[],2:array} */
    private function validate(?array $s): array
    {
        $r = $this->request;
        $errors = [];
        $title = $r->str('title', 190);
        if ($title === '') {
            $errors['title'] = 'Bitte einen Namen angeben.';
        }
        $slug = Slug::make($r->str('slug', 120) !== '' ? $r->str('slug', 120) : $title);
        if (!Slug::valid($slug)) {
            $errors['slug'] = 'Bitte eine gültige URL angeben (Kleinbuchstaben, Ziffern, Bindestriche).';
        } elseif (DB::value('SELECT id FROM services WHERE slug = ? AND id <> ?', [$slug, (int) ($s['id'] ?? 0)])) {
            $errors['slug'] = 'Diese URL wird bereits von einer anderen Leistung verwendet.';
        }
        $parentId = $r->int('parent_id') ?: null;
        if ($parentId !== null) {
            $parent = ServiceRepository::find($parentId);
            if (!$parent || $parent['parent_id'] !== null || ($s && $parentId === (int) $s['id'])) {
                $errors['parent_id'] = 'Als übergeordnete Leistung ist nur eine Hauptleistung möglich.';
            } elseif ($s && ServiceRepository::children((int) $s['id'], false)) {
                $errors['parent_id'] = 'Diese Leistung hat selbst Unterleistungen und kann deshalb keiner anderen untergeordnet werden.';
            }
        }
        $status = $r->str('status', 12);
        if (!isset(self::STATUSES[$status])) {
            $status = 'draft';
        }
        $icon = $r->str('icon', 40);
        if ($icon !== '' && !isset(BlockTypes::ICONS[$icon])) {
            $icon = '';
        }
        $image = $r->int('image_media_id');
        if ($image && !DB::value('SELECT id FROM media WHERE id = ?', [$image])) {
            $image = 0;
        }
        $related = array_values(array_unique(array_map('intval', $r->arr('related'))));
        return [[
            'parent_id' => $parentId,
            'slug' => $slug,
            'title' => $title,
            'teaser' => $r->str('teaser', 600),
            'intro' => $r->str('intro', 1200),
            'icon' => $icon,
            'image_media_id' => $image ?: null,
            'status' => $status,
            'is_featured' => $r->bool('is_featured') ? 1 : 0,
            'meta_title' => $r->str('meta_title', 190),
            'meta_description' => $r->str('meta_description', 320),
            'admin_note' => $r->str('admin_note', 500),
        ], $related, $errors];
    }
}
