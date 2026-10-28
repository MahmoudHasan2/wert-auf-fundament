<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Site\SiteController;
use App\Core\DB;
use App\Core\Response;
use App\Repositories\BlockRepository;
use App\Repositories\PageRepository;
use App\Services\Revisions;
use App\Support\Slug;

final class PageAdminController extends AdminController
{
    public function index(): Response
    {
        $status = $this->request->q('status');
        $q = $this->request->q('q');
        $sql = 'SELECT p.*, (SELECT COUNT(*) FROM content_blocks b WHERE b.page_id = p.id) AS block_count FROM pages p WHERE 1=1';
        $params = [];
        if (in_array($status, ['draft', 'published'], true)) {
            $sql .= ' AND p.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $sql .= ' AND (p.title LIKE ? OR p.slug LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        $sql .= " ORDER BY p.system_key IS NULL, FIELD(p.system_key, 'home', 'services', 'projects', 'about', 'contact', 'imprint', 'privacy'), p.title";
        return $this->view('pages/index', ['pages' => DB::all($sql, $params), 'status' => $status, 'q' => $q], 'Seiten');
    }

    public function create(): Response
    {
        return $this->view('pages/create', [], 'Neue Seite');
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate(null);
        if ($errors) {
            return $this->invalid('/admin/pages/create', $errors);
        }
        $id = DB::transaction(function () use ($data) {
            $id = DB::insert('pages', $data + ['updated_by' => $this->user['id']]);
            BlockRepository::create('page', $id, 'hero', ['eyebrow' => '', 'title' => $data['title'], 'text' => '', 'image' => 0, 'button1_label' => '', 'button1_url' => '', 'button2_label' => '', 'button2_url' => '', 'variant' => 'compact']);
            return $id;
        });
        Revisions::snapshot($id, 'Seite angelegt');
        $this->audit('page.created', 'page', $id, 'Seite „' . $data['title'] . '“ angelegt');
        return $this->redirect('/admin/pages/' . $id, 'success', 'Die Seite wurde angelegt. Fügen Sie jetzt Inhaltsblöcke hinzu.');
    }

    public function edit(int $id): Response
    {
        $page = $this->findOr404(PageRepository::find($id));
        $this->guardLegal($page);
        return $this->view('pages/edit', [
            'page' => $page,
            'blocks' => BlockRepository::forOwner('page', $id, true),
            'revisions' => Revisions::forPage($id),
            'navRefs' => PageRepository::navReferences($id),
        ], 'Seite: ' . $page['title']);
    }

    public function update(int $id): Response
    {
        $page = $this->findOr404(PageRepository::find($id));
        $this->guardLegal($page);
        [$data, $errors] = $this->validate($page);
        if ($errors) {
            return $this->invalid('/admin/pages/' . $id, $errors);
        }
        DB::update('pages', $data + ['updated_by' => $this->user['id']], 'id = :id', ['id' => $id]);
        Revisions::snapshot($id, 'Seiteneinstellungen gespeichert');
        $action = $data['status'] !== $page['status'] ? ($data['status'] === 'published' ? 'page.published' : 'page.unpublished') : 'page.updated';
        $this->audit($action, 'page', $id, 'Seite „' . $data['title'] . '“ ' . ['page.published' => 'veröffentlicht', 'page.unpublished' => 'zurückgezogen', 'page.updated' => 'bearbeitet'][$action]);
        return $this->redirect('/admin/pages/' . $id, 'success', 'Die Seiteneinstellungen wurden gespeichert.');
    }

    public function delete(int $id): Response
    {
        $page = $this->findOr404(PageRepository::find($id));
        if ($page['system_key'] !== null) {
            return $this->redirect('/admin/pages/' . $id, 'error', 'Systemseiten können nicht gelöscht werden. Sie können die Seite stattdessen als Entwurf speichern (außer Rechtstexte).');
        }
        $refs = PageRepository::navReferences($id);
        if ($refs) {
            return $this->redirect('/admin/pages/' . $id, 'error', 'Die Seite ist noch im Menü verlinkt (' . implode(', ', array_column($refs, 'label')) . '). Entfernen Sie zuerst die Menüeinträge.');
        }
        DB::exec('DELETE FROM pages WHERE id = ?', [$id]);
        $this->audit('page.deleted', 'page', $id, 'Seite „' . $page['title'] . '“ gelöscht');
        return $this->redirect('/admin/pages', 'success', 'Die Seite „' . $page['title'] . '“ wurde gelöscht.');
    }

    public function duplicate(int $id): Response
    {
        $page = $this->findOr404(PageRepository::find($id));
        $this->guardLegal($page);
        $base = $page['slug'] . '-kopie';
        $slug = $base;
        for ($i = 2; PageRepository::slugTaken($slug) || Slug::reserved($slug); $i++) {
            $slug = $base . '-' . $i;
        }
        $newId = DB::transaction(function () use ($page, $id, $slug) {
            $newId = DB::insert('pages', [
                'slug' => $slug, 'title' => $page['title'] . ' (Kopie)', 'template' => $page['template'], 'status' => 'draft',
                'meta_title' => $page['meta_title'], 'meta_description' => $page['meta_description'], 'og_media_id' => $page['og_media_id'],
                'noindex' => $page['noindex'], 'updated_by' => $this->user['id'],
            ]);
            foreach (BlockRepository::forOwner('page', $id, true) as $b) {
                BlockRepository::create('page', $newId, $b['type'], $b['data'], null, (bool) $b['is_visible'], $b['admin_note']);
            }
            return $newId;
        });
        $this->audit('page.created', 'page', $newId, 'Seite „' . $page['title'] . '“ dupliziert');
        return $this->redirect('/admin/pages/' . $newId, 'success', 'Kopie als Entwurf angelegt.');
    }

    public function preview(int $id): Response
    {
        $page = $this->findOr404(PageRepository::find($id));
        return SiteController::renderPage($page, $this->request, true)->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function restore(int $id, int $revisionId): Response
    {
        $page = $this->findOr404(PageRepository::find($id));
        $this->guardLegal($page);
        $rev = DB::row('SELECT id FROM page_revisions WHERE id = ? AND page_id = ?', [$revisionId, $id]);
        if (!$rev) {
            return $this->redirect('/admin/pages/' . $id, 'error', 'Version nicht gefunden.');
        }
        Revisions::snapshot($id, 'Stand vor Wiederherstellung');
        Revisions::restore($revisionId);
        $this->audit('page.restored', 'page', $id, 'Seite „' . $page['title'] . '“ auf Version #' . $revisionId . ' zurückgesetzt');
        return $this->redirect('/admin/pages/' . $id, 'success', 'Die Version wurde wiederhergestellt. Der vorherige Stand ist als eigene Version gesichert.');
    }

    public function imprint(): Response
    {
        $p = PageRepository::system('imprint');
        return $this->redirect($p ? '/admin/pages/' . $p['id'] : '/admin/pages');
    }

    public function privacy(): Response
    {
        $p = PageRepository::system('privacy');
        return $this->redirect($p ? '/admin/pages/' . $p['id'] : '/admin/pages');
    }

    // ---------------------------------------------------------------- helpers

    private function guardLegal(array $page): void
    {
        if (PageRepository::isLegal($page)) {
            $this->requirePerm('legal');
        }
    }

    /** @return array{0:array,1:array} */
    private function validate(?array $page): array
    {
        $r = $this->request;
        $errors = [];
        $title = $r->str('title', 190);
        if ($title === '') {
            $errors['title'] = 'Bitte einen Seitentitel angeben.';
        }
        $isSystem = $page !== null && $page['system_key'] !== null;
        if ($isSystem) {
            $slug = $page['slug'];
        } else {
            $slug = Slug::make($r->str('slug', 120) !== '' ? $r->str('slug', 120) : $title);
            if (!Slug::valid($slug)) {
                $errors['slug'] = 'Bitte eine gültige URL angeben (Kleinbuchstaben, Ziffern und Bindestriche).';
            } elseif (Slug::reserved($slug)) {
                $errors['slug'] = 'Diese URL ist für das System reserviert. Bitte eine andere wählen.';
            } elseif (PageRepository::slugTaken($slug, (int) ($page['id'] ?? 0))) {
                $errors['slug'] = 'Diese URL wird bereits von einer anderen Seite verwendet.';
            }
        }
        $template = $r->str('template', 20);
        if (!isset(PageRepository::TEMPLATES[$template])) {
            $template = 'standard';
        }
        $status = $r->str('status', 12) === 'published' ? 'published' : 'draft';
        $publishedAt = $this->dateInput($r->str('published_at', 30));
        if ($page && (PageRepository::isLegal($page) || $page['system_key'] === 'home')) {
            if ($status !== 'published' || ($publishedAt !== null && strtotime($publishedAt) > time())) {
                $errors['status'] = 'Startseite, Impressum und Datenschutzerklärung müssen dauerhaft veröffentlicht bleiben.';
            }
        }
        $og = $r->int('og_media_id');
        if ($og && !DB::value('SELECT id FROM media WHERE id = ?', [$og])) {
            $og = 0;
        }
        return [[
            'title' => $title,
            'slug' => $slug,
            'template' => $template,
            'status' => $status,
            'published_at' => $publishedAt,
            'meta_title' => $r->str('meta_title', 190),
            'meta_description' => $r->str('meta_description', 320),
            'og_media_id' => $og ?: null,
            'noindex' => $r->bool('noindex') ? 1 : 0,
        ], $errors];
    }
}
