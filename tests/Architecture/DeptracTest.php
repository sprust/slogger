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
        yield 'domain uses transport' => [
            'layers',
            'Mcp/Domain/Actions/ReadAction',
            'Mcp/Transports/IncidentObjectTransport',
            true,
        ];

        yield 'transport uses data' => [
            'layers',
            'Mcp/Transports/IncidentObjectTransport',
            'Watcher/Entities/IncidentObject',
            true,
        ];

        yield 'transport cannot call domain' => [
            'layers',
            'Mcp/Transports/IncidentObjectTransport',
            'Watcher/Domain/Actions/ReadAction',
            false,
        ];

        yield 'transport cannot call infrastructure' => [
            'layers',
            'Mcp/Transports/IncidentObjectTransport',
            'Watcher/Infrastructure/Adapter',
            false,
        ];

        yield 'entity cannot call transport' => [
            'layers',
            'Mcp/Entities/IncidentObject',
            'Mcp/Transports/IncidentObjectTransport',
            false,
        ];

        yield 'infrastructure cannot call transport' => [
            'layers',
            'Mcp/Infrastructure/Adapter',
            'Mcp/Transports/IncidentObjectTransport',
            false,
        ];

        yield 'transport declared foreign data' => [
            'modules',
            'Mcp/Transports/IncidentObjectTransport',
            'Watcher/Entities/IncidentObject',
            true,
        ];

        yield 'transport reverse data forbidden' => [
            'modules',
            'Watcher/Transports/IncidentObjectTransport',
            'Notification/Entities/IncidentObject',
            false,
        ];

        yield 'transport foreign repository forbidden' => [
            'modules',
            'Mcp/Transports/IncidentObjectTransport',
            'Watcher/Repositories/ExampleRepository',
            false,
        ];

        yield 'transport foreign domain forbidden' => [
            'modules',
            'Mcp/Transports/IncidentObjectTransport',
            'Watcher/Domain/Actions/ReadAction',
            false,
        ];

        yield 'common transport no reverse edge' => [
            'modules',
            'Common/Transports/IncidentObjectTransport',
            'Watcher/Entities/IncidentObject',
            false,
        ];

        yield 'provider binds repository' => [
            'layers',
            'Watcher/Infrastructure/WatcherServiceProvider',
            'Watcher/Repositories/ExampleRepository',
            true,
        ];

        yield 'root infrastructure is collected' => [
            'layers',
            'Watcher/Infrastructure/Adapter',
            'Watcher/Repositories/ExampleRepository',
            false,
        ];

        yield 'enum cannot query repository' => [
            'layers',
            'Watcher/Enums/ExampleEnum',
            'Watcher/Repositories/ExampleRepository',
            false,
        ];

        yield 'common entities cannot query domain' => [
            'layers',
            'Common/Entities/ExampleObject',
            'Common/Domain/Actions/ReadAction',
            false,
        ];

        yield 'usecase cannot query repository' => [
            'layers',
            'Watcher/Domain/UseCases/ReadUseCase',
            'Watcher/Repositories/ExampleRepository',
            false,
        ];

        yield 'allowed integration' => [
            'modules',
            'Watcher/Domain/Actions/ReadAction',
            'Trace/Domain/Actions/ReadAction',
            true,
        ];

        yield 'foreign repository' => [
            'modules',
            'Watcher/Domain/Actions/ReadAction',
            'Trace/Repositories/ExampleRepository',
            false,
        ];

        yield 'foreign infrastructure' => [
            'modules',
            'Watcher/Infrastructure/Adapter',
            'Trace/Infrastructure/Adapter',
            false,
        ];

        yield 'reverse integration' => [
            'modules',
            'Watcher/Domain/Actions/ReadAction',
            'Notification/Domain/Actions/ReadAction',
            false,
        ];

        yield 'new module defaults closed' => [
            'modules',
            'NewModule/Domain/Actions/ReadAction',
            'Trace/Domain/Actions/ReadAction',
            false,
        ];

        yield 'event integration' => [
            'modules',
            'Notification/Infrastructure/Listener',
            'Watcher/Domain/Events/ChangedEvent',
            true,
        ];

        yield 'event is not entire domain' => [
            'modules',
            'Notification/Infrastructure/Listener',
            'Watcher/Domain/Actions/ReadAction',
            false,
        ];

        yield 'provider cannot bind foreign service' => [
            'modules',
            'Notification/Infrastructure/NotificationServiceProvider',
            'Watcher/Domain/Services/Registry',
            false,
        ];

        yield 'base provider is collected' => [
            'layers',
            'Common/Infrastructure/Providers/BaseServiceProvider',
            'Common/Domain/Services/DispatcherInterface',
            true,
        ];

        yield 'nested provider cannot bind foreign service' => [
            'modules',
            'Notification/Infrastructure/Providers/NotificationServiceProvider',
            'Watcher/Domain/Services/Registry',
            false,
        ];

        yield 'local dto cannot hold foreign dto' => [
            'modules',
            'Mcp/Entities/ExampleObject',
            'Trace/Entities/ExampleObject',
            false,
        ];

        yield 'common has no reverse edge' => [
            'modules',
            'Common/Domain/Actions/ReadAction',
            'Trace/Domain/Actions/ReadAction',
            false,
        ];
    }

    #[DataProvider('dependencies')]
    public function testDependency(string $config, string $source, string $target, bool $allowed): void
    {
        $root       = sys_get_temp_dir() . '/slogger-architecture-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();

        $filesystem->mkdir(
            dirs: [
                $root . '/code-analyse',
                $root . '/app/Models',
                $root . '/app/Providers',
            ],
        );

        $extension = $config === 'layers' ? 'yaml' : 'php';
        $filename  = "deptrac-{$config}.{$extension}";

        $filesystem->copy(
            originFile: __DIR__ . '/../../code-analyse/' . $filename,
            targetFile: $root . '/code-analyse/' . $filename,
        );

        $targetClass = 'App\Modules\\' . str_replace(
            '/',
            '\\',
            $target,
        );

        foreach (
            [
                $target,
                $source,
            ] as $path
        ) {
            $namespace = 'App\Modules\\' . str_replace(
                '/',
                '\\',
                dirname($path),
            );
            $dependency = $path === $source ? "public function __construct(public \\{$targetClass} \$dependency) {}" : '';

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
                    '--config-file=' . $root . '/code-analyse/' . $filename,
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
                self::assertStringContainsString('DependsOnDisallowedLayer', $process->getOutput());
            }
        } finally {
            $filesystem->remove($root);
        }
    }
}
