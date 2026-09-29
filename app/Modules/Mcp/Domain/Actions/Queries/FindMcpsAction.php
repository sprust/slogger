<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Queries;

use App\Modules\Mcp\Entities\McpObject;
use App\Modules\Mcp\Repositories\McpRepository;

readonly class FindMcpsAction
{
    public function __construct(
        private McpRepository $mcpRepository
    ) {
    }

    /**
     * @return McpObject[]
     */
    public function handle(): array
    {
        return $this->mcpRepository->find();
    }
}
