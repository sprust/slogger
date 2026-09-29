<?php

namespace Tests\Modules\Mcp\Domain\Actions;

use App\Modules\Mcp\Domain\Actions\Mutations\CountMcpRequestAction;
use App\Modules\Mcp\Repositories\McpRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpFactoryTrait;

class CountMcpRequestActionTest extends TestCase
{
    use McpFactoryTrait;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testEveryRequestIsCounted(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:10');

        $repository = $this->createMock(McpRepository::class);

        $repository->expects($this->once())
            ->method('incrementRequestsCount')
            ->with(1, $this->callback(static fn(Carbon $at): bool => $at->eq(Carbon::now())));

        new CountMcpRequestAction($repository)->handle(
            $this->mcpObject(lastUsedAt: Carbon::parse('2026-09-28 12:00:00'))
        );
    }
}
