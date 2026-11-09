<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

require __DIR__ . '/../vendor/autoload.php';

$root  = dirname(__DIR__);
$files = Finder::create()
    ->files()
    ->in([
        "$root/app",
        "$root/bootstrap",
        "$root/code-analyse",
        "$root/config",
        "$root/database",
        "$root/packages",
        "$root/routes",
        "$root/tests",
    ])
    ->exclude([
        'cache',
        'vendor',
        'migrations',
    ])
    ->name('*.php')
    ->notName('*.blade.php');

$violations = 0;

foreach ($files as $file) {
    $code         = $file->getContents();
    $fixtureLines = [];
    $line         = 1;
    $inHeredoc    = false;

    // Embedded fixtures are external input: reflowing them changes the scenario.
    foreach (token_get_all($code) as $token) {
        $content = is_array($token) ? $token[1] : $token;
        $kind    = is_array($token) ? $token[0] : null;

        if ($kind === T_START_HEREDOC) {
            $inHeredoc = true;
        }

        $endLine = $line + substr_count($content, "\n");

        if ($inHeredoc) {
            for ($fixtureLine = $line; $fixtureLine <= $endLine; ++$fixtureLine) {
                $fixtureLines[$fixtureLine] = true;
            }
        }

        if ($kind === T_END_HEREDOC) {
            $inHeredoc = false;
        }

        $line = $endLine;
    }

    foreach (explode("\n", $code) as $index => $content) {
        if (
            array_key_exists($index + 1, $fixtureLines)
            || preg_match('/^\s*(?:namespace|use)\s/', $content) === 1
        ) {
            continue;
        }

        $length = mb_strlen($content);

        if ($length <= 120) {
            continue;
        }

        ++$violations;

        printf(
            "%s:%d: %d characters (maximum 120).\n",
            substr($file->getPathname(), strlen($root) + 1),
            $index + 1,
            $length,
        );
    }
}

if ($violations === 0) {
    echo "PHP line lengths are valid.\n";
}

exit($violations === 0 ? 0 : 1);
