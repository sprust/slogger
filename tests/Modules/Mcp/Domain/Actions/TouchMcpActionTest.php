<?php

namespace Tests\Modules\Mcp\Domain\Actions;

use App\Modules\Mcp\Domain\Actions\Mutations\TouchMcpAction;
use App\Modules\Mcp\Repositories\McpRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpFactoryTrait;

class TouchMcpActionTest extends TestCase
{
    use McpFactoryTrait;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testFirstUseIsWritten(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');

        $repository = $this->createMock(McpRepository::class);

        $repository->expects($this->once())
            ->method('updateLastUsedAt')
            ->with(1, $this->callback(static fn(Carbon $at): bool => $at->eq(Carbon::now())));

        new TouchMcpAction($repository)->handle($this->mcpObject());
    }

    public function testUseWithinAMinuteIsNotWritten(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:10');

        $repository = $this->createMock(McpRepository::class);

        $repository->expects($this->never())->method('updateLastUsedAt');

        new TouchMcpAction($repository)->handle(
            $this->mcpObject(lastUsedAt: Carbon::parse('2026-09-28 12:00:00'))
        );
    }

    public function testUseAfterAMinuteIsWritten(): void
    {
        Carbon::setTestNow('2026-09-28 12:01:00');

        $repository = $this->createMock(McpRepository::class);

        $repository->expects($this->once())->method('updateLastUsedAt');

        new TouchMcpAction($repository)->handle(
            $this->mcpObject(lastUsedAt: Carbon::parse('2026-09-28 12:00:00'))
        );
    }
}
