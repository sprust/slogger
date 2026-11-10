<?php

declare (strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class DeptracTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, bool}> */
    public static function dependencies(): iterable
    {
        yield 'domain uses repository' => ['layers', 'Mcp/Domain/Actions/ReadAction', 'Mcp/Repositories/TraceRepository', true];
        yield 'transport uses entity' => ['layers', 'Mcp/Transports/TraceTransport', 'Trace/Entities/TraceObject', true];
        yield 'transport cannot call domain' => ['layers', 'Mcp/Transports/TraceTransport', 'Trace/Domain/Actions/ReadAction', false];
        yield 'entity cannot call transport' => ['layers', 'Mcp/Entities/TraceObject', 'Mcp/Transports/TraceTransport', false];
        yield 'infrastructure cannot call transport' => ['layers', 'Mcp/Infrastructure/Http/Controller', 'Mcp/Transports/TraceTransport', false];
        yield 'foreign domain integration' => ['modules', 'Mcp/Domain/Actions/ReadAction', 'Trace/Domain/Actions/ReadAction', true];
        yield 'foreign repository forbidden' => ['modules', 'Mcp/Repositories/TraceRepository', 'Trace/Repositories/TraceRepository', false];
        yield 'foreign infrastructure forbidden' => ['modules', 'Mcp/Infrastructure/Http/Controller', 'Trace/Infrastructure/Http/Controller', false];
    }

    #[DataProvider('dependencies')]
    public function testDependency(
        string $config,
        string $source,
        string $target,
        bool $allowed
    ): void {
        $root       = sys_get_temp_dir() . '/slogger-architecture-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();

        $filesystem->mkdir(
            dirs: [
                $root . '/code-analyse/deptrac',
                $root . '/app/Models',
                $root . '/app/Providers',
            ],
        );

        $extension = $config === 'layers' ? 'yaml' : 'php';
        $filename  = "deptrac-{$config}.{$extension}";

        $filesystem->copy(
            originFile: __DIR__ . '/../../code-analyse/deptrac/' . $filename,
            targetFile: $root . '/code-analyse/deptrac/' . $filename,
        );

        $targetClass = 'App\Modules\\'
            . str_replace('/', '\\', $target);

        foreach ([$target, $source] as $path) {
            $namespace = 'App\Modules\\'
                . str_replace('/', '\\', dirname($path));
            $dependency = $path === $source
                ? "public function __construct(public \\{$targetClass} \$dependency) {}"
                : '';

            $filesystem->dumpFile(
                filename: $root . '/app/Modules/' . $path . '.php',
                content: "<?php\nnamespace {$namespace};\nclass " . basename($path) . " { {$dependency} }\n",
            );
        }

        try {
            $process = new Process(
                command: [
                    PHP_BINARY,
                    __DIR__ . '/../../vendor/bin/deptrac',
                    'analyse',
                    '--config-file=' . $root . '/code-analyse/deptrac/' . $filename,
                    '--no-progress',
                    '--no-cache',
                ],
            );

            $process->run();

            self::assertSame(
                $allowed ? 0 : 1,
                $process->getExitCode(),
                $process->getOutput() . $process->getErrorOutput()
            );

            if (!$allowed) {
                self::assertStringContainsString(
                    'DependsOnDisallowedLayer',
                    $process->getOutput()
                );
            }
        } finally {
            $filesystem->remove($root);
        }
    }
}
