<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Word-level diff (LCS) rendering <del>/<ins> markup — used by the wiki
 * version history.
 */
final class Diff
{
    public static function words(string $old, string $new): string
    {
        $oldWords = preg_split('/(\s+)/u', $old, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $newWords = preg_split('/(\s+)/u', $new, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

        // Cap for pathological inputs — fall back to block diff
        if (count($oldWords) * count($newWords) > 4_000_000) {
            return '<del>' . e($old) . '</del><ins>' . e($new) . '</ins>';
        }

        $m = count($oldWords);
        $n = count($newWords);
        // LCS table (two rows to save memory)
        $prev = array_fill(0, $n + 1, 0);
        $table = [];
        for ($i = $m - 1; $i >= 0; $i--) {
            $current = array_fill(0, $n + 1, 0);
            for ($j = $n - 1; $j >= 0; $j--) {
                $current[$j] = $oldWords[$i] === $newWords[$j]
                    ? $prev[$j + 1] + 1
                    : max($prev[$j], $current[$j + 1]);
            }
            $table[$i] = $current;
            $prev = $current;
        }

        $out = '';
        $i = 0;
        $j = 0;
        $buffer = ['same' => '', 'del' => '', 'ins' => ''];
        $flush = static function () use (&$buffer, &$out): void {
            if ($buffer['del'] !== '') {
                $out .= '<del>' . e($buffer['del']) . '</del>';
                $buffer['del'] = '';
            }
            if ($buffer['ins'] !== '') {
                $out .= '<ins>' . e($buffer['ins']) . '</ins>';
                $buffer['ins'] = '';
            }
        };
        while ($i < $m && $j < $n) {
            if ($oldWords[$i] === $newWords[$j]) {
                $flush();
                $out .= e($newWords[$j]);
                $i++;
                $j++;
            } elseif (($table[$i + 1][$j] ?? 0) >= ($table[$i][$j + 1] ?? 0)) {
                $buffer['del'] .= $oldWords[$i];
                $i++;
            } else {
                $buffer['ins'] .= $newWords[$j];
                $j++;
            }
        }
        while ($i < $m) {
            $buffer['del'] .= $oldWords[$i++];
        }
        while ($j < $n) {
            $buffer['ins'] .= $newWords[$j++];
        }
        $flush();
        return $out;
    }
}
