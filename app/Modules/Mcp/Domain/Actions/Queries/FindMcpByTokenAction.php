<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Queries;

use App\Modules\Mcp\Entities\McpObject;
use App\Modules\Mcp\Repositories\McpRepository;

readonly class FindMcpByTokenAction
{
    public function __construct(
        private McpRepository $mcpRepository
    ) {
    }

    public function handle(string $token): ?McpObject
    {
        $mcp = $this->mcpRepository->findOneByToken($token);

        if (is_null($mcp) || !$mcp->enabled) {
            return null;
        }

        return $mcp;
    }
}
