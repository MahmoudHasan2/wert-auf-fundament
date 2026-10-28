<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\ErrorHandler;
use App\Core\View;
use App\Repositories\BlockRepository;

final class BlockRenderer
{
    public static function render(array $blocks, array $ctx = []): string
    {
        $mediaIds = [];
        foreach ($blocks as $b) {
            $def = BlockTypes::get($b['type']);
            if ($def) {
                $mediaIds = array_merge($mediaIds, FieldForm::mediaIds($def['fields'], $b['data']));
            }
        }
        Media::preload($mediaIds);
        $ctx['index'] = 0;
        $html = '';
        foreach ($blocks as $b) {
            $out = self::one($b, $ctx);
            if ($out !== '') {
                $html .= $out;
                $ctx['index']++;
            }
        }
        return $html;
    }

    public static function one(array $block, array $ctx, int $depth = 0): string
    {
        $preview = !empty($ctx['preview']);
        if (!(int) $block['is_visible'] && !$preview) {
            return '';
        }
        $def = BlockTypes::get($block['type']);
        if (!$def) {
            return '';
        }
        if ($block['type'] === 'library_ref') {
            $lib = $depth === 0 ? BlockRepository::find((int) ($block['data']['library'] ?? 0)) : null;
            if (!$lib || $lib['type'] === 'library_ref' || (!(int) $lib['is_visible'] && !$preview)) {
                return '';
            }
            return self::one($lib, $ctx, $depth + 1);
        }
        $data = $block['data'] + FieldForm::defaults($def['fields']);
        try {
            $html = View::partial('blocks/' . $block['type'], ['d' => $data, 'block' => $block, 'ctx' => $ctx]);
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            return $preview ? '<div class="preview-note">Block #' . (int) $block['id'] . ' konnte nicht dargestellt werden.</div>' : '';
        }
        if ($preview && !(int) $block['is_visible'] && trim($html) !== '') {
            $html = '<div class="preview-hidden"><span class="preview-hidden__badge">Ausgeblendet – nur in der Vorschau sichtbar</span>' . $html . '</div>';
        }
        return $html;
    }

    /** Builds the CSS class list for a block section. */
    public static function sectionClass(string $base, array $d): string
    {
        $bg = in_array($d['bg'] ?? 'white', ['white', 'sand', 'dark'], true) ? ($d['bg'] ?? 'white') : 'white';
        return 'section ' . $base . ' section--' . $bg;
    }
}
