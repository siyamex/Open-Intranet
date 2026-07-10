<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\SvgSanitizer;

final class SvgTestCommand
{
    public const DESCRIPTION = 'Prove SvgSanitizer neutralizes malicious SVG samples';

    public static function run(array $args): int
    {
        $samples = [
            'script tag' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><circle r="5"/></svg>',
                static fn (?string $out): bool => $out !== null && !str_contains($out, 'script') && str_contains($out, 'circle'),
            ],
            'onload handler' => [
                '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><rect width="4" height="4"/></svg>',
                static fn (?string $out): bool => $out !== null && !str_contains($out, 'onload'),
            ],
            'onclick on child' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><path d="M0 0h4" onclick="steal()"/></svg>',
                static fn (?string $out): bool => $out !== null && !str_contains($out, 'onclick'),
            ],
            'foreignObject iframe' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><iframe src="https://evil.example"></iframe></foreignObject></svg>',
                static fn (?string $out): bool => $out !== null && !str_contains($out, 'foreignObject') && !str_contains($out, 'iframe'),
            ],
            'external use href' => [
                '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="https://evil.example/x.svg#p"/></svg>',
                static fn (?string $out): bool => $out !== null && !str_contains($out, 'evil.example'),
            ],
            'javascript: href' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><use href="javascript:alert(1)"/></svg>',
                static fn (?string $out): bool => $out !== null && !str_contains($out, 'javascript:'),
            ],
            'CSS url() exfil' => [
                '<svg xmlns="http://www.w3.org/2000/svg"><rect width="4" height="4" style="fill:url(https://evil.example/t)"/></svg>',
                static fn (?string $out): bool => $out !== null && !str_contains($out, 'url('),
            ],
            'entity/XXE doctype' => [
                '<?xml version="1.0"?><!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg xmlns="http://www.w3.org/2000/svg">&x;</svg>',
                static fn (?string $out): bool => $out === null,
            ],
            'not svg at all' => [
                '<html><body>hi</body></html>',
                static fn (?string $out): bool => $out === null,
            ],
            'clean icon survives' => [
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 11l9-8 9 8"/></svg>',
                static fn (?string $out): bool => $out !== null && str_contains($out, 'M3 11l9-8 9 8'),
            ],
        ];

        $failures = 0;
        foreach ($samples as $label => [$input, $assert]) {
            $output = SvgSanitizer::sanitize($input);
            $ok = $assert($output);
            echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . "\n";
            if (!$ok) {
                $failures++;
                echo '        output: ' . var_export($output, true) . "\n";
            }
        }
        echo $failures === 0 ? "All samples neutralized.\n" : "{$failures} sample(s) NOT handled!\n";
        return $failures === 0 ? 0 : 1;
    }
}
