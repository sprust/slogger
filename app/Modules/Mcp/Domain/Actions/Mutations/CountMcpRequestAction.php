<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Mutations;

use App\Modules\Mcp\Entities\McpObject;
use App\Modules\Mcp\Repositories\McpRepository;
use Illuminate\Support\Carbon;

readonly class CountMcpRequestAction
{
    public function __construct(
        private McpRepository $mcpRepository
    ) {
    }

    public function handle(McpObject $mcp): void
    {
        $this->mcpRepository->incrementRequestsCount(
            id: $mcp->id,
            lastUsedAt: Carbon::now()
        );
    }
}
