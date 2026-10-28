<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Response;
use App\Repositories\ContentRepository;
use App\Security\HtmlSanitizer;

final class FaqAdminController extends AdminController
{
    public function index(): Response
    {
        return $this->view('faq/index', [
            'categories' => ContentRepository::faqCategories(),
            'faqs' => DB::all('SELECT f.*, c.name AS category_name FROM faqs f LEFT JOIN faq_categories c ON c.id = f.category_id ORDER BY c.sort_order, f.category_id, f.sort_order, f.id'),
        ], 'Häufige Fragen (FAQ)');
    }

    public function create(): Response
    {
        return $this->view('faq/form', ['faq' => null, 'categories' => ContentRepository::faqCategories()], 'Neue Frage');
    }

    public function store(): Response
    {
        [$data, $errors] = $this->validate();
        if ($errors) {
            return $this->invalid('/admin/faq/create', $errors);
        }
        $data['sort_order'] = (int) DB::value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM faqs WHERE category_id <=> ?', [$data['category_id']]);
        $id = DB::insert('faqs', $data);
        $this->audit('faq.created', 'faq', $id, 'FAQ „' . mb_substr($data['question'], 0, 80) . '“ angelegt');
        return $this->redirect('/admin/faq', 'success', 'Die Frage wurde angelegt.');
    }

    public function edit(int $id): Response
    {
        $faq = $this->findOr404(DB::row('SELECT * FROM faqs WHERE id = ?', [$id]));
        return $this->view('faq/form', ['faq' => $faq, 'categories' => ContentRepository::faqCategories()], 'Frage bearbeiten');
    }

    public function update(int $id): Response
    {
        $this->findOr404(DB::row('SELECT * FROM faqs WHERE id = ?', [$id]));
        [$data, $errors] = $this->validate();
        if ($errors) {
            return $this->invalid('/admin/faq/' . $id, $errors);
        }
        DB::update('faqs', $data, 'id = :id', ['id' => $id]);
        $this->audit('faq.updated', 'faq', $id, 'FAQ „' . mb_substr($data['question'], 0, 80) . '“ bearbeitet');
        return $this->redirect('/admin/faq', 'success', 'Die Frage wurde gespeichert.');
    }

    public function move(int $id): Response
    {
        $faq = $this->findOr404(DB::row('SELECT * FROM faqs WHERE id = ?', [$id]));
        $ids = array_map('intval', DB::column('SELECT id FROM faqs WHERE category_id <=> ? ORDER BY sort_order, id', [$faq['category_id']]));
        $pos = array_search($id, $ids, true);
        $swap = $pos + ($this->request->str('dir', 4) === 'up' ? -1 : 1);
        if ($pos !== false && $swap >= 0 && $swap < count($ids)) {
            [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
            foreach ($ids as $i => $fid) {
                DB::exec('UPDATE faqs SET sort_order = ? WHERE id = ?', [($i + 1) * 10, $fid]);
            }
        }
        return $this->redirect('/admin/faq#faq-' . $id);
    }

    public function delete(int $id): Response
    {
        $faq = $this->findOr404(DB::row('SELECT * FROM faqs WHERE id = ?', [$id]));
        DB::exec('DELETE FROM faqs WHERE id = ?', [$id]);
        $this->audit('faq.deleted', 'faq', $id, 'FAQ „' . mb_substr($faq['question'], 0, 80) . '“ gelöscht');
        return $this->redirect('/admin/faq', 'success', 'Die Frage wurde gelöscht.');
    }

    public function storeCategory(): Response
    {
        $name = $this->request->str('name', 120);
        if ($name === '') {
            return $this->redirect('/admin/faq', 'error', 'Bitte einen Kategorienamen angeben.');
        }
        DB::insert('faq_categories', ['name' => $name, 'sort_order' => (int) DB::value('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM faq_categories')]);
        return $this->redirect('/admin/faq', 'success', 'Kategorie angelegt.');
    }

    public function updateCategory(int $id): Response
    {
        $name = $this->request->str('name', 120);
        if ($name !== '') {
            DB::update('faq_categories', ['name' => $name, 'sort_order' => $this->request->int('sort_order')], 'id = :id', ['id' => $id]);
        }
        return $this->redirect('/admin/faq', 'success', 'Kategorie gespeichert.');
    }

    public function deleteCategory(int $id): Response
    {
        $used = (int) DB::value("SELECT COUNT(*) FROM content_blocks WHERE type = 'faq' AND CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.category')) AS UNSIGNED) = ?", [$id]);
        if ($used) {
            return $this->redirect('/admin/faq', 'error', 'Die Kategorie wird in ' . $used . ' FAQ-Block/Blöcken verwendet und kann nicht gelöscht werden.');
        }
        DB::exec('DELETE FROM faq_categories WHERE id = ?', [$id]);
        return $this->redirect('/admin/faq', 'success', 'Kategorie gelöscht. Zugehörige Fragen sind jetzt ohne Kategorie.');
    }

    /** @return array{0:array,1:array} */
    private function validate(): array
    {
        $r = $this->request;
        $errors = [];
        $q = $r->str('question', 300);
        $a = HtmlSanitizer::clean((string) $r->input('answer', ''), 10000);
        if ($q === '') {
            $errors['question'] = 'Bitte die Frage eingeben.';
        }
        if (trim(strip_tags($a)) === '') {
            $errors['answer'] = 'Bitte eine Antwort eingeben.';
        }
        $cat = $r->int('category_id');
        if ($cat && !DB::value('SELECT id FROM faq_categories WHERE id = ?', [$cat])) {
            $cat = 0;
        }
        return [['question' => $q, 'answer' => $a, 'category_id' => $cat ?: null, 'status' => $r->str('status', 12) === 'published' ? 'published' : 'draft'], $errors];
    }
}
