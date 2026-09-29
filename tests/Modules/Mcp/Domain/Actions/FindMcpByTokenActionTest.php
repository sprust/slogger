<?php

namespace Tests\Modules\Mcp\Domain\Actions;

use App\Modules\Mcp\Domain\Actions\Queries\FindMcpByTokenAction;
use App\Modules\Mcp\Repositories\McpRepository;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpFactoryTrait;

class FindMcpByTokenActionTest extends TestCase
{
    use McpFactoryTrait;

    public function testFindsAnEnabledMcp(): void
    {
        $repository = $this->createMock(McpRepository::class);

        $repository->method('findOneByToken')->willReturn($this->mcpObject(id: 7));

        $this->assertSame(7, $this->action($repository)->handle('token')?->id);
    }

    public function testSkipsADisabledMcp(): void
    {
        $repository = $this->createMock(McpRepository::class);

        $repository->method('findOneByToken')->willReturn($this->mcpObject(enabled: false));

        $this->assertNull($this->action($repository)->handle('token'));
    }

    public function testUnknownTokenFindsNothing(): void
    {
        $repository = $this->createMock(McpRepository::class);

        $repository->method('findOneByToken')->willReturn(null);

        $this->assertNull($this->action($repository)->handle('token'));
    }

    private function action(McpRepository $repository): FindMcpByTokenAction
    {
        return new FindMcpByTokenAction($repository);
    }
}
