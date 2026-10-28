<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\DB;

/** Small lookups for galleries and FAQs. */
final class ContentRepository
{
    public static function galleryItems(int $galleryId): array
    {
        return DB::all('SELECT gi.*, m.alt, m.caption AS media_caption FROM gallery_items gi JOIN media m ON m.id = gi.media_id WHERE gi.gallery_id = ? ORDER BY gi.sort_order, gi.id', [$galleryId]);
    }

    public static function galleries(): array
    {
        return DB::all('SELECT g.*, (SELECT COUNT(*) FROM gallery_items gi WHERE gi.gallery_id = g.id) AS item_count FROM galleries g ORDER BY g.name');
    }

    public static function faqs(int $categoryId = 0): array
    {
        if ($categoryId > 0) {
            return DB::all("SELECT * FROM faqs WHERE status = 'published' AND category_id = ? ORDER BY sort_order, id", [$categoryId]);
        }
        return DB::all("SELECT * FROM faqs WHERE status = 'published' ORDER BY sort_order, id");
    }

    public static function faqCategories(): array
    {
        return DB::all('SELECT * FROM faq_categories ORDER BY sort_order, name');
    }
}
