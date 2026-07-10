<?php

declare(strict_types=1);

namespace App\Core;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * DOMDocument-based SVG sanitizer with a strict allowlist. Strips scripts,
 * event handlers (on*), foreignObject, external references and CSS url().
 */
final class SvgSanitizer
{
    private const ALLOWED_ELEMENTS = [
        'svg', 'g', 'path', 'circle', 'rect', 'ellipse', 'line', 'polyline', 'polygon',
        'defs', 'lineargradient', 'radialgradient', 'stop', 'title', 'desc', 'symbol',
        'use', 'text', 'tspan', 'clippath', 'mask', 'pattern', 'marker',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'viewbox', 'width', 'height', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'd', 'points', 'transform', 'fill', 'fill-rule', 'fill-opacity', 'stroke', 'stroke-width',
        'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-opacity',
        'stroke-miterlimit', 'opacity', 'clip-rule', 'clip-path', 'mask', 'id', 'class',
        'offset', 'stop-color', 'stop-opacity', 'gradientunits', 'gradienttransform',
        'patternunits', 'markerunits', 'refx', 'refy', 'orient', 'font-size', 'font-family',
        'text-anchor', 'dx', 'dy', 'xmlns', 'version', 'preserveaspectratio', 'aria-hidden', 'role',
        'href', 'xlink:href', 'style',
    ];

    /**
     * @return string|null sanitized SVG markup, or null if the input is not usable SVG
     */
    public static function sanitize(string $svg): ?string
    {
        if (strlen($svg) > 512 * 1024) {
            return null;
        }
        // Reject doctypes outright (XXE / entity tricks).
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg)) {
            return null;
        }
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded === false || $doc->documentElement === null
            || strtolower($doc->documentElement->localName) !== 'svg') {
            return null;
        }
        self::clean($doc->documentElement);
        $out = $doc->saveXML($doc->documentElement);
        return $out === false ? null : $out;
    }

    private static function clean(DOMElement $element): void
    {
        // Depth-first over a static list because we mutate the tree.
        $children = [];
        foreach ($element->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                if (!in_array(strtolower($child->localName), self::ALLOWED_ELEMENTS, true)) {
                    $element->removeChild($child);
                    continue;
                }
                self::clean($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE
                || $child->nodeType === XML_PI_NODE) {
                $element->removeChild($child);
            }
        }

        $attributes = [];
        foreach ($element->attributes ?? [] as $attribute) {
            $attributes[] = $attribute;
        }
        foreach ($attributes as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = (string) $attribute->nodeValue;
            $remove = false;

            if (str_starts_with($name, 'on')) {
                $remove = true;
            } elseif (!in_array($name, self::ALLOWED_ATTRIBUTES, true)) {
                $remove = true;
            } elseif ($name === 'href' || $name === 'xlink:href') {
                // Only same-document fragment references are allowed.
                if (!str_starts_with(trim($value), '#')) {
                    $remove = true;
                }
            } elseif ($name === 'style') {
                if (preg_match('/url\s*\(|expression\s*\(|javascript:|@import/i', $value)) {
                    $remove = true;
                }
            } elseif (preg_match('/javascript:|data:text|url\s*\(/i', $value)) {
                $remove = true;
            }

            if ($remove) {
                $element->removeAttributeNode($attribute);
            }
        }
    }
}
