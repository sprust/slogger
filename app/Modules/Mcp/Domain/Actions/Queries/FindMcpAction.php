<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Queries;

use App\Modules\Mcp\Entities\McpObject;
use App\Modules\Mcp\Repositories\McpRepository;

readonly class FindMcpAction
{
    public function __construct(
        private McpRepository $mcpRepository
    ) {
    }

    public function handle(int $id): ?McpObject
    {
        return $this->mcpRepository->findOneById($id);
    }
}
