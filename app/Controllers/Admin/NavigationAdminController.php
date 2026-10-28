<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Response;
use App\Repositories\NavigationRepository;
use App\Services\FieldForm;

final class NavigationAdminController extends AdminController
{
    public function index(): Response
    {
        $menu = $this->request->q('menu', 'main');
        if (!isset(NavigationRepository::MENUS[$menu])) {
            $menu = 'main';
        }
        $rows = NavigationRepository::rows($menu);
        $tree = [];
        foreach ($rows as $r) {
            if ($r['parent_id'] === null) {
                $tree[] = ['item' => $r, 'children' => array_values(array_filter($rows, fn ($c) => (int) $c['parent_id'] === (int) $r['id']))];
            }
        }
        return $this->view('navigation/index', ['menu' => $menu, 'tree' => $tree, 'item' => null], 'Navigation');
    }

    public function edit(int $id): Response
    {
        $item = $this->findOr404(DB::row('SELECT * FROM nav_items WHERE id = ?', [$id]));
        return $this->view('navigation/edit', ['item' => $item, 'menu' => $item['menu']], 'Menüeintrag bearbeiten');
    }

    public function store(): Response
    {
        $menu = $this->request->str('menu', 20);
        [$data, $errors] = $this->validate(null, $menu);
        if ($errors) {
            return $this->invalid('/admin/navigation?menu=' . urlencode($menu), $errors);
        }
        $data['menu'] = $menu;
        $data['sort_order'] = (int) DB::value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM nav_items WHERE menu = ? AND parent_id <=> ?', [$menu, $data['parent_id']]);
        $id = DB::insert('nav_items', $data);
        $this->audit('nav.created', 'nav', $id, 'Menüeintrag „' . $data['label'] . '“ (' . NavigationRepository::MENUS[$menu] . ') angelegt');
        return $this->redirect('/admin/navigation?menu=' . $menu, 'success', 'Menüeintrag hinzugefügt.');
    }

    public function update(int $id): Response
    {
        $item = $this->findOr404(DB::row('SELECT * FROM nav_items WHERE id = ?', [$id]));
        [$data, $errors] = $this->validate($item, $item['menu']);
        if ($errors) {
            return $this->invalid('/admin/navigation/' . $id, $errors);
        }
        DB::update('nav_items', $data, 'id = :id', ['id' => $id]);
        $this->audit('nav.updated', 'nav', $id, 'Menüeintrag „' . $data['label'] . '“ bearbeitet');
        return $this->redirect('/admin/navigation?menu=' . $item['menu'], 'success', 'Menüeintrag gespeichert.');
    }

    public function move(int $id): Response
    {
        $item = $this->findOr404(DB::row('SELECT * FROM nav_items WHERE id = ?', [$id]));
        $ids = array_map('intval', DB::column('SELECT id FROM nav_items WHERE menu = ? AND parent_id <=> ? ORDER BY sort_order, id', [$item['menu'], $item['parent_id']]));
        $pos = array_search($id, $ids, true);
        $swap = $pos + ($this->request->str('dir', 4) === 'up' ? -1 : 1);
        if ($pos !== false && $swap >= 0 && $swap < count($ids)) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            DB::transaction(function () use ($ids) {
                foreach ($ids as $i => $nid) {
                    DB::exec('UPDATE nav_items SET sort_order = ? WHERE id = ?', [($i + 1) * 10, $nid]);
                }
            });
        }
        return $this->redirect('/admin/navigation?menu=' . $item['menu']);
    }

    public function delete(int $id): Response
    {
        $item = $this->findOr404(DB::row('SELECT * FROM nav_items WHERE id = ?', [$id]));
        $children = (int) DB::value('SELECT COUNT(*) FROM nav_items WHERE parent_id = ?', [$id]);
        DB::exec('DELETE FROM nav_items WHERE id = ?', [$id]);
        $this->audit('nav.deleted', 'nav', $id, 'Menüeintrag „' . $item['label'] . '“ gelöscht' . ($children ? ' (inkl. ' . $children . ' Untereinträgen)' : ''));
        return $this->redirect('/admin/navigation?menu=' . $item['menu'], 'success', 'Menüeintrag gelöscht.');
    }

    /** @return array{0:array,1:array} */
    private function validate(?array $item, string $menu): array
    {
        $r = $this->request;
        $errors = [];
        if (!isset(NavigationRepository::MENUS[$menu])) {
            $errors['menu'] = 'Ungültiges Menü.';
        }
        $label = $r->str('label', 120);
        if ($label === '') {
            $errors['label'] = 'Bitte eine Beschriftung angeben.';
        }
        $type = $r->str('link_type', 12);
        if (!isset(NavigationRepository::TYPES[$type])) {
            $type = 'page';
        }
        $pageId = $serviceId = null;
        $url = '';
        if ($type === 'page') {
            $pageId = $r->int('page_id') ?: null;
            if (!$pageId || !DB::value('SELECT id FROM pages WHERE id = ?', [$pageId])) {
                $errors['page_id'] = 'Bitte eine Seite auswählen.';
            }
        } elseif ($type === 'service') {
            $serviceId = $r->int('service_id') ?: null;
            if (!$serviceId || !DB::value('SELECT id FROM services WHERE id = ?', [$serviceId])) {
                $errors['service_id'] = 'Bitte eine Leistung auswählen.';
            }
        } else {
            $url = $r->str('url', 500);
            if ($url === '' || !FieldForm::validLink($url)) {
                $errors['url'] = 'Bitte einen gültigen Link angeben (z. B. /seite, https://…, mailto: oder tel:).';
            }
        }
        $parentId = $r->int('parent_id') ?: null;
        if ($parentId !== null) {
            $parent = DB::row('SELECT * FROM nav_items WHERE id = ?', [$parentId]);
            if (!$parent || $parent['menu'] !== $menu || $parent['parent_id'] !== null || ($item && $parentId === (int) $item['id'])) {
                $errors['parent_id'] = 'Untermenüs sind nur eine Ebene tief möglich.';
            } elseif ($item && DB::value('SELECT id FROM nav_items WHERE parent_id = ?', [$item['id']])) {
                $errors['parent_id'] = 'Dieser Eintrag hat selbst Untereinträge und kann nicht verschoben werden.';
            }
        }
        return [[
            'label' => $label,
            'link_type' => $type,
            'page_id' => $pageId,
            'service_id' => $serviceId,
            'url' => $url,
            'parent_id' => $parentId,
            'auto_children' => $r->bool('auto_children') && $parentId === null ? 1 : 0,
            'new_tab' => $r->bool('new_tab') ? 1 : 0,
            'is_visible' => $r->bool('is_visible') ? 1 : 0,
        ], $errors];
    }
}
