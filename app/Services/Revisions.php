<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Repositories\BlockRepository;
use App\Security\Auth;

/** Version history for pages (incl. Impressum/Datenschutz): snapshot of page fields + all blocks. */
final class Revisions
{
    public const KEEP = 40;
    private const PAGE_FIELDS = ['title', 'template', 'meta_title', 'meta_description', 'og_media_id', 'noindex'];

    public static function snapshot(int $pageId, string $note): void
    {
        $page = DB::row('SELECT * FROM pages WHERE id = ?', [$pageId]);
        if (!$page) {
            return;
        }
        $blocks = array_map(fn ($b) => [
            'type' => $b['type'], 'data' => $b['data'], 'is_visible' => (int) $b['is_visible'], 'admin_note' => $b['admin_note'],
        ], BlockRepository::forOwner('page', $pageId, true));
        $snapshot = ['page' => array_intersect_key($page, array_flip(self::PAGE_FIELDS)), 'blocks' => $blocks];
        DB::insert('page_revisions', [
            'page_id' => $pageId,
            'admin_id' => Auth::id(),
            'note' => mb_substr($note, 0, 190),
            'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
        $keepFrom = DB::value('SELECT id FROM page_revisions WHERE page_id = ? ORDER BY id DESC LIMIT 1 OFFSET ' . (self::KEEP - 1), [$pageId]);
        if ($keepFrom) {
            DB::exec('DELETE FROM page_revisions WHERE page_id = ? AND id < ?', [$pageId, (int) $keepFrom]);
        }
    }

    public static function forPage(int $pageId): array
    {
        return DB::all('SELECT r.id, r.note, r.created_at, a.name AS admin_name FROM page_revisions r LEFT JOIN admins a ON a.id = r.admin_id WHERE r.page_id = ? ORDER BY r.id DESC', [$pageId]);
    }

    public static function restore(int $revisionId): int
    {
        $rev = DB::row('SELECT * FROM page_revisions WHERE id = ?', [$revisionId]) ?? throw new \RuntimeException('Version nicht gefunden.');
        $snap = json_decode((string) $rev['snapshot'], true);
        if (!is_array($snap) || !isset($snap['page'], $snap['blocks'])) {
            throw new \RuntimeException('Version ist beschädigt.');
        }
        $pageId = (int) $rev['page_id'];
        DB::transaction(function () use ($pageId, $snap) {
            $fields = array_intersect_key($snap['page'], array_flip(self::PAGE_FIELDS));
            if (!empty($fields['og_media_id']) && !DB::value('SELECT id FROM media WHERE id = ?', [(int) $fields['og_media_id']])) {
                $fields['og_media_id'] = null;
            }
            if ($fields) {
                DB::update('pages', $fields, 'id = :id', ['id' => $pageId]);
            }
            DB::exec('DELETE FROM content_blocks WHERE page_id = ?', [$pageId]);
            foreach ($snap['blocks'] as $b) {
                if (!BlockTypes::get((string) $b['type'])) {
                    continue;
                }
                BlockRepository::create('page', $pageId, $b['type'], (array) $b['data'], null, (bool) $b['is_visible'], (string) ($b['admin_note'] ?? ''));
            }
        });
        return $pageId;
    }
}
