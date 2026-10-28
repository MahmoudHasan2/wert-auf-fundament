<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\DB;
use App\Services\BlockTypes;
use App\Services\FieldForm;

/**
 * Content blocks belong to exactly one owner: a page, a service, or the reusable library.
 */
final class BlockRepository
{
    public const OWNERS = ['page' => 'page_id', 'service' => 'service_id'];

    public static function decode(array $row): array
    {
        $row['data'] = json_decode((string) $row['data'], true) ?: [];
        return $row;
    }

    public static function find(int $id): ?array
    {
        $r = DB::row('SELECT * FROM content_blocks WHERE id = ?', [$id]);
        return $r ? self::decode($r) : null;
    }

    public static function forOwner(string $owner, int $ownerId, bool $includeHidden = false): array
    {
        $col = self::OWNERS[$owner] ?? throw new \InvalidArgumentException('owner');
        $rows = DB::all("SELECT * FROM content_blocks WHERE $col = ?" . ($includeHidden ? '' : ' AND is_visible = 1') . ' ORDER BY sort_order, id', [$ownerId]);
        return array_map([self::class, 'decode'], $rows);
    }

    public static function library(): array
    {
        return array_map([self::class, 'decode'], DB::all('SELECT * FROM content_blocks WHERE library_name IS NOT NULL ORDER BY library_name'));
    }

    /** @return array{0:string,1:?int} owner type & id */
    public static function owner(array $block): array
    {
        if ($block['page_id']) {
            return ['page', (int) $block['page_id']];
        }
        if ($block['service_id']) {
            return ['service', (int) $block['service_id']];
        }
        return ['library', null];
    }

    public static function create(string $owner, ?int $ownerId, string $type, array $data, ?string $libraryName = null, bool $visible = true, string $note = ''): int
    {
        if (!BlockTypes::get($type)) {
            throw new \InvalidArgumentException('Unknown block type');
        }
        return DB::transaction(function () use ($owner, $ownerId, $type, $data, $libraryName, $visible, $note) {
            $row = ['type' => $type, 'data' => self::encode($data), 'is_visible' => $visible ? 1 : 0, 'admin_note' => mb_substr($note, 0, 500)];
            if ($owner === 'library') {
                $row['library_name'] = mb_substr((string) $libraryName, 0, 160);
                $row['sort_order'] = 0;
            } else {
                $col = self::OWNERS[$owner];
                $row[$col] = $ownerId;
                $row['sort_order'] = (int) DB::value("SELECT COALESCE(MAX(sort_order), 0) + 10 FROM content_blocks WHERE $col = ?", [$ownerId]);
            }
            $id = DB::insert('content_blocks', $row);
            self::syncMedia($id, $type, $data);
            return $id;
        });
    }

    public static function update(int $id, array $data, bool $visible, string $note, ?string $libraryName = null): void
    {
        $block = self::find($id) ?? throw new \RuntimeException('Block not found');
        DB::transaction(function () use ($block, $id, $data, $visible, $note, $libraryName) {
            $row = ['data' => self::encode($data), 'is_visible' => $visible ? 1 : 0, 'admin_note' => mb_substr($note, 0, 500)];
            if ($block['library_name'] !== null && $libraryName !== null && $libraryName !== '') {
                $row['library_name'] = mb_substr($libraryName, 0, 160);
            }
            DB::update('content_blocks', $row, 'id = :id', ['id' => $id]);
            self::syncMedia($id, $block['type'], $data);
        });
    }

    public static function setVisible(int $id, bool $visible): void
    {
        DB::update('content_blocks', ['is_visible' => $visible ? 1 : 0], 'id = :id', ['id' => $id]);
    }

    /** Swap with the neighbour above (-1) or below (+1). */
    public static function move(int $id, int $dir): void
    {
        $b = self::find($id);
        if (!$b || $b['library_name'] !== null) {
            return;
        }
        [$owner, $ownerId] = self::owner($b);
        $ids = array_map('intval', DB::column('SELECT id FROM content_blocks WHERE ' . self::OWNERS[$owner] . ' = ? ORDER BY sort_order, id', [$ownerId]));
        $pos = array_search($id, $ids, true);
        $swap = $pos + ($dir < 0 ? -1 : 1);
        if ($pos === false || $swap < 0 || $swap >= count($ids)) {
            return;
        }
        [$ids[$pos], $ids[$swap]] = [$ids[$swap], $ids[$pos]];
        self::writeOrder($ids);
    }

    /** Applies an explicit order (used by drag & drop). Only ids of that owner are accepted. */
    public static function reorder(string $owner, int $ownerId, array $orderedIds): void
    {
        $existing = array_map('intval', DB::column('SELECT id FROM content_blocks WHERE ' . self::OWNERS[$owner] . ' = ? ORDER BY sort_order, id', [$ownerId]));
        $ordered = array_values(array_intersect(array_map('intval', $orderedIds), $existing));
        $rest = array_values(array_diff($existing, $ordered));
        self::writeOrder(array_merge($ordered, $rest));
    }

    private static function writeOrder(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach ($ids as $i => $bid) {
                DB::exec('UPDATE content_blocks SET sort_order = ? WHERE id = ?', [($i + 1) * 10, $bid]);
            }
        });
    }

    public static function duplicate(int $id): int
    {
        $b = self::find($id) ?? throw new \RuntimeException('Block not found');
        [$owner, $ownerId] = self::owner($b);
        if ($owner === 'library') {
            return self::create('library', null, $b['type'], $b['data'], $b['library_name'] . ' (Kopie)', (bool) $b['is_visible'], $b['admin_note']);
        }
        return DB::transaction(function () use ($b, $owner, $ownerId) {
            $col = self::OWNERS[$owner];
            DB::exec("UPDATE content_blocks SET sort_order = sort_order + 10 WHERE $col = ? AND sort_order > ?", [$ownerId, $b['sort_order']]);
            $newId = DB::insert('content_blocks', [
                $col => $ownerId, 'type' => $b['type'], 'data' => self::encode($b['data']), 'sort_order' => (int) $b['sort_order'] + 10,
                'is_visible' => 0, 'admin_note' => $b['admin_note'],
            ]);
            self::syncMedia($newId, $b['type'], $b['data']);
            return $newId;
        });
    }

    /** Library blocks: where are they referenced? */
    public static function libraryUsage(int $libraryId): array
    {
        $rows = DB::all("SELECT b.id, b.page_id, b.service_id, p.title AS page_title, s.title AS service_title FROM content_blocks b
            LEFT JOIN pages p ON p.id = b.page_id LEFT JOIN services s ON s.id = b.service_id
            WHERE b.type = 'library_ref'");
        $out = [];
        foreach ($rows as $r) {
            $b = self::find((int) $r['id']);
            if ((int) ($b['data']['library'] ?? 0) === $libraryId) {
                $out[] = $r['page_id'] ? ['label' => 'Seite „' . $r['page_title'] . '“', 'url' => '/admin/pages/' . $r['page_id']]
                    : ['label' => 'Leistung „' . $r['service_title'] . '“', 'url' => '/admin/services/' . $r['service_id']];
            }
        }
        return $out;
    }

    public static function delete(int $id): void
    {
        $b = self::find($id);
        if (!$b) {
            return;
        }
        if ($b['library_name'] !== null && self::libraryUsage($id)) {
            throw new \DomainException('Dieser Baustein wird noch auf Seiten verwendet. Entfernen Sie zuerst die Verweise.');
        }
        DB::exec('DELETE FROM content_blocks WHERE id = ?', [$id]);
    }

    public static function syncMedia(int $blockId, string $type, array $data): void
    {
        $def = BlockTypes::get($type);
        $ids = $def ? FieldForm::mediaIds($def['fields'], $data) : [];
        DB::exec('DELETE FROM content_block_media WHERE block_id = ?', [$blockId]);
        foreach ($ids as $mid) {
            DB::exec('INSERT IGNORE INTO content_block_media (block_id, media_id) SELECT ?, id FROM media WHERE id = ?', [$blockId, $mid]);
        }
    }

    public static function encode(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
