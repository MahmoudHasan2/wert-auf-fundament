<?php
declare(strict_types=1);

namespace App\Security;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitizer for rich-text fields.
 * Only basic formatting tags survive; all attributes except a validated a[href] are removed.
 * Dangerous elements (script, style, iframe, svg …) are dropped together with their content.
 */
final class HtmlSanitizer
{
    private const ALLOWED = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a'];
    private const RENAME = ['b' => 'strong', 'i' => 'em', 'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'];
    private const DROP = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript',
        'form', 'input', 'button', 'select', 'textarea', 'link', 'meta', 'base', 'frame', 'frameset', 'applet', 'video', 'audio', 'img', 'picture', 'source', 'canvas', 'head', 'title'];

    public static function clean(?string $html, int $maxLength = 60000): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }
        $html = mb_substr($html, 0, $maxLength);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="__root">' . $html . '</div></body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('__root');
        if (!$root) {
            return '';
        }
        self::walk($root, $doc);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        // Normalise empty paragraphs produced by editors.
        $out = preg_replace('#<p>(\s|&nbsp;|<br>)*</p>#u', '', $out) ?? $out;
        return trim($out);
    }

    private static function walk(DOMNode $node, DOMDocument $doc): void
    {
        // Iterate over a static copy because we modify the tree.
        $children = iterator_to_array($node->childNodes);
        foreach ($children as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);
                continue;
            }
            if (isset(self::RENAME[$tag])) {
                $new = $doc->createElement(self::RENAME[$tag]);
                while ($child->firstChild) {
                    $new->appendChild($child->firstChild);
                }
                $node->replaceChild($new, $child);
                $child = $new;
                $tag = $new->tagName;
            }
            self::walk($child, $doc);
            if (!in_array($tag, self::ALLOWED, true)) {
                // Unknown wrapper (div, span, font …): keep the content, drop the element.
                if (in_array($tag, ['div', 'section', 'article'], true) && $child->childNodes->length > 0) {
                    $p = $doc->createElement('p');
                    while ($child->firstChild) {
                        $p->appendChild($child->firstChild);
                    }
                    $node->replaceChild($p, $child);
                } else {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                }
                continue;
            }
            $href = $tag === 'a' ? trim($child->getAttribute('href')) : '';
            foreach (iterator_to_array($child->attributes) as $attr) {
                $child->removeAttribute($attr->nodeName);
            }
            if ($tag === 'a') {
                if (!self::safeHref($href)) {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                $child->setAttribute('href', $href);
                if (preg_match('#^https?://#i', $href)) {
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }
    }

    public static function safeHref(string $href): bool
    {
        if ($href === '') {
            return false;
        }
        // Remove control characters/whitespace browsers ignore before checking the scheme.
        $normalised = preg_replace('/[\x00-\x20]/', '', html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '';
        if ($normalised === '') {
            return false;
        }
        if (preg_match('#^(https?:|mailto:|tel:)#i', $normalised)) {
            return filter_var($normalised, FILTER_VALIDATE_URL) !== false || !preg_match('#^https?:#i', $normalised);
        }
        if (($normalised[0] === '/' && !str_starts_with($normalised, '//')) || $normalised[0] === '#') {
            return !str_contains($normalised, '\\');
        }
        return false;
    }

    /** Plain text from rich text (for excerpts / meta descriptions). */
    public static function toText(?string $html): string
    {
        return trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</li>'], ["\n", "\n", "\n"], (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
