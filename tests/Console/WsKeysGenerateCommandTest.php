<?php

namespace Tests\Console;

use App\Console\Commands\WsKeysGenerateCommand;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The part of the command that edits somebody's .env.
 *
 * Everything else it does is read a file and write it back; this is the piece that can
 * quietly corrupt a working installation, so it is the piece with tests. The command is
 * driven through reflection rather than the console, because the rewriting has no need
 * of a container and a test that boots one would be testing Laravel.
 */
class WsKeysGenerateCommandTest extends TestCase
{
    public function testAnExistingAssignmentIsReplacedInPlace(): void
    {
        $this->assertSame(
            "A=1\nSCONCUR_WS_APP_KEY=new\nB=2\n",
            $this->set("A=1\nSCONCUR_WS_APP_KEY=old\nB=2\n", 'SCONCUR_WS_APP_KEY', 'new')
        );
    }

    public function testAMissingAssignmentIsAppended(): void
    {
        $this->assertSame(
            "A=1\nSCONCUR_WS_APP_KEY=new\n",
            $this->set("A=1\n", 'SCONCUR_WS_APP_KEY', 'new')
        );
    }

    public function testAVariableThatEndsWithTheNameIsLeftAlone(): void
    {
        // frontend/.env carries both, and VITE_SCONCUR_WS_KEY is the one that must keep
        // its interpolation — it reads the value being written here.
        $this->assertSame(
            "SCONCUR_WS_KEY=new\nVITE_SCONCUR_WS_KEY=\${SCONCUR_WS_KEY}\n",
            $this->set("SCONCUR_WS_KEY=\nVITE_SCONCUR_WS_KEY=\${SCONCUR_WS_KEY}\n", 'SCONCUR_WS_KEY', 'new')
        );
    }

    public function testAnEmptyAssignmentDoesNotCountAsFilled(): void
    {
        $this->assertFalse($this->isFilled("SCONCUR_WS_APP_KEY=\n", 'SCONCUR_WS_APP_KEY'));
        $this->assertTrue($this->isFilled("SCONCUR_WS_APP_KEY=abc\n", 'SCONCUR_WS_APP_KEY'));
    }

    public function testAnAssignmentOnlyMentionedInAValueDoesNotCountAsFilled(): void
    {
        $this->assertFalse($this->isFilled("OTHER=\${SCONCUR_WS_APP_KEY}\n", 'SCONCUR_WS_APP_KEY'));
    }

    private function set(string $env, string $name, string $value): string
    {
        return $this->call('set', $env, $name, $value);
    }

    private function isFilled(string $env, string $name): bool
    {
        return $this->call('isFilled', $env, $name);
    }

    private function call(string $method, mixed ...$arguments): mixed
    {
        return new ReflectionMethod(WsKeysGenerateCommand::class, $method)
            ->invoke(new WsKeysGenerateCommand(), ...$arguments);
    }
}
