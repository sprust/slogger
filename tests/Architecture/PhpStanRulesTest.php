<?php

declare (strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class PhpStanRulesTest extends TestCase
{
    public function testInputContractsAndDomainDependencies(): void
    {
        $root       = sys_get_temp_dir() . '/slogger-stan-' . bin2hex(random_bytes(8));
        $filesystem = new Filesystem();

        $filesystem->mkdir(
            dirs: $root,
        );

        $filesystem->dumpFile(
            filename: $root . '/fixture.php',
            content: <<<'SOURCE'
            <?php
            namespace App\Modules\Trace\Parameters {
                class Filter {}
            }
            namespace App\Modules\Mcp\Parameters {
                class Inherited extends \App\Modules\Trace\Parameters\Filter {}
                class Nested { public \App\Modules\Trace\Parameters\Filter $filter; }
                class Local { public string $value; }
            }
            namespace App\Modules\Mcp\Domain\Actions {
                use App\Modules\Trace\Parameters\Filter as Alias;
                class Direct { public function handle(Alias $filter): void {} }
                class Union { public function handle(Alias|string $filter): void {} }
                class Child { public function handle(\App\Modules\Mcp\Parameters\Inherited $filter): void {} }
                class Nested { public function handle(\App\Modules\Mcp\Parameters\Nested $filter): void {} }
                class Generic {
                    /** @param array<string, Alias> $filters */
                    public function handle(array $filters): void {}
                }
                /** @phpstan-type Filters array<string, Alias> */
                class TypeAlias {
                    /** @param Filters $filters */
                    public function handle(array $filters): void {}
                }
                class Allowed {
                    public function handle(\App\Modules\Mcp\Parameters\Local $filter): void { $foreign = new Alias(); }
                }
            }
            namespace App\Modules\Mcp\Repositories {
                class FirstRepository { public function read(): void {} }
                class SecondRepository { public function read(): void { (new FirstRepository())->read(); } }
                class MappingService {}
                class AllowedRepository { public function read(): void { new MappingService(); } }
            }
            namespace App\Modules\Mcp\Domain\UseCases {
                class OtherUseCase { public function handle(): void {} }
                class RepositoryUseCase { public function handle(): void { (new \App\Modules\Mcp\Repositories\FirstRepository())->read(); } }
                class NestedUseCase { public function handle(): void { (new OtherUseCase())->handle(); } }
            }
            SOURCE,
        );

        $filesystem->dumpFile(
            filename: $root . '/phpstan.neon',
            content: <<<'CONFIG'
            parameters:
                level: 0
            services:
                - CodeAnalyse\PHPStan\ModuleTypes
                -
                    class: CodeAnalyse\PHPStan\InputParametersRule
                    tags: [phpstan.rules.rule]
                -
                    class: CodeAnalyse\PHPStan\DomainDependencyRule
                    tags: [phpstan.rules.rule]
            CONFIG,
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
            $inputs   = array_filter(
                $messages,
                static fn(array $message): bool => $message['identifier'] === 'architecture.foreignParameters',
            );

            self::assertCount(6, $inputs, $process->getOutput());

            $dependencies = array_filter(
                $messages,
                static fn(array $message): bool => $message['identifier'] === 'architecture.domainDependency',
            );

            self::assertNotEmpty($dependencies);

            $text = implode(
                "\n",
                array_column(
                    $dependencies,
                    'message',
                ),
            );

            foreach (
                [
                    'SecondRepository',
                    'RepositoryUseCase',
                    'NestedUseCase',
                ] as $invalid
            ) {
                self::assertStringContainsString($invalid, $text);
            }

            self::assertStringNotContainsString('Allowed', $process->getOutput());

            self::assertCount(
                count($inputs) + count($dependencies),
                $messages,
                $process->getOutput()
            );
        } finally {
            $filesystem->remove($root);
        }
    }
}
