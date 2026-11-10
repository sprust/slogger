<?php

declare (strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class ModuleStructureTest extends TestCase
{
    public function testExistingTree(): void
    {
        self::assertSame(
            [],
            $this->errors(__DIR__ . '/../../app/Modules')
        );
    }

    public function testOptionalFoldersAndUnknownDirectories(): void
    {
        $root       = sys_get_temp_dir() . '/slogger-structure-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();

        $filesystem->mkdir(
            dirs: [
                $root . '/Minimal/Enums',
                $root . '/Minimal/Transports',
                $root . '/Full/Repositories/Dto',
                $root . '/Full/Infrastructure/Tasks',
                $root . '/Full/Infrastructure/Providers',
                $root . '/Full/Domain/UseCases',
            ],
        );

        try {
            self::assertSame(
                [],
                $this->errors($root)
            );

            $filesystem->mkdir(
                dirs: [
                    $root . '/Full/Domain/Typo',
                    $root . '/Minimal/Unknown',
                ],
            );

            $filesystem->dumpFile($root . '/Unowned.php', '<?php class Unowned {}');

            $filesystem->dumpFile(
                $root . '/Minimal/BadAction.php',
                '<?php class BadAction {}'
            );

            $filesystem->dumpFile(
                $root . '/Full/Domain/HiddenAction.php',
                '<?php class HiddenAction {}'
            );

            $filesystem->dumpFile(
                $root . '/Full/Infrastructure/Misplaced.php',
                '<?php class Misplaced {}'
            );

            $process = new Process(
                command: [
                    PHP_BINARY,
                    __DIR__ . '/../../code-analyse/module-structure/modules-structure.php',
                    $root,
                ],
            );

            $process->run();

            self::assertSame(
                1,
                $process->getExitCode()
            );

            self::assertStringContainsString(
                'Full/Domain/Typo',
                $process->getErrorOutput()
            );

            self::assertStringContainsString(
                'Minimal/Unknown',
                $process->getErrorOutput()
            );

            self::assertStringContainsString(
                'Unowned.php',
                $process->getErrorOutput()
            );

            self::assertStringContainsString(
                'Minimal/BadAction.php',
                $process->getErrorOutput()
            );

            self::assertStringContainsString(
                'Full/Domain/HiddenAction.php',
                $process->getErrorOutput()
            );

            self::assertStringContainsString(
                'Full/Infrastructure/Misplaced.php',
                $process->getErrorOutput()
            );
        } finally {
            $filesystem->remove($root);
        }
    }

    /** @return list<string> */
    private function errors(string $root): array
    {
        $process = new Process([
            PHP_BINARY,
            __DIR__ . '/../../code-analyse/module-structure/modules-structure.php',
            $root,
        ]);
        $process->run();

        self::assertSame(
            expected: 0,
            actual: $process->getExitCode(),
            message: $process->getErrorOutput()
        );

        return [];
    }
}
