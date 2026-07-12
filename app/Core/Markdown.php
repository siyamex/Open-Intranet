<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small hand-written Markdown renderer with a strict HTML allowlist by
 * construction: ALL input is escaped first, then markdown syntax is
 * transformed into a fixed set of tags (h1-h4, p, strong, em, code, pre,
 * a, ul, ol, li, blockquote, hr). Raw HTML in the source stays escaped.
 */
final class Markdown
{
    /**
     * @return array{html: string, toc: array<int, array{level: int, id: string, text: string}>}
     */
    public static function render(string $markdown): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $html = '';
        $toc = [];
        $inCode = false;
        $listStack = []; // 'ul' | 'ol'
        $inQuote = false;
        $paragraph = [];

        $closeLists = static function () use (&$listStack, &$html): void {
            while ($listStack !== []) {
                $html .= '</' . array_pop($listStack) . ">\n";
            }
        };
        $flushParagraph = static function () use (&$paragraph, &$html): void {
            if ($paragraph !== []) {
                $html .= '<p>' . self::inline(implode(' ', $paragraph)) . "</p>\n";
                $paragraph = [];
            }
        };
        $closeQuote = static function () use (&$inQuote, &$html): void {
            if ($inQuote) {
                $html .= "</blockquote>\n";
                $inQuote = false;
            }
        };

        foreach ($lines as $line) {
            // fenced code blocks
            if (preg_match('/^```/', $line)) {
                $flushParagraph();
                $closeLists();
                $closeQuote();
                if ($inCode) {
                    $html .= "</code></pre>\n";
                    $inCode = false;
                } else {
                    $html .= '<pre><code>';
                    $inCode = true;
                }
                continue;
            }
            if ($inCode) {
                $html .= e($line) . "\n";
                continue;
            }

            // headings
            if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $m)) {
                $flushParagraph();
                $closeLists();
                $closeQuote();
                $level = strlen($m[1]);
                $text = trim($m[2]);
                $id = 'h-' . strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $text), '-'));
                if ($level >= 2 && $level <= 3) {
                    $toc[] = ['level' => $level, 'id' => $id, 'text' => $text];
                }
                $html .= "<h{$level} id=\"" . e($id) . '">' . self::inline($text) . "</h{$level}>\n";
                continue;
            }
            // horizontal rule
            if (preg_match('/^\s*(-{3,}|\*{3,})\s*$/', $line)) {
                $flushParagraph();
                $closeLists();
                $closeQuote();
                $html .= "<hr>\n";
                continue;
            }
            // blockquote
            if (preg_match('/^>\s?(.*)$/', $line, $m)) {
                $flushParagraph();
                $closeLists();
                if (!$inQuote) {
                    $html .= '<blockquote>';
                    $inQuote = true;
                }
                $html .= self::inline($m[1]) . '<br>';
                continue;
            }
            $closeQuote();
            // lists
            if (preg_match('/^\s*[-*]\s+(.*)$/', $line, $m)) {
                $flushParagraph();
                if (end($listStack) !== 'ul') {
                    $closeLists();
                    $html .= "<ul>\n";
                    $listStack[] = 'ul';
                }
                $html .= '<li>' . self::inline($m[1]) . "</li>\n";
                continue;
            }
            if (preg_match('/^\s*\d+[.)]\s+(.*)$/', $line, $m)) {
                $flushParagraph();
                if (end($listStack) !== 'ol') {
                    $closeLists();
                    $html .= "<ol>\n";
                    $listStack[] = 'ol';
                }
                $html .= '<li>' . self::inline($m[1]) . "</li>\n";
                continue;
            }
            // blank line
            if (trim($line) === '') {
                $flushParagraph();
                $closeLists();
                continue;
            }
            $paragraph[] = trim($line);
        }
        if ($inCode) {
            $html .= "</code></pre>\n";
        }
        $flushParagraph();
        $closeLists();
        $closeQuote();

        return ['html' => $html, 'toc' => $toc];
    }

    /**
     * Inline markdown on an UNESCAPED source string — escapes first,
     * then applies bold/italic/code/links.
     */
    private static function inline(string $text): string
    {
        $text = e($text);
        // inline code first (its contents stay literal)
        $text = (string) preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        $text = (string) preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = (string) preg_replace('/(?<!\*)\*([^*\s][^*]*)\*(?!\*)/', '<em>$1</em>', $text);
        // links: [label](https://… | /path | #anchor)
        $text = (string) preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            static function (array $m): string {
                $href = $m[2];
                if (!preg_match('#^(https?://|/|\#)#i', $href)) {
                    return $m[0];
                }
                $external = (bool) preg_match('#^https?://#i', $href);
                return '<a href="' . $href . '"' . ($external ? ' target="_blank" rel="noopener"' : '') . '>' . $m[1] . '</a>';
            },
            $text
        );
        return $text;
    }
}
