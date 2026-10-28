<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/**
 * Media library: upload validation, image re-encoding, responsive variants, usage tracking.
 *
 * Security model for uploads:
 *  - extension allow-list (jpg, jpeg, png, webp) AND finfo MIME check AND getimagesize() AND successful GD decode
 *  - the original file is NEVER stored; every image is re-encoded by GD (strips metadata & polyglot payloads)
 *  - server-generated random filenames; the uploads directory refuses to execute scripts (.htaccess)
 *  - SVG is not accepted
 */
final class Media
{
    public const SIZES = ['sm' => 480, 'md' => 960, 'lg' => 1600, 'full' => 2400];
    private const TYPES = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    private static array $cache = [];

    public static function find(?int $id): ?array
    {
        if (!$id) {
            return null;
        }
        if (!array_key_exists($id, self::$cache)) {
            self::$cache[$id] = DB::row('SELECT * FROM media WHERE id = ?', [$id]);
        }
        return self::$cache[$id];
    }

    /** Preloads several media rows with one query. */
    public static function preload(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($i) => $i > 0 && !array_key_exists($i, self::$cache))));
        if (!$ids) {
            return;
        }
        foreach (DB::all('SELECT * FROM media WHERE id IN (' . DB::placeholders($ids) . ')', $ids) as $row) {
            self::$cache[(int) $row['id']] = $row;
        }
        foreach ($ids as $id) {
            self::$cache[$id] ??= null;
        }
    }

    public static function forget(int $id): void
    {
        unset(self::$cache[$id]);
    }

    /** @return array<string,int> variant => width */
    public static function variants(array $m): array
    {
        $out = [];
        foreach (explode(',', (string) $m['sizes']) as $pair) {
            [$k, $w] = array_pad(explode(':', $pair), 2, 0);
            if ($k !== '') {
                $out[$k] = (int) $w;
            }
        }
        return $out;
    }

    public static function url(?array $m, string $size = 'lg'): string
    {
        if (!$m) {
            return '';
        }
        $variants = self::variants($m);
        if (!isset($variants[$size])) {
            $size = array_key_last($variants) ?? 'sm';
            // Prefer the largest variant not exceeding the requested one.
        }
        return url('/uploads/' . $m['path'] . '-' . $size . '.' . $m['ext']);
    }

    public static function absUrl(?array $m, string $size = 'lg'): string
    {
        return $m ? abs_url('/uploads/' . $m['path'] . '-' . (isset(self::variants($m)[$size]) ? $size : array_key_last(self::variants($m))) . '.' . $m['ext']) : '';
    }

    /**
     * Responsive <img>. $sizes is the HTML sizes attribute.
     */
    public static function img(?int $id, string $sizes = '100vw', string $class = '', bool $lazy = true, ?string $alt = null, string $default = 'md'): string
    {
        $m = self::find($id);
        if (!$m) {
            return '';
        }
        $variants = self::variants($m);
        $srcset = [];
        foreach ($variants as $k => $w) {
            $srcset[] = e(url('/uploads/' . $m['path'] . '-' . $k . '.' . $m['ext'])) . ' ' . $w . 'w';
        }
        $src = isset($variants[$default]) ? $default : (string) array_key_last($variants);
        $w = $variants[$src] ?? (int) $m['width'];
        $h = (int) round($w * ((int) $m['height'] / max(1, (int) $m['width'])));
        return sprintf(
            '<img src="%s" srcset="%s" sizes="%s" width="%d" height="%d" alt="%s"%s%s decoding="async">',
            e(url('/uploads/' . $m['path'] . '-' . $src . '.' . $m['ext'])),
            implode(', ', $srcset),
            e($sizes),
            $w,
            $h,
            e($alt ?? $m['alt']),
            $class !== '' ? ' class="' . e($class) . '"' : '',
            $lazy ? ' loading="lazy"' : ' fetchpriority="high"'
        );
    }

    public static function credit(?int $id): string
    {
        $m = self::find($id);
        if (!$m || $m['credit'] === '' || Settings::get('show_media_credits', '1') !== '1') {
            return '';
        }
        return 'Foto: ' . $m['credit'] . ($m['license'] !== '' ? ' (' . $m['license'] . ')' : '');
    }

    // ------------------------------------------------------------------ upload / processing

    /**
     * Validates an entry of $_FILES and stores it. Returns the new media id.
     * @throws MediaException
     */
    public static function upload(array $file, ?int $adminId, array $meta = []): int
    {
        $tmp = self::validateUpload($file);
        $data = self::process($tmp, (string) $file['name']);
        return self::insert($data, $adminId, $meta);
    }

    /** Imports a local file (used by the installer seed). */
    public static function importLocal(string $path, ?int $adminId, array $meta = []): int
    {
        if (!is_file($path)) {
            throw new MediaException('Datei nicht gefunden.');
        }
        self::validateFile($path, basename($path), (int) filesize($path));
        $data = self::process($path, basename($path));
        return self::insert($data, $adminId, $meta);
    }

    /** Replaces the image file of an existing media item. All references keep working (same id). */
    public static function replace(int $id, array $file): void
    {
        $old = self::find($id);
        if (!$old) {
            throw new MediaException('Medium nicht gefunden.');
        }
        $tmp = self::validateUpload($file);
        $data = self::process($tmp, (string) $file['name']);
        try {
            DB::update('media', [
                'path' => $data['path'], 'ext' => $data['ext'], 'sizes' => $data['sizes'], 'mime' => $data['mime'],
                'bytes' => $data['bytes'], 'width' => $data['width'], 'height' => $data['height'],
                'original_name' => $data['original_name'], 'is_placeholder' => 0, 'credit' => '', 'license' => '', 'source_url' => '',
            ], 'id = :id', ['id' => $id]);
        } catch (\Throwable $e) {
            self::deleteFiles($data);
            throw $e;
        }
        self::deleteFiles($old);
        self::forget($id);
    }

    private static function insert(array $data, ?int $adminId, array $meta): int
    {
        try {
            return DB::insert('media', $data + [
                'alt'            => mb_substr(trim((string) ($meta['alt'] ?? '')), 0, 255),
                'caption'        => mb_substr(trim((string) ($meta['caption'] ?? '')), 0, 255),
                'credit'         => mb_substr(trim((string) ($meta['credit'] ?? '')), 0, 255),
                'license'        => mb_substr(trim((string) ($meta['license'] ?? '')), 0, 80),
                'source_url'     => mb_substr(trim((string) ($meta['source_url'] ?? '')), 0, 500),
                'is_placeholder' => !empty($meta['is_placeholder']) ? 1 : 0,
                'uploaded_by'    => $adminId,
            ]);
        } catch (\Throwable $e) {
            self::deleteFiles($data);
            throw $e;
        }
    }

    private static function validateUpload(array $file): string
    {
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            throw new MediaException(match ($err) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß (Server-Limit: ' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise hochgeladen. Bitte erneut versuchen.',
                UPLOAD_ERR_NO_FILE => 'Bitte wählen Sie eine Datei aus.',
                default => 'Der Upload ist fehlgeschlagen (Serverfehler ' . $err . ').',
            });
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($tmp) && !(defined('WAF_TESTING') && is_file($tmp))) {
            throw new MediaException('Ungültiger Upload.');
        }
        self::validateFile($tmp, (string) ($file['name'] ?? ''), (int) ($file['size'] ?? 0));
        return $tmp;
    }

    private static function validateFile(string $path, string $name, int $size): void
    {
        $max = (int) config('uploads.max_bytes', 10485760);
        $size = max($size, (int) @filesize($path));
        if ($size <= 0) {
            throw new MediaException('Die Datei ist leer.');
        }
        if ($size > $max) {
            throw new MediaException(sprintf('Die Datei ist zu groß (maximal %d MB).', (int) round($max / 1048576)));
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$ext])) {
            throw new MediaException('Dateityp nicht erlaubt. Erlaubt sind JPG, PNG und WebP.');
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime, self::TYPES[$ext], true)) {
            throw new MediaException('Der Dateiinhalt passt nicht zur Dateiendung oder ist kein unterstütztes Bild.');
        }
        $info = @getimagesize($path);
        if ($info === false || !in_array($info['mime'] ?? '', self::TYPES[$ext], true)) {
            throw new MediaException('Die Datei ist kein gültiges Bild.');
        }
        [$w, $h] = $info;
        $maxSide = (int) config('uploads.max_side', 9000);
        if ($w < 16 || $h < 16 || $w > $maxSide || $h > $maxSide || $w * $h > (int) config('uploads.max_pixels', 40000000)) {
            throw new MediaException(sprintf('Ungültige Bildabmessungen (%d × %d px). Erlaubt: 16 bis %d px Kantenlänge, max. 40 Megapixel.', $w, $h, $maxSide));
        }
    }

    /** Decodes, auto-rotates and re-encodes into variants. @return array media row fields */
    private static function process(string $path, string $originalName): array
    {
        $info = getimagesize($path);
        $mime = $info['mime'] ?? '';
        @ini_set('memory_limit', '512M');
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default      => false,
        };
        if (!$img instanceof \GdImage) {
            throw new MediaException('Das Bild konnte nicht verarbeitet werden (beschädigt oder nicht unterstützt).');
        }
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $img = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => imagerotate($img, 180, 0) ?: $img,
                6 => imagerotate($img, -90, 0) ?: $img,
                8 => imagerotate($img, 90, 0) ?: $img,
                default => $img,
            };
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $hasAlpha = $mime !== 'image/jpeg';

        $useWebp = function_exists('imagewebp');
        $ext = $useWebp ? 'webp' : ($hasAlpha ? 'png' : 'jpg');

        $dir = date('Y/m');
        $abs = public_path('uploads/' . $dir);
        if (!is_dir($abs) && !mkdir($abs, 0755, true) && !is_dir($abs)) {
            throw new MediaException('Upload-Verzeichnis ist nicht beschreibbar.');
        }
        $name = bin2hex(random_bytes(12));
        $base = $dir . '/' . $name;

        $sizes = [];
        $bytes = 0;
        $written = [];
        try {
            foreach (self::SIZES as $key => $target) {
                $tw = min($target, $w);
                if ($sizes && end($sizes) >= $tw) {
                    break; // image smaller than this step – no upscaling, no duplicates
                }
                $th = max(1, (int) round($h * $tw / $w));
                $canvas = imagecreatetruecolor($tw, $th);
                if ($hasAlpha) {
                    imagealphablending($canvas, false);
                    imagesavealpha($canvas, true);
                    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
                }
                imagecopyresampled($canvas, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
                $file = public_path('uploads/' . $base . '-' . $key . '.' . $ext);
                $ok = match ($ext) {
                    'webp' => imagewebp($canvas, $file, 80),
                    'png'  => imagepng($canvas, $file, 7),
                    default => imagejpeg($canvas, $file, 82),
                };
                imagedestroy($canvas);
                if (!$ok) {
                    throw new MediaException('Das Bild konnte nicht gespeichert werden.');
                }
                $written[] = $file;
                $sizes[$key] = $tw;
                $bytes += (int) filesize($file);
            }
        } catch (\Throwable $e) {
            foreach ($written as $f) {
                @unlink($f);
            }
            throw $e;
        } finally {
            imagedestroy($img);
        }

        $pairs = [];
        foreach ($sizes as $k => $v) {
            $pairs[] = $k . ':' . $v;
        }
        $largest = end($sizes);
        return [
            'path' => $base,
            'ext' => $ext,
            'sizes' => implode(',', $pairs),
            'original_name' => mb_substr(preg_replace('/[^\pL\pN._ -]/u', '', $originalName) ?: 'bild', 0, 190),
            'mime' => 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext),
            'bytes' => $bytes,
            'width' => $largest,
            'height' => (int) round($h * $largest / $w),
        ];
    }

    private static function deleteFiles(array $m): void
    {
        foreach (array_keys(self::variants($m)) as $k) {
            $file = public_path('uploads/' . $m['path'] . '-' . $k . '.' . $m['ext']);
            // Paths come from the DB and are generated by us; still guard against traversal.
            if (preg_match('#^\d{4}/\d{2}/[a-f0-9]{24}$#', (string) $m['path']) && is_file($file)) {
                @unlink($file);
            }
        }
    }

    // ------------------------------------------------------------------ usage / delete

    /** @return array<int, array{label:string, url:string}> */
    public static function usage(int $id): array
    {
        $out = [];
        foreach (DB::all('SELECT b.id, b.page_id, b.service_id, b.library_name, p.title AS page_title, s.title AS service_title
                FROM content_block_media cbm JOIN content_blocks b ON b.id = cbm.block_id
                LEFT JOIN pages p ON p.id = b.page_id LEFT JOIN services s ON s.id = b.service_id
                WHERE cbm.media_id = ?', [$id]) as $r) {
            $label = $r['page_id'] ? 'Seite „' . $r['page_title'] . '“' : ($r['service_id'] ? 'Leistung „' . $r['service_title'] . '“' : 'Baustein „' . $r['library_name'] . '“');
            $out[] = ['label' => $label . ' (Inhaltsblock)', 'url' => '/admin/blocks/' . $r['id']];
        }
        foreach (DB::all('SELECT id, title FROM services WHERE image_media_id = ?', [$id]) as $r) {
            $out[] = ['label' => 'Leistung „' . $r['title'] . '“ (Bild)', 'url' => '/admin/services/' . $r['id']];
        }
        foreach (DB::all('SELECT id, title FROM projects WHERE cover_media_id = ?', [$id]) as $r) {
            $out[] = ['label' => 'Referenz „' . $r['title'] . '“ (Titelbild)', 'url' => '/admin/projects/' . $r['id']];
        }
        foreach (DB::all('SELECT DISTINCT g.id, g.name FROM gallery_items gi JOIN galleries g ON g.id = gi.gallery_id WHERE gi.media_id = ?', [$id]) as $r) {
            $out[] = ['label' => 'Galerie „' . $r['name'] . '“', 'url' => '/admin/galleries/' . $r['id']];
        }
        foreach (DB::all('SELECT id, title FROM pages WHERE og_media_id = ?', [$id]) as $r) {
            $out[] = ['label' => 'Seite „' . $r['title'] . '“ (Vorschaubild)', 'url' => '/admin/pages/' . $r['id']];
        }
        foreach (['logo_media_id' => 'Logo', 'logo_light_media_id' => 'Logo (hell)', 'favicon_media_id' => 'Favicon', 'seo_og_media_id' => 'Standard-Vorschaubild'] as $key => $label) {
            if ((int) Settings::get($key) === $id) {
                $out[] = ['label' => 'Einstellungen: ' . $label, 'url' => '/admin/settings'];
            }
        }
        return $out;
    }

    public static function delete(int $id): void
    {
        $m = self::find($id);
        if (!$m) {
            throw new MediaException('Medium nicht gefunden.');
        }
        if (self::usage($id)) {
            throw new MediaException('Das Bild wird noch verwendet und kann nicht gelöscht werden.');
        }
        DB::exec('DELETE FROM media WHERE id = ?', [$id]);
        self::deleteFiles($m);
        self::forget($id);
    }
}
