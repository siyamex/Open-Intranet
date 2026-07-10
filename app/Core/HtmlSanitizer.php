<?php

declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;

/**
 * Allowlist HTML sanitizer for rich content (news bodies, comments).
 * Allowed: p, h2, h3, ul, ol, li, blockquote, a[href], img[src from our own
 * media routes], table/thead/tbody/tr/td/th, pre, code, strong, em, u, br.
 * All style and on* attributes are stripped; everything else is unwrapped.
 */
final class HtmlSanitizer
{
    private const ALLOWED = [
        'p' => [], 'h2' => [], 'h3' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'a' => ['href'], 'img' => ['src', 'alt'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'td' => ['colspan', 'rowspan'], 'th' => ['colspan', 'rowspan'],
        'pre' => [], 'code' => [], 'strong' => [], 'em' => [], 'u' => [], 'br' => [],
    ];

    public static function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>',
            LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if ($root === null) {
            return '';
        }
        self::clean($root, $doc);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    private static function clean(DOMElement $element, DOMDocument $doc): void
    {
        $children = [];
        foreach ($element->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE
                || $child->nodeType === XML_PI_NODE) {
                $element->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->localName);

            if ($tag === 'script' || $tag === 'style' || $tag === 'iframe' || $tag === 'object'
                || $tag === 'embed' || $tag === 'form' || $tag === 'svg' || $tag === 'math') {
                // dangerous: remove including contents
                $element->removeChild($child);
                continue;
            }

            if (!isset(self::ALLOWED[$tag])) {
                // unknown tag: unwrap (keep the children)
                self::clean($child, $doc);
                while ($child->firstChild !== null) {
                    $element->insertBefore($child->firstChild, $child);
                }
                $element->removeChild($child);
                continue;
            }

            self::clean($child, $doc);
            self::cleanAttributes($child, $tag, $doc);

            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $element->removeChild($child); // img lost its src during cleaning
            }
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag, DOMDocument $doc): void
    {
        $allowed = self::ALLOWED[$tag];
        $attributes = [];
        foreach ($el->attributes ?? [] as $attribute) {
            $attributes[] = $attribute;
        }
        foreach ($attributes as $attribute) {
            $name = strtolower($attribute->nodeName);
            if (!in_array($name, $allowed, true)) {
                $el->removeAttributeNode($attribute);
            }
        }
        if ($tag === 'a' && $el->hasAttribute('href')) {
            $href = trim($el->getAttribute('href'));
            if (!preg_match('#^(https?://|mailto:|/|\#)#i', $href) || preg_match('/[\x00-\x1f]/', $href)) {
                $el->removeAttribute('href');
            } else {
                $el->setAttribute('rel', 'noopener');
                if (preg_match('#^https?://#i', $href)) {
                    $el->setAttribute('target', '_blank');
                }
            }
        }
        if ($tag === 'img' && $el->hasAttribute('src')) {
            if (!self::isOwnMediaUrl(trim($el->getAttribute('src')))) {
                $el->removeAttribute('src');
            }
        }
    }

    /**
     * Images may only come from our own protected media routes.
     */
    private static function isOwnMediaUrl(string $src): bool
    {
        $base = rtrim((string) Config::env('APP_URL', ''), '/');
        $prefixes = [];
        foreach (['/news-media/', '/files/'] as $route) {
            $prefixes[] = $base . $route;
            $path = (string) parse_url($base, PHP_URL_PATH);
            $prefixes[] = ($path === '' ? '' : $path) . $route;
            $prefixes[] = $route;
        }
        foreach (array_unique($prefixes) as $prefix) {
            if ($prefix !== '' && str_starts_with($src, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
