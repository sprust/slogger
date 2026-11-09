<?php

declare(strict_types=1);

namespace Tests\Modules\Mcp\Integration;

use Tests\Modules\BaseModuleTestCase;
use App\Modules\Mcp\Domain\UseCases\RegenerateMcpTokenByAdminUseCase;
use App\Modules\Mcp\Repositories\McpRepository;
use Tests\Factories\Entities\McpFactory;

class RegenerateMcpTokenDatabaseTest extends BaseModuleTestCase
{
    public function test(): void
    {
        $mcpFactory = $this->appMake(McpFactory::class);

        $token = fake()->uuid();
        $mcpId = $mcpFactory->create(
            token: $token,
        );

        $this->appMake(RegenerateMcpTokenByAdminUseCase::class)->handle($mcpId);

        $mcpRepository = $this->appMake(McpRepository::class);
        $mcp           = $mcpRepository->findOneById($mcpId);

        self::assertNotNull(
            $mcp
        );
        self::assertNotSame(
            expected: $token,
            actual: $mcp->token
        );
        self::assertNull(
            $mcpRepository->findOneByToken($token)
        );
        self::assertSame(
            expected: $mcpId,
            actual: $mcpRepository->findOneByToken($mcp->token)?->id
        );
    }
}
