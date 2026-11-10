<?php

declare (strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class PhpStanRulesTest extends TestCase
{
    public function testMultilineArrayKey(): void
    {
        $root       = sys_get_temp_dir() . '/slogger-stan-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();

        $filesystem->mkdir(
            dirs: $root,
        );

        $filesystem->dumpFile(
            filename: $root . '/fixture.php',
            content: <<<'PHP_FIXTURE'
                <?php
                $values = ['key' => 1];
                $invalid = $values[
                    strtolower(
                        'KEY'
                    )
                ];
                $key = strtolower('KEY');
                $valid = $values[$key];
                PHP_FIXTURE,
        );
        $filesystem->dumpFile(
            filename: $root . '/phpstan.neon',
            content: "parameters:\n    level: 0\n    bootstrapFiles: [" . dirname(__DIR__, 2)
                . "/vendor/autoload.php]\nrules:\n    - CodeAnalyse\\PhpStan\\NoMultilineArrayKeyRule\n",
        );

        try {
            $process = new Process(
                command: [
                    PHP_BINARY,
                    __DIR__ . '/../../vendor/bin/phpstan',
                    'analyse',
                    '-c',
                    $root . '/phpstan.neon',
                    $root . '/fixture.php',
                    '--no-progress',
                    '--error-format=json',
                ],
            );

            $process->run();

            self::assertSame(
                1,
                $process->getExitCode(),
                $process->getErrorOutput()
            );

            $result = json_decode(
                json: $process->getOutput(),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
            $messages = $result['files'][$root . '/fixture.php']['messages'];

            self::assertCount(
                expectedCount: 1,
                haystack: $messages
            );
            self::assertSame(
                expected: 'ifksco.multilineArrayKey',
                actual: $messages[0]['identifier']
            );
        } finally {
            $filesystem->remove($root);
        }
    }
}
