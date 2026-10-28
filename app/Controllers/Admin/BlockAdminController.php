<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\HttpException;
use App\Core\Response;
use App\Repositories\BlockRepository;
use App\Repositories\PageRepository;
use App\Repositories\ServiceRepository;
use App\Services\BlockTypes;
use App\Services\FieldForm;
use App\Services\Revisions;

final class BlockAdminController extends AdminController
{
    public function add(): Response
    {
        $owner = $this->request->str('owner', 10);
        $ownerId = $this->request->int('owner_id');
        $back = $this->ownerUrl($owner, $ownerId);
        $this->guardOwner($owner, $ownerId);

        $libraryId = $this->request->int('library_id');
        if ($libraryId > 0) {
            $lib = BlockRepository::find($libraryId);
            if (!$lib || $lib['library_name'] === null || $lib['type'] === 'library_ref') {
                return $this->redirect($back, 'error', 'Baustein nicht gefunden.');
            }
            $id = BlockRepository::create($owner, $ownerId, 'library_ref', ['library' => $libraryId]);
            $this->afterChange($owner, $ownerId, 'Baustein „' . $lib['library_name'] . '“ eingefügt');
            return $this->redirect($back . '#block-' . $id, 'success', 'Der Baustein „' . $lib['library_name'] . '“ wurde eingefügt.');
        }

        $type = $this->request->str('type', 40);
        $def = BlockTypes::get($type);
        if (!$def || $type === 'library_ref') {
            return $this->redirect($back, 'error', 'Bitte einen Blocktyp auswählen.');
        }
        $data = FieldForm::defaults($def['fields']);
        $id = BlockRepository::create($owner, $ownerId, $type, $data, null, false);
        $this->afterChange($owner, $ownerId, 'Block „' . $def['label'] . '“ hinzugefügt');
        return $this->redirect('/admin/blocks/' . $id, 'info', 'Neuer Block angelegt. Er ist ausgeblendet, bis Sie ihn beim Speichern sichtbar schalten.');
    }

    public function edit(int $id): Response
    {
        $block = $this->findOr404(BlockRepository::find($id));
        [$owner, $ownerId] = BlockRepository::owner($block);
        $this->guardOwner($owner, $ownerId);
        $def = BlockTypes::get($block['type']) ?? throw HttpException::notFound();
        return $this->view('blocks/edit', [
            'block' => $block,
            'def' => $def,
            'owner' => $owner,
            'ownerRow' => $owner === 'page' ? PageRepository::find((int) $ownerId) : ($owner === 'service' ? ServiceRepository::find((int) $ownerId) : null),
            'back' => $this->ownerUrl($owner, $ownerId),
            'usage' => $owner === 'library' ? BlockRepository::libraryUsage($id) : [],
        ], 'Block bearbeiten: ' . $def['label']);
    }

    public function update(int $id): Response
    {
        $block = $this->findOr404(BlockRepository::find($id));
        [$owner, $ownerId] = BlockRepository::owner($block);
        $this->guardOwner($owner, $ownerId);
        $def = BlockTypes::get($block['type']) ?? throw HttpException::notFound();
        [$data, $errors] = FieldForm::parse($def['fields'], $this->request->arr('data'));
        $libraryName = $owner === 'library' ? $this->request->str('library_name', 160) : null;
        if ($owner === 'library' && $libraryName === '') {
            $errors['library_name'] = 'Bitte einen Namen für den Baustein angeben.';
        }
        if ($errors) {
            return $this->invalid('/admin/blocks/' . $id, $errors);
        }
        BlockRepository::update($id, $data, $this->request->bool('is_visible'), $this->request->str('admin_note', 500), $libraryName);
        $this->afterChange($owner, $ownerId, 'Block „' . $def['label'] . '“ bearbeitet');
        $this->audit('block.updated', 'block', $id, 'Block „' . $def['label'] . '“ bearbeitet' . $this->ownerLabel($owner, $ownerId));
        if ($this->request->str('after', 10) === 'back') {
            return $this->redirect($this->ownerUrl($owner, $ownerId) . '#block-' . $id, 'success', 'Der Block wurde gespeichert.');
        }
        return $this->redirect('/admin/blocks/' . $id, 'success', 'Der Block wurde gespeichert.');
    }

    public function move(int $id): Response
    {
        $block = $this->findOr404(BlockRepository::find($id));
        [$owner, $ownerId] = BlockRepository::owner($block);
        $this->guardOwner($owner, $ownerId);
        BlockRepository::move($id, $this->request->str('dir', 4) === 'up' ? -1 : 1);
        $this->afterChange($owner, $ownerId, 'Reihenfolge geändert');
        return $this->redirect($this->ownerUrl($owner, $ownerId) . '#block-' . $id);
    }

    public function reorder(): Response
    {
        $owner = $this->request->str('owner', 10);
        $ownerId = $this->request->int('owner_id');
        if (!isset(BlockRepository::OWNERS[$owner])) {
            return Response::json(['error' => 'Ungültig'], 422);
        }
        $this->guardOwner($owner, $ownerId);
        BlockRepository::reorder($owner, $ownerId, array_map('intval', $this->request->arr('ids')));
        $this->afterChange($owner, $ownerId, 'Reihenfolge geändert');
        return Response::json(['ok' => true]);
    }

    public function toggle(int $id): Response
    {
        $block = $this->findOr404(BlockRepository::find($id));
        [$owner, $ownerId] = BlockRepository::owner($block);
        $this->guardOwner($owner, $ownerId);
        BlockRepository::setVisible($id, !(int) $block['is_visible']);
        $label = (BlockTypes::get($block['type'])['label'] ?? $block['type']);
        $this->afterChange($owner, $ownerId, 'Block „' . $label . '“ ' . ((int) $block['is_visible'] ? 'ausgeblendet' : 'eingeblendet'));
        $this->audit('block.visibility', 'block', $id, 'Block „' . $label . '“ ' . ((int) $block['is_visible'] ? 'ausgeblendet' : 'eingeblendet') . $this->ownerLabel($owner, $ownerId));
        return $this->redirect($this->ownerUrl($owner, $ownerId) . '#block-' . $id, 'success', (int) $block['is_visible'] ? 'Block ausgeblendet.' : 'Block ist jetzt sichtbar.');
    }

    public function duplicate(int $id): Response
    {
        $block = $this->findOr404(BlockRepository::find($id));
        [$owner, $ownerId] = BlockRepository::owner($block);
        $this->guardOwner($owner, $ownerId);
        $newId = BlockRepository::duplicate($id);
        $this->afterChange($owner, $ownerId, 'Block dupliziert');
        return $this->redirect($this->ownerUrl($owner, $ownerId) . '#block-' . $newId, 'success', 'Block dupliziert. Die Kopie ist zunächst ausgeblendet.');
    }

    public function delete(int $id): Response
    {
        $block = $this->findOr404(BlockRepository::find($id));
        [$owner, $ownerId] = BlockRepository::owner($block);
        $this->guardOwner($owner, $ownerId);
        try {
            BlockRepository::delete($id);
        } catch (\DomainException $e) {
            return $this->redirect('/admin/blocks/' . $id, 'error', $e->getMessage());
        }
        $label = BlockTypes::get($block['type'])['label'] ?? $block['type'];
        $this->afterChange($owner, $ownerId, 'Block „' . $label . '“ gelöscht');
        $this->audit('block.deleted', 'block', $id, 'Block „' . $label . '“ gelöscht' . $this->ownerLabel($owner, $ownerId));
        return $this->redirect($this->ownerUrl($owner, $ownerId), 'success', 'Der Block wurde gelöscht.');
    }

    public function library(): Response
    {
        $blocks = BlockRepository::library();
        $usage = [];
        foreach ($blocks as $b) {
            $usage[$b['id']] = count(BlockRepository::libraryUsage((int) $b['id']));
        }
        return $this->view('blocks/library', ['blocks' => $blocks, 'usage' => $usage], 'Wiederverwendbare Bausteine');
    }

    public function createLibrary(): Response
    {
        $type = $this->request->str('type', 40);
        $name = $this->request->str('library_name', 160);
        $def = BlockTypes::get($type);
        if (!$def || in_array($type, ['library_ref', 'contact_form'], true) || $name === '') {
            return $this->invalid('/admin/library', ['library_name' => 'Bitte Typ und Namen angeben.']);
        }
        $id = BlockRepository::create('library', null, $type, FieldForm::defaults($def['fields']), $name);
        $this->audit('library.created', 'block', $id, 'Baustein „' . $name . '“ angelegt');
        return $this->redirect('/admin/blocks/' . $id, 'success', 'Baustein angelegt.');
    }

    // ---------------------------------------------------------------- helpers

    private function guardOwner(string $owner, ?int $ownerId): void
    {
        if ($owner === 'page') {
            $page = PageRepository::find((int) $ownerId) ?? throw HttpException::notFound();
            if (PageRepository::isLegal($page)) {
                $this->requirePerm('legal');
            }
        } elseif ($owner === 'service') {
            ServiceRepository::find((int) $ownerId) ?? throw HttpException::notFound();
        } elseif ($owner !== 'library') {
            throw HttpException::notFound();
        }
    }

    private function ownerUrl(string $owner, ?int $ownerId): string
    {
        return match ($owner) {
            'page' => '/admin/pages/' . (int) $ownerId,
            'service' => '/admin/services/' . (int) $ownerId,
            default => '/admin/library',
        };
    }

    private function ownerLabel(string $owner, ?int $ownerId): string
    {
        if ($owner === 'page') {
            return ' (Seite „' . (PageRepository::find((int) $ownerId)['title'] ?? '') . '“)';
        }
        if ($owner === 'service') {
            return ' (Leistung „' . (ServiceRepository::find((int) $ownerId)['title'] ?? '') . '“)';
        }
        return ' (Bibliothek)';
    }

    private function afterChange(string $owner, ?int $ownerId, string $note): void
    {
        if ($owner === 'page') {
            DB::update('pages', ['updated_by' => $this->user['id'], 'updated_at' => now()], 'id = :id', ['id' => (int) $ownerId]);
            Revisions::snapshot((int) $ownerId, $note);
        } elseif ($owner === 'service') {
            DB::update('services', ['updated_at' => now()], 'id = :id', ['id' => (int) $ownerId]);
        }
    }
}
