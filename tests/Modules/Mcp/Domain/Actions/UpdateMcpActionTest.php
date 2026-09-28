<?php

namespace Tests\Modules\Mcp\Domain\Actions;

use App\Modules\Mcp\Domain\Actions\Mutations\DeleteMcpAction;
use App\Modules\Mcp\Domain\Actions\Mutations\UpdateMcpAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpAction;
use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Mcp\Parameters\UpdateMcpParameters;
use App\Modules\Mcp\Repositories\McpRepository;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpFactoryTrait;

class UpdateMcpActionTest extends TestCase
{
    use McpFactoryTrait;

    public function testUpdatesNameAndEnabled(): void
    {
        $repository = $this->createMock(McpRepository::class);

        $repository->method('findOneById')->willReturn($this->mcpObject());
        $repository->expects($this->once())
            ->method('update')
            ->with(1, 'CI agent', false);

        $this->action($repository)->handle(
            new UpdateMcpParameters(id: 1, name: 'CI agent', enabled: false)
        );
    }

    public function testUpdatingUnknownMcpThrows(): void
    {
        $repository = $this->createMock(McpRepository::class);

        $repository->method('findOneById')->willReturn(null);
        $repository->expects($this->never())->method('update');

        $this->expectException(McpNotFoundException::class);

        $this->action($repository)->handle(
            new UpdateMcpParameters(id: 1, name: 'CI agent', enabled: false)
        );
    }

    public function testDeletingUnknownMcpThrows(): void
    {
        $repository = $this->createMock(McpRepository::class);

        $repository->method('delete')->willReturn(false);

        $this->expectException(McpNotFoundException::class);

        new DeleteMcpAction($repository)->handle(1);
    }

    private function action(McpRepository $repository): UpdateMcpAction
    {
        return new UpdateMcpAction(
            $repository,
            new FindMcpAction($repository)
        );
    }
}
