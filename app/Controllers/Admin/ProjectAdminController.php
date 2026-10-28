<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Site\ProjectController;
use App\Core\DB;
use App\Core\Response;
use App\Repositories\ContentRepository;
use App\Repositories\ProjectRepository;
use App\Security\HtmlSanitizer;
use App\Support\Slug;

final class ProjectAdminController extends AdminController
{
    public function index(): Response
    {
        $projects = DB::all('SELECT p.*, s.title AS service_title, (SELECT COUNT(*) FROM gallery_items gi WHERE gi.gallery_id = p.gallery_id) AS image_count
            FROM projects p LEFT JOIN services s ON s.id = p.service_id ORDER BY p.sort_order, p.created_at DESC');
        return $this->view('projects/index', ['projects' => $projects], 'Referenzen');
    }

    public function create(): Response
    {
        return $this->view('projects/form', ['project' => null, 'images' => []], 'Neue Referenz');
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate(null);
        if ($errors) {
            return $this->invalid('/admin/projects/create', $errors);
        }
        $id = DB::transaction(function () use ($data) {
            $data['gallery_id'] = DB::insert('galleries', ['name' => 'Referenz: ' . $data['title']]);
            $data['sort_order'] = (int) DB::value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM projects');
            return DB::insert('projects', $data);
        });
        $this->audit('project.created', 'project', $id, 'Referenz „' . $data['title'] . '“ angelegt');
        return $this->redirect('/admin/projects/' . $id, 'success', 'Die Referenz wurde angelegt. Fügen Sie jetzt Projektfotos hinzu.');
    }

    public function edit(int $id): Response
    {
        $p = $this->findOr404(ProjectRepository::find($id));
        return $this->view('projects/form', [
            'project' => $p,
            'images' => $p['gallery_id'] ? ContentRepository::galleryItems((int) $p['gallery_id']) : [],
        ], 'Referenz: ' . $p['title']);
    }

    public function update(int $id): Response
    {
        $p = $this->findOr404(ProjectRepository::find($id));
        [$data, $errors] = $this->validate($p);
        if ($errors) {
            return $this->invalid('/admin/projects/' . $id, $errors);
        }
        DB::transaction(function () use ($id, $p, $data) {
            if (!$p['gallery_id']) {
                $data['gallery_id'] = DB::insert('galleries', ['name' => 'Referenz: ' . $data['title']]);
            } else {
                DB::update('galleries', ['name' => 'Referenz: ' . $data['title']], 'id = :id', ['id' => $p['gallery_id']]);
            }
            DB::update('projects', $data, 'id = :id', ['id' => $id]);
        });
        $action = $data['status'] !== $p['status'] ? 'project.status' : 'project.updated';
        $this->audit($action, 'project', $id, 'Referenz „' . $data['title'] . '“ ' . ($action === 'project.status' ? '→ ' . status_label($data['status']) : 'bearbeitet'));
        return $this->redirect('/admin/projects/' . $id, 'success', 'Die Referenz wurde gespeichert.');
    }

    public function move(int $id): Response
    {
        $this->findOr404(ProjectRepository::find($id));
        $ids = array_map('intval', DB::column('SELECT id FROM projects ORDER BY sort_order, created_at DESC'));
        $pos = array_search($id, $ids, true);
        $swap = $pos + ($this->request->str('dir', 4) === 'up' ? -1 : 1);
        if ($pos !== false && $swap >= 0 && $swap < count($ids)) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            DB::transaction(function () use ($ids) {
                foreach ($ids as $i => $pid) {
                    DB::exec('UPDATE projects SET sort_order = ? WHERE id = ?', [($i + 1) * 10, $pid]);
                }
            });
        }
        return $this->redirect('/admin/projects');
    }

    public function delete(int $id): Response
    {
        $p = $this->findOr404(ProjectRepository::find($id));
        DB::transaction(function () use ($p, $id) {
            DB::exec('DELETE FROM projects WHERE id = ?', [$id]);
            if ($p['gallery_id']) {
                // The project gallery belongs to the project. Keep it if it is also used in a content block.
                $usedInBlock = DB::value("SELECT id FROM content_blocks WHERE type = 'gallery' AND CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.gallery')) AS UNSIGNED) = ?", [(int) $p['gallery_id']]);
                if (!$usedInBlock) {
                    DB::exec('DELETE FROM galleries WHERE id = ?', [$p['gallery_id']]);
                }
            }
        });
        $this->audit('project.deleted', 'project', $id, 'Referenz „' . $p['title'] . '“ gelöscht');
        return $this->redirect('/admin/projects', 'success', 'Die Referenz wurde gelöscht. Die Bilder bleiben in der Mediathek erhalten.');
    }

    public function preview(int $id): Response
    {
        $p = $this->findOr404(ProjectRepository::find($id));
        return ProjectController::renderProject($p, $this->request, true)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /** @return array{0:array,1:array} */
    private function validate(?array $p): array
    {
        $r = $this->request;
        $errors = [];
        $title = $r->str('title', 190);
        if ($title === '') {
            $errors['title'] = 'Bitte einen Titel angeben.';
        }
        $slug = Slug::make($r->str('slug', 120) !== '' ? $r->str('slug', 120) : $title);
        if (!Slug::valid($slug)) {
            $errors['slug'] = 'Bitte eine gültige URL angeben.';
        } elseif (ProjectRepository::slugTaken($slug, (int) ($p['id'] ?? 0))) {
            $errors['slug'] = 'Diese URL wird bereits von einer anderen Referenz verwendet.';
        }
        $status = $r->str('status', 12);
        if (!in_array($status, ['draft', 'published', 'archived'], true)) {
            $status = 'draft';
        }
        $cover = $r->int('cover_media_id');
        if ($cover && !DB::value('SELECT id FROM media WHERE id = ?', [$cover])) {
            $cover = 0;
        }
        if ($status === 'published' && !$cover) {
            $errors['cover_media_id'] = 'Für eine veröffentlichte Referenz wird ein Titelbild benötigt.';
        }
        $serviceId = $r->int('service_id');
        if ($serviceId && !DB::value('SELECT id FROM services WHERE id = ?', [$serviceId])) {
            $serviceId = 0;
        }
        return [[
            'title' => $title,
            'slug' => $slug,
            'service_id' => $serviceId ?: null,
            'location' => $r->str('location', 160),
            'period' => $r->str('period', 80),
            'summary' => $r->str('summary', 1000),
            'body' => HtmlSanitizer::clean((string) $r->input('body', '')),
            'cover_media_id' => $cover ?: null,
            'status' => $status,
            'is_featured' => $r->bool('is_featured') ? 1 : 0,
            'meta_title' => $r->str('meta_title', 190),
            'meta_description' => $r->str('meta_description', 320),
        ], $errors];
    }
}
