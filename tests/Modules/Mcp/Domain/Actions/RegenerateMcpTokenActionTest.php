<?php

namespace Tests\Modules\Mcp\Domain\Actions;

use App\Modules\Mcp\Domain\Actions\Mutations\RegenerateMcpTokenAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpAction;
use App\Modules\Mcp\Domain\Exceptions\McpNotFoundException;
use App\Modules\Mcp\Repositories\McpRepository;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpFactoryTrait;

class RegenerateMcpTokenActionTest extends TestCase
{
    use McpFactoryTrait;

    public function testWritesANewToken(): void
    {
        $repository = $this->createMock(McpRepository::class);

        $repository->method('findOneById')->willReturn($this->mcpObject(token: 'old-token'));
        $repository->expects($this->once())
            ->method('updateToken')
            ->with(1, $this->callback(static fn(string $token): bool => strlen($token) === 50 && $token !== 'old-token'));

        $this->action($repository)->handle(1);
    }

    public function testUnknownMcpThrows(): void
    {
        $repository = $this->createMock(McpRepository::class);

        $repository->method('findOneById')->willReturn(null);
        $repository->expects($this->never())->method('updateToken');

        $this->expectException(McpNotFoundException::class);

        $this->action($repository)->handle(1);
    }

    private function action(McpRepository $repository): RegenerateMcpTokenAction
    {
        return new RegenerateMcpTokenAction(
            $repository,
            new FindMcpAction($repository)
        );
    }
}
