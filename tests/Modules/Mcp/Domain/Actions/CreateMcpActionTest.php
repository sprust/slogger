<?php

namespace Tests\Modules\Mcp\Domain\Actions;

use App\Modules\Mcp\Domain\Actions\Mutations\CreateMcpAction;
use App\Modules\Mcp\Parameters\CreateMcpParameters;
use App\Modules\Mcp\Repositories\McpRepository;
use PHPUnit\Framework\TestCase;
use Tests\Modules\Mcp\McpFactoryTrait;

class CreateMcpActionTest extends TestCase
{
    use McpFactoryTrait;

    public function testCreatesWithAGeneratedToken(): void
    {
        $tokens     = [];
        $repository = $this->createMock(McpRepository::class);

        $repository->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(
                function (string $name, string $token) use (&$tokens) {
                    $tokens[] = $token;

                    return $this->mcpObject(token: $token);
                }
            );

        $action = new CreateMcpAction($repository);

        $first  = $action->handle(new CreateMcpParameters(name: 'Claude Code'));
        $second = $action->handle(new CreateMcpParameters(name: 'CI agent'));

        $this->assertSame(50, strlen($first->token));
        $this->assertSame(50, strlen($second->token));
        $this->assertNotSame($first->token, $second->token);
        $this->assertSame($tokens, [$first->token, $second->token]);
    }
}
