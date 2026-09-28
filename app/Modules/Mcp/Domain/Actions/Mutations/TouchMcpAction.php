<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Mutations;

use App\Modules\Mcp\Entities\McpObject;
use App\Modules\Mcp\Repositories\McpRepository;
use Illuminate\Support\Carbon;

readonly class TouchMcpAction
{
    private const int INTERVAL_SECONDS = 60;

    public function __construct(
        private McpRepository $mcpRepository
    ) {
    }

    public function handle(McpObject $mcp): void
    {
        $now = Carbon::now();

        if (!is_null($mcp->lastUsedAt) && $mcp->lastUsedAt->diffInSeconds($now) < self::INTERVAL_SECONDS) {
            return;
        }

        $this->mcpRepository->updateLastUsedAt(
            id: $mcp->id,
            lastUsedAt: $now
        );
    }
}
